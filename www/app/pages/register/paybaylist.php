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
 * журнал расчет с покупателями
 */
class PayBayList extends \App\Pages\Base
{

    private $_doc       = null;
    private $_cust      = null;
    public  $_custlist  = array();
    public  $_doclist   = array();
    public  $_pays      = array();
    public  $_totamount = 0;
    private $_docs      = " and ( meta_name in('GoodsIssue','Invoice' ,'PosCheck','ServiceAct','Order','ReturnIssue')  or  (meta_name='IncomeMoney'  and content like '%<detail>1</detail>%'  )  or  (meta_name='OutcomeMoney'  and content like '%<detail>2</detail>%'  ))  ";
    private $_state     = "1,2,3,17,8";

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('PayBayList')) {
            return;
        }
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('holdlist', \App\Entity\Customer::getHoldList(), 0));

        $this->add(new Panel("clist"));

        $this->clist->add(new Label("totamount"));

        $this->clist->add(new DataView('custlist', new ArrayDataSource($this, '_custlist'), $this, 'custlistOnRow'));

        $this->add(new Panel("plist"))->setVisible(false);
        $this->plist->add(new Label("cname"));
        $this->plist->add(new ClickLink("back", $this, "onBack"));

        $doclist = $this->plist->add(new DataView('doclist', new ArrayDataSource($this, '_doclist'), $this, 'doclistOnRow'));

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        $this->add(new Panel("paypan"))->setVisible(false);
        $this->paypan->add(new Label("pname"));
        $this->paypan->add(new Form('payform'))->onSubmit($this, 'payOnSubmit');
        $this->paypan->payform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(), H::getDefMF()));
        $this->paypan->payform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', "details like '%<usefisc>1</usefisc>%' "), 0));
        $this->paypan->payform->add(new TextInput('pamount'));
        $this->paypan->payform->add(new TextInput('pcomment'));
        $this->paypan->payform->add(new Date('pdate', time()));

        $this->paypan->add(new DataView('paylist', new ArrayDataSource($this, '_pays'), $this, 'payOnRow'))->Reload();

        $this->clist->add(new ClickLink('csv', $this, 'oncsv'));
        $this->plist->add(new ClickLink('csv2', $this, 'oncsv'));

        $this->updateCust();
    }

    public function filterOnSubmit($sender) {


        $this->plist->setVisible(false);
        $this->updateCust();
    }

    public function updateCust() {
        $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }
        $hold = "";
        $holding = $this->filter->holdlist->getValue();
        if ($holding > 0) {
            $hold = " where  c.detail like '%<holding>{$holding}</holding>%'";
        }

        $sql = "select c.customer_name,c.phone, c.customer_id,coalesce(sum(sam),0) as sam  from (
        select customer_id,  (case when  ( meta_name='OutcomeMoney' or meta_name='ReturnIssue' ) then  (payed - payamount )   else  (payamount - payed)  end) as sam 
            from `documents_view`  
            where {$br}     (payamount >0  or  payed >0) {$this->_docs}  and state not in ({$this->_state})   and  ( (meta_name <>'POSCheck' and payamount <> payed) or(meta_name = 'POSCheck' and payamount > payed  ))
            ) t join customers c  on t.customer_id = c.customer_id  and c.status=0   {$hold}
             group by c.customer_name,c.phone, c.customer_id 
             having sam <> 0 
             order by c.customer_name ";

        $this->_custlist = \App\DataItem::query($sql);

        $this->_totamount = 0;

        $this->clist->custlist->Reload();
        $this->clist->totamount->setText(H::fa($this->_totamount));
    }

    public function custlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $cust = $row->getDataItem();
        
        $row->add(new RedirectLink('customer_name', "\\App\\Pages\\Reference\\CustomerList", array($cust->customer_id)))->setValue($cust->customer_name);
        $row->add(new Label('phone', $cust->phone));
        $row->add(new Label('amount', H::fa($cust->sam)));

        $row->add(new RedirectLink('createpay'))->setVisible(false);
        if ($cust->sam > 0) {
            $row->createpay->setLink("\\App\\Pages\\Doc\\IncomeMoney", array(0, $cust->customer_id, $cust->sam));
            $row->createpay->setVisible(true);
        }
        $row->add(new ClickLink('showdocs'))->onClick($this, 'showdocsOnClick');

        $this->_totamount += $cust->sam;
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

        $docs = " and meta_name in({$this->_docs})  ";

        $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }

        $this->_doclist = array();

        $list = \App\Entity\Doc\Document::find(" {$br} customer_id= {$this->_cust->customer_id} and (payamount >0  or  payed >0) and  ( (meta_name <>'POSCheck' and payamount <> payed) or(meta_name = 'POSCheck' and payamount > payed  ))  and state not in ({$this->_state})   {$this->_docs} ", "document_date desc, document_id desc");
        $sum = 0;

        foreach ($list as $d) {
            $this->_doclist[] = $d;
            $sum += ($d->payamount - $d->payed);
            if ($this->_cust->sam == $sum) {
                break;
            }
        }
        $this->_doclist = array_reverse($this->_doclist);

        $this->plist->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('name', $doc->meta_desc));
        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('date', H::fd($doc->document_date)));

        $row->add(new Label('payamount', H::fa(($doc->payamount > 0) ? $doc->payamount : "")));
        $row->add(new Label('payed', H::fa(($doc->payed > 0) ? $doc->payed : "")));
        if ($doc->meta_name == 'OutcomeMoney') {
            $row->payamount->setText(H::fa(($doc->payed > 0) ? $doc->payed : ""));
            $row->payed->setText(H::fa(($doc->payamount > 0) ? $doc->payamount : ""));
        }

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('pay'))->onClick($this, 'payOnClick');
        $row->pay->setVisible($doc->payamount > 0);
        if ($doc->document_id == $this->_doc->document_id) {
            $row->setAttribute('class', 'table-success');
        }
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

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
        $amount = $this->_doc->payamount - $this->_doc->payed;
        if ($amount > $this->_cust->sam) {
            $amount = $this->_cust->sam;
        }

        $this->paypan->payform->pamount->setText(H::fa($this->_doc->payamount - $this->_doc->payed));
        $this->paypan->payform->pcomment->setText("");;
        $this->paypan->pname->setText($this->_doc->document_number);;

        $this->_pays = \App\Entity\Pay::getPayments($this->_doc->document_id);
        $this->paypan->paylist->Reload();
    }

    public function payOnRow($row) {
        $pay = $row->getDataItem();
        $row->add(new Label('plamount', H::fa($pay->amount)));
        $row->add(new Label('pluser', $pay->username));
        $row->add(new Label('pldate', H::fdt($pay->paydate)));
        $row->add(new Label('plmft', $pay->mf_name));
        $row->add(new Label('plcomment', $pay->notes));
    }

    public function payOnSubmit($sender) {
        $form = $this->paypan->payform;
        $pos_id = $form->pos->getValue();
        $amount = $form->pamount->getText();
        $pdate = $form->pdate->getDate();
        if ($amount == 0) {
            return;
        }


        if ($amount > H::fa($this->_doc->payamount - $this->_doc->payed)) {

            $this->setWarn('sumoverpay');
        }
        $type = \App\Entity\IOState::TYPE_BASE_INCOME;

        if (in_array($this->_doc->meta_name, array('GoodsReceipt', 'InvoiceCust', 'ReturnIssue'))) {
            $amount = 0 - $amount;
            $type = \App\Entity\IOState::TYPE_BASE_OUTCOME;
        }


        if ($pos_id > 0) {
            $pos = \App\Entity\Pos::load($pos_id);
            if ($pos->usefisc == 1 && $this->_tvars['ppo'] == true) {

                $ret = \App\Modules\PPO\PPOHelper::checkpay($this->_doc, $pos_id, $amount, $form->payment->getValue());
                if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                    //повторяем для  нового номера
                    $pos->fiscdocnumber = $ret['doclocnumber'];
                    $pos->save();
                    $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
                }
                if ($ret['success'] == false) {
                    $this->setErrorTopPage($ret['data']);
                    return;
                } else {

                    if ($ret['docnumber'] > 0) {
                        $pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                        $pos->save();
                        $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                    } else {
                        $this->setError("ppo_noretnumber");
                        return;
                    }
                }
           }
        }


        Pay::addPayment($this->_doc->document_id, $pdate, $amount, $form->payment->getValue(), $type, $form->pcomment->getText());

        $this->setSuccess('payment_added');

        //$this->updateDocs();
        $this->paypan->setVisible(false);
        $this->onBack(null);
    }

    public function oncsv($sender) {
        $csv = "";

        $header = array();
        $data = array();

        $i = 0;

        if ($sender->id == 'csv') {
            $list = $this->clist->custlist->getDataSource()->getItems(-1, -1, 'customer_name');

            foreach ($list as $c) {
                $i++;
                $data['A' . $i] = $c->customer_name;
                $data['B' . $i] = $c->phone;
                $data['C' . $i] = H::fa($c->sam);
            }
        }
        if ($sender->id == 'csv2') {
            $list = $this->plist->doclist->getDataSource()->getItems(-1, -1, 'document_id');

            foreach ($list as $d) {
                $i++;
                $data['A' . $i] = H::fd($d->document_date);
                $data['B' . $i] = $d->document_number;
                $data['C' . $i] = H::fa($d->amount);
                $data['D' . $i] = $d->notes;
            }
        }

        H::exportExcel($data, $header, 'baylist.xlsx');
    }

}
