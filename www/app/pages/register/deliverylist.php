<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * журнал  доставок
 */
class DeliveryList extends \App\Pages\Base
{

    private $_doc     = null;
    public  $_doclist = array();

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('DeliveryList')) {
            return;
        }

        $this->add(new DataView('orderlist', new ArrayDataSource($this, '_doclist'), $this, 'onDocRow'));

        $this->add(new Form('searchform'))->onSubmit($this, 'updateorderlist');
        $this->searchform->add(new TextInput('searchnumber', $filter->searchnumber));

        //панель статуса,  просмотр
        $this->add(new Panel('statuspan'))->setVisible(false);

        $sf = $this->statuspan->add(new Form('statusform'));
        $sf->add(new  DropDownChoice('emp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name"), 0));
        $sf->add(new  SubmitButton('bsend'))->onClick($this, 'onStatus');
        $sf->add(new  SubmitButton('bdelivered'))->onClick($this, 'onStatus');

        $pf = $this->statuspan->add(new Form('payform'));
        $pf->add(new  SubmitButton('bpay'))->onClick($this, 'onStatus');
        $pf->add(new  TextInput('payamount'));
        $pf->add(new  DropDownChoice('mf', \App\Entity\MoneyFund::getList(), 0));

        $this->statuspan->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        $this->updateorderlist(null);

    }

    public function onDocRow($row) {
        $doc = $row->getDataItem();
        $row->add(new ClickLink('docnumber', $this, 'OnDocViewClick'))->setValue($doc->document_number);
        $row->add(new Label('state', Document::getStateName($doc->state)));
        $row->add(new Label('docdate', H::fd($doc->document_date)));
        $row->add(new Label('docamount', H::fa($doc->amount)));
        $row->add(new Label('deltime', date("Y-m-d H:i", $doc->headerdata['deltime'])));
        $row->add(new Label('address', $doc->headerdata['ship_address']));
        $row->add(new Label('emp_name', $doc->headerdata['emp_name']));
        $row->add(new Label('contact', $doc->headerdata['contact']));
        $row->add(new Label('docnotes', $doc->notes));
        $row->add(new Label('wp'))->setVisible($doc->payamount > $doc->payed);

        if ($doc->document_id == @$this->_doc->document_id) {
            $row->setAttribute('class', 'table-success');
        }

    }

    public function OnDocViewClick($sender) {
        $this->_doc = $sender->getOwner()->getDataItem();
        $this->OnDocView();

    }

    public function OnDocView() {
        $this->statuspan->setVisible(true);
        $this->statuspan->statusform->setVisible(true);

        $sf = $this->statuspan->statusform;
        $pf = $this->statuspan->payform;

        $pf->setVisible(false);
        $sf->bsend->setVisible(false);
        $sf->emp->setVisible(false);
        $sf->bdelivered->setVisible(false);

        if ($this->_doc->state == Document::STATE_READYTOSHIP) { //готов  к  отправке
            $sf->bsend->setVisible(true);
        }
        if ($this->_doc->state == Document::STATE_INSHIPMENT) { //в  пути
            $sf->bdelivered->setVisible(true);

        }
        if ($this->_doc->state == Document::STATE_DELIVERED) { //доставлен
            $sf->setVisible(false);

        }

        if ($this->_doc->payamount > $this->_doc->payed) { //к  оплате
            $pf->setVisible(true);
            $pf->payamount->setText(H::fa($this->_doc->payamount - $this->_doc->payed));
            $pf->mf->setValue(0);
        }


        $this->statuspan->docview->setDoc($this->_doc);
        $this->orderlist->Reload(false);

        $this->goAnkor('dankor');
    }

    public function onStatus($sender) {


        if ($sender->id == 'bpay') {

            $am = doubleval($this->statuspan->payform->payamount->getText());
            $mf = intval($this->statuspan->payform->mf->getValue());

            if ($am > 0 && $mf > 0) {
                // $amount = $this->_doc->payamount ;
                $this->_doc->payed = $am;
                $this->_doc->headerdata['payment'] = $mf;
                $this->_doc->headerdata['payed'] = $am;
                $this->_doc->headerdata['exchange'] = 0;
                if ($am > $this->_doc->payamount) {
                    $this->_doc->headerdata['exchange'] = $am - $this->_doc->payamount;
                }
                $this->_doc->save();
                $this->_doc = $this->_doc->cast();
                $this->_doc->DoPayment();

                if ($this->_doc->payamount == $this->_doc->payed && $this->_doc->state == Document::STATE_DELIVERED) {     //оплачен
                    $this->_doc->updateStatus(Document::STATE_CLOSED);
                }
                $this->statuspan->setVisible(false);
            }


        }
        if ($sender->id == 'bsend') {
            $this->_doc->headerdata['emp'] = $this->statuspan->statusform->emp->getValue();
            if ($this->_doc->headerdata['emp'] > 0) {
                $this->_doc->headerdata['emp_name'] = $this->statuspan->statusform->emp->getValueName();
            }
            $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
        }
        if ($sender->id == 'bdelivered') {

            $this->_doc->updateStatus(Document::STATE_DELIVERED);
            if ($this->_doc->payamount == $this->_doc->payed) {     //оплачен
                $this->_doc->updateStatus(Document::STATE_CLOSED);
            }

        }


        $this->updateorderlist(null);

    }

    public function updateorderlist($sender) {
        $where = " state   in(11,14,20) ";
        if ($sender instanceof Form) {
            $text = trim($sender->searchnumber->getText());

            if (strlen($text) > 0) {

                $where = "   document_number=" . Document::qstr($text);
            }


        }
        $where .= " and meta_name='OrderFood'   ";


        $this->_doclist = Document::find($where, 'priority desc, document_id desc');
        $this->orderlist->Reload();
        $this->statuspan->setVisible(false);


    }

    public function getMessages($args, $post) {

        $cnt = 0;
        $mlist = \App\Entity\Notify::find("checked <> 1 and user_id=" . \App\Entity\Notify::DELIV);
        foreach ($mlist as $n) {
            $msg = @unserialize($n->message);
            $doc = Document::load(intval($msg['document_id']));
            if ($doc->state == Document::STATE_READYTOSHIP) {
                $cnt++;
            }
        }

        \App\Entity\Notify::markRead(\App\Entity\Notify::DELIV);

        return json_encode(array("cnt" => $cnt), JSON_UNESCAPED_UNICODE);
    }
}