<?php

namespace App\Pages\Register;

use App\Entity\Pay;
use App\Helper as H;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * журнал расчет с контрагентами
 */
class PayCustList extends \App\Pages\Base
{

    private $_doc = null;
    private $_cust = null;
    public $_custlist = array();
    public $_doclist = array();
    public $_pays = array();

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('PayCustList')) {
            return;
        }

        $this->add(new Panel("clist"));

        $this->clist->add(new DataView('custlist', new ArrayDataSource($this, '_custlist'), $this, 'custlistOnRow'));


        $this->add(new Panel("plist"))->setVisible(false);
        $this->plist->add(new Label("cname"));
        $this->plist->add(new ClickLink("back", $this, "onBack"));

        $doclist = $this->plist->add(new DataView('doclist', new ArrayDataSource($this, '_doclist'), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        $this->add(new Panel("paypan"))->setVisible(false);
        $this->paypan->add(new Label("pname"));
        $this->paypan->add(new Form('payform'))->onSubmit($this, 'payOnSubmit');
        $this->paypan->payform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(), H::getDefMF()));
        $this->paypan->payform->add(new TextInput('pamount'));
        $this->paypan->payform->add(new TextInput('pcomment'));
        $this->paypan->payform->add(new Date('pdate', time()));


        $this->paypan->add(new DataView('paylist', new ArrayDataSource($this, '_pays'), $this, 'payOnRow'))->Reload();

        $this->clist->add(new ClickLink('csv', $this, 'oncsv'));
        $this->plist->add(new ClickLink('csv2', $this, 'oncsv'));


        $this->updateCust();
    }

    public function updateCust() {
        $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }

        $sql = "select c.customer_name,c.phone, c.customer_id,sam, fl from (
        select customer_id,   coalesce(sum(payamount - payed),0) as sam,
        (case when
         (SELECT coalesce(sum(amount),0)  FROM `paylist` WHERE documents.document_id = paylist.document_id )>0
         then 1 else -1 end ) as fl
            from `documents`  
            where {$br}  payamount > 0 and payamount > payed  and state not in (1,2,3,17,8)   
            group by customer_id, fl ) t join customers c  on t.customer_id = c.customer_id order by c.customer_name ";
        $this->_custlist = \App\DataItem::query($sql);
        $this->clist->custlist->Reload();
    }

    public function custlistOnRow($row) {
        $cust = $row->getDataItem();
        $row->add(new RedirectLink('customer_name', "\\App\\Pages\\Reference\\CustomerList", array($cust->customer_id)))->setValue($cust->customer_name);
        $row->add(new Label('phone', $cust->phone));
        $row->add(new Label('credit', H::fa($cust->fl == -1 ? $cust->sam : "")));
        $row->add(new Label('debet', H::fa($cust->fl == 1 ? $cust->sam : "")));

        $row->add(new ClickLink('showdocs'))->onClick($this, 'showdocsOnClick');
    }

    //список документов
    public function showdocsOnClick($sender) {

        $this->_cust = $sender->owner->getDataItem();
        $this->plist->cname->setText($this->_cust->customer_name);
        $this->updateDocs();

        $this->clist->setVisible(false);
        $this->plist->setVisible(true);
    }

    public function updateDocs() {

        if ($this->_cust->fl == -1) {
            $docs = "'GoodsReceipt','InvoiceCust','ReturnIssue'";
        }
        if ($this->_cust->fl == 1) {
            $docs = "'GoodsIssue', 'ServiceAct','Invoice','POSCheck','RetCustIssue'";
        }

        $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }


        $this->_doclist = \App\Entity\Doc\Document::find(" {$br} customer_id= {$this->_cust->customer_id} and payamount > 0 and payamount  > payed  and state not in (1,2,3,17,8)  and meta_name in({$docs})", "(payamount - payed) desc");

        $this->plist->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('name', $doc->meta_desc));
        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('date', date('d.m.Y', $doc->document_date)));


        $row->add(new Label('amount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));

        $row->add(new Label('payamount', H::fa($doc->payamount - $doc->payed)));


        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('pay'))->onClick($this, 'payOnClick');
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }
        $this->plist->doclist->setSelectedRow($sender->getOwner());
        $this->plist->doclist->Reload(false);
        $this->docview->setVisible(true);
        $this->paypan->setVisible(false);
        $this->docview->setDoc($this->_doc);
        $this->goAnkor('dankor');
    }

    public function onBack($sender) {
        $this->clist->setVisible(true);
        $this->plist->setVisible(false);
        $this->docview->setVisible(false);
        $this->paypan->setVisible(false);
        $this->updateCust();
    }

    //оплаты
    public function payOnClick($sender) {
        $this->docview->setVisible(false);


        $this->_doc = $sender->owner->getDataItem();


        $this->paypan->setVisible(true);


        $this->plist->doclist->setSelectedRow($sender->getOwner());
        $this->plist->doclist->Reload(false);

        $this->goAnkor('dankor');

        $this->paypan->payform->pamount->setText($this->_doc->payamount - $this->_doc->payed);;
        $this->paypan->payform->pcomment->setText("");;
        $this->paypan->pname->setText($this->_doc->document_number);;

        $this->_pays = \App\Entity\Pay::getPayments($this->_doc->document_id);
        $this->paypan->paylist->Reload();
    }

    public function payOnRow($row) {
        $pay = $row->getDataItem();
        $row->add(new Label('plamount', H::fa($pay->amount)));
        $row->add(new Label('pluser', $pay->username));
        $row->add(new Label('pldate', date('Y-m-d', $pay->paydate)));
        $row->add(new Label('plmft', $pay->mf_name));
        $row->add(new Label('plcomment', $pay->notes));
    }

    public function payOnSubmit($sender) {
        $form = $this->paypan->payform;
        $amount = $form->pamount->getText();
        $pdate = $form->pdate->getDate();
        if ($amount == 0) {
            return;
        }


        if ($amount > $this->_doc->payamount - $this->_doc->payed) {

            $this->setError('sumoverpay');
            return;
        }

        $type = \App\Entity\Pay::PAY_BASE_INCOME;
        //закупки  и возвраты
        if ($this->_doc->meta_name == 'GoodsReceipt' || $this->_doc->meta_name == 'InvoiceCust' || $this->_doc->meta_name == 'ReturnIssue') {
            $amount = 0 - $amount;
            $type = Pay::PAY_BASE_OUTCOME;
        }

        Pay::addPayment($this->_doc->document_id, $pdate, $amount, $form->payment->getValue(), $type, $form->pcomment->getText());


        $this->setSuccess('payment_added');


        $this->updateDocs();
        $this->paypan->setVisible(false);
    }

    public function oncsv($sender) {
        $csv = "";
        if ($sender->id == 'csv') {
            $list = $this->clist->custlist->getDataSource()->getItems(-1, -1, 'customer_name');


            foreach ($list as $c) {

                $csv .= $c->customer_name . ';';
                $csv .= $c->phone . ';';

                $csv .= H::fa($c->fl == -1 ? $c->sam : "") . ';';
                $csv .= H::fa($c->fl == 1 ? $c->sam : "") . ';';

                $csv .= "\n";
            }
        }
        if ($sender->id == 'csv2') {
            $list = $this->plist->doclist->getDataSource()->getItems(-1, -1, 'document_id');


            foreach ($list as $d) {
                $csv .= date('Y.m.d', $d->document_date) . ';';
                $csv .= $d->document_number . ';';

                $csv .= H::fa($d->amount) . ';';
                $csv .= str_replace(';', '', $d->notes) . ';';
                $csv .= "\n";
            }
        }

        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=baylist.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}
