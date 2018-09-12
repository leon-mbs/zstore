<?php

namespace App\Pages\Register;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Binding;
use \Zippy\Html\Panel;
use \App\Entity\Customer;
use \App\Entity\Doc\Document;
use \App\DataItem;

class PayList extends \App\Pages\Base
{

    public $_slist = array();
    public $_clist = array();
    private $_doc;

    public function __construct() {
        parent::__construct();

       if(false ==\App\ACL::checkShowReg('PayList'))return;       

        $this->add(new Panel('custpanel'));
        $this->custpanel->add(new DataView('sdoclist', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_slist')), $this, 'slistOnRow'));
        $this->custpanel->add(new DataView('cdoclist', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_clist')), $this, 'clistOnRow'));

        $this->add(new Panel('docpanel'))->setVisible(false);
        $this->add(new Panel('paypanel'))->setVisible(false);

        $this->docpanel->add(new \App\Widgets\DocView('docview'));

        $this->paypanel->add(new Label('pdocdesc'));
        $this->paypanel->add(new Form('payform'))->onSubmit($this, 'onSubmit');
        $this->paypanel->payform->add(new TextInput('summa'));


        $this->update();
    }

    public function slistOnRow($row) {
        $doc = $row->getDataItem();
        $row->add(new Label('sname', $doc->headerdata['customer_name']));
        $row->add(new ClickLink('sdoc', $this, 'onDoc'))->setValue($doc->document_number . ' от ' . date('Y-m-d', $doc->document_date));
        $row->add(new ClickLink('samount', $this, 'onPay'))->setValue($doc->amount - $doc->datatag);
    }

    public function clistOnRow($row) {
        $doc = $row->getDataItem();
        $row->add(new Label('cname', $doc->headerdata['customer_name']));
        $row->add(new ClickLink('cdoc', $this, 'onDoc'))->setValue($doc->document_number . ' от ' . date('Y-m-d', $doc->document_date));
        $row->add(new ClickLink('camount', $this, 'onPay'))->setValue($doc->amount - $doc->datatag);
    }

    private function update() {
        $this->_slist = Document::find("amount <> datatag and meta_name in('GoodsReceipt') and state not in(1,2,3)");
        $this->custpanel->sdoclist->Reload();
        $this->_clist = Document::find("amount <> datatag and meta_name in('GoodsIssue','serviceAct') and state not in(1,2,3)");
        $this->custpanel->cdoclist->Reload();
    }

    public function onDoc($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $this->docpanel->setVisible(true);
        $this->paypanel->setVisible(false);
        $this->docpanel->docview->setDoc($doc);
        $this->goAnkor('docpanel');
    }

    public function onPay($sender) {
        $this->_doc = $sender->getOwner()->getDataItem();
        $this->paypanel->pdocdesc->setText($this->_doc->meta_desc . ' №' . $this->_doc->document_number);
        $this->paypanel->payform->summa->setText($this->_doc->amount - $this->_doc->datatag);

        $this->docpanel->setVisible(false);
        $this->paypanel->setVisible(true);
        $this->goAnkor('paypanel');
    }

    public function onSubmit($sender) {

        $sw = $this->_doc->amount - $this->_doc->datatag;
        $summa = $sender->summa->getText();
        if ($summa <= 0 || $summa > $sw) {
            $this->setError('Неверное значение');
            return;
        }
        $this->_doc->datatag = $this->_doc->datatag + $summa;
        if ($this->_doc->amount == $this->_doc->datatag) {
            $this->_doc->headerdata['incredit'] = false;
            $this->_doc->updateStatus(Document::STATE_PAYED);
        }
        $this->_doc->save();



        $this->docpanel->setVisible(false);
        $this->paypanel->setVisible(false);
        $this->update();
        $this->setSuccess('Оплата добавлена');
    }

}
