<?php

namespace App\Pages\Register;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\Paginator;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \App\Entity\Doc\Document;
use \App\Helper as H;
use \App\Application as App;
use \App\System;

/**
 * журнал  продаж
 */
class GIList extends \App\Pages\Base {

    private $_doc = null;
    public $_pays = array();

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('GIList'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time() + (1 * 24 * 3600)));

        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => 'Открытые', 1 => 'Новые', 2 => 'Отправленые', 4 => 'Неоплаченные', 5 => 'На выполнении', 3 => 'Все'), 0));


        $doclist = $this->add(new DataView('doclist', new GoodsIssueDataSource($this), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(25);




        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        $this->statuspan->statusform->add(new SubmitButton('bsend'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bclose'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bcloseact'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('inprocact'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new TextInput('ship_number'));




        $this->statuspan->add(new \App\Widgets\DocView('docview'));
        $this->add(new Panel("paypan"))->setVisible(false);
        $this->paypan->add(new Label("pname"));
        $this->paypan->add(new Form('payform'))->onSubmit($this, 'payOnSubmit');

        $this->paypan->payform->add(new TextInput('pamount'));
        $this->paypan->payform->add(new TextInput('pcomment'));
        $this->paypan->payform->add(new SubmitButton('bpay'))->onClick($this, 'payOnSubmit');

        $this->paypan->add(new DataView('paylist', new ArrayDataSource(new Prop($this, '_pays')), $this, 'payOnRow'))->Reload();

        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function filterOnSubmit($sender) {

        $this->paypan->setVisible(false);
        $this->statuspan->setVisible(false);

        $this->doclist->Reload(false);
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', $doc->amount));
        $row->add(new Label('order', $doc->headerdata['order']));
        $row->add(new Label('customer', $doc->customer_name));
        $row->add(new Label('spay', $doc->amount - $doc->payamount));

        $row->add(new Label('state', Document::getStateName($doc->state)));

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state == Document::STATE_CANCELED || $doc->state == Document::STATE_EDITED || $doc->state == Document::STATE_NEW) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }

        $row->add(new ClickLink('pay'))->onClick($this, 'payOnClick');
    }

    public function statusOnSubmit($sender) {

        $state = $this->_doc->state;
        $order = Document::load($this->_doc->headerdata['order_id']);


        if ($sender->id == "bsend") {
            $dec = $this->statuspan->statusform->ship_number->getText();
            $this->_doc->headerdata['sentdate'] = date('Y-m-d', time());
            if (strlen($dec) > 0)
                $this->_doc->headerdata['ship_number'] = $dec;
            $this->_doc->headerdata['document_date'] = time();
            $this->_doc->save();

            $this->_doc->updateStatus(Document::STATE_EXECUTED);
            $this->_doc->updateStatus(Document::STATE_INSHIPMENT);


            $this->_doc->save();
            if ($order instanceof Document) {
                $order = $order->cast();
                $order->updateStatus(Document::STATE_INSHIPMENT);
            }
            $this->statuspan->statusform->ship_number->setText('');
            $this->setSuccess('Отправлено');
        }

        if ($sender->id == "bclose") {
            $this->_doc->updateStatus(Document::STATE_DELIVERED);
            $msg = 'Отправка  доставлена.';

            if ($order instanceof Document) {
                $order = $order->cast();
                if ($order->state != Document::STATE_CLOSED && $this->_doc->amount == $this->_doc->payamount) { //если  все  доставлено и оплачено закрываем  заказ
                    $order->updateStatus(Document::STATE_CLOSED);
                    $msg .= " Заказ {$order->document_number} закрыт";
                }
            }

            $this->setSuccess($msg);


            //$this->_doc->save();

            $this->_doc->updateStatus(Document::STATE_CLOSED);
        }


        if ($sender->id == "inprocact") {
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
        }
        if ($sender->id == "bcloseact") {
            $this->_doc->updateStatus(Document::STATE_EXECUTED);
            $this->_doc->updateStatus(Document::STATE_CLOSED);
            if ($order instanceof Document) {
                $order = $order->cast();
                if ($order->state != Document::STATE_CLOSED && $this->_doc->amount == $this->_doc->payamount) { //если  все   выполнено и оплачено закрываем  заказ
                    $order->updateStatus(Document::STATE_CLOSED);
                    $msg .= " Заказ {$order->document_number} закрыт";
                }
            }
        }



        $this->doclist->Reload(false);

        $this->statuspan->setVisible(false);
        //todo  отослать писмо 

        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {

        $this->statuspan->statusform->bclose->setVisible(true);

        $state = $this->_doc->state;


        //новый     
        if ($state == Document::STATE_CANCELED || $state == Document::STATE_EDITED || $state == Document::STATE_NEW) {
            $this->statuspan->statusform->bsend->setVisible(true);
            $this->statuspan->statusform->ship_number->setVisible(true);
            $this->statuspan->statusform->inprocact->setVisible(true);

            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->bcloseact->setVisible(false);
        } else {
            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
            $this->statuspan->statusform->bclose->setVisible(true);
            $this->statuspan->statusform->bcloseact->setVisible(true);
            $this->statuspan->statusform->inprocact->setVisible(true);
        }
        //отправлен
        if ($state == Document::STATE_INSHIPMENT) {

            $this->statuspan->statusform->bclose->setVisible(true);
            $this->statuspan->statusform->bsend->setVisible(false);
        }
        // в работе
        if ($state == Document::STATE_INPROCESS) {

            $this->statuspan->statusform->bcloseact->setVisible(true);
            $this->statuspan->statusform->inprocact->setVisible(false);
        }

        //закрыт
        if ($state == Document::STATE_CLOSED) {
            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->setVisible(false);
        }



        //прячем лишнее
        if ($this->_doc->meta_name == 'GoodsIssue') {

            $this->statuspan->statusform->bcloseact->setVisible(false);
            $this->statuspan->statusform->inprocact->setVisible(false);
        }
        if ($this->_doc->meta_name == 'ServiceAct') {

            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
        }
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true))
            return;
        $this->paypan->setVisible(false);
        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true))
            return;


        App::Redirect("\\App\\Pages\\Doc\\GoodsIssue", $doc->document_id);
    }

    //оплаты
    public function payOnClick($sender) {
        $this->statuspan->setVisible(false);


        $this->_doc = $sender->owner->getDataItem();


        $this->paypan->setVisible(true);

        $this->statuspan->setVisible(false);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(true);

        $this->goAnkor('dankor');

        $this->paypan->payform->pamount->setText($this->_doc->amount - $this->_doc->payamount);
        ;
        $this->paypan->payform->pcomment->setText("");
        ;
        $this->paypan->pname->setText($this->_doc->document_number);
        ;

        $this->_pays = $this->_doc->getPayments();
        $this->paypan->paylist->Reload();
    }

    public function payOnRow($row) {
        $pay = $row->getDataItem();
        $row->add(new Label('plamount', $pay->amount));
        $row->add(new Label('pluser', $pay->user));
        $row->add(new Label('pldate', date('Y-m-d', $pay->date)));
        $row->add(new Label('plcomment', $pay->comment));
    }

    public function payOnSubmit($sender) {
        $form = $this->paypan->payform;
        $amount = $form->pamount->getText();
        if ($amount == 0)
            return;
        $amount = $form->pamount->getText();
        if ($amount == 0)
            return;

        $this->_doc->addPayment(System::getUser()->getUserName(), $amount, $form->pcomment->getText());
        $this->_doc->payamount += $amount;
        if ($this->_doc->payamount > $this->_doc->amount) {
            $this->setWarn('Сумма  больше  необходимой  оплаты');
        }

        $this->_doc->save();

        $this->setSuccess('Оплата добавлена');

        $this->doclist->Reload(false);

        $this->paypan->setVisible(false);
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $d) {
            $csv .= date('Y.m.d', $d->document_date) . ';';
            $csv .= $d->document_number . ';';
            $csv .= $d->headerdata['order'] . ';';
            $csv .= $d->customer_name . ';';
            $csv .= $d->amount . ';';
            $csv .= $d->notes . ';';
            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=selllist.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class GoodsIssueDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = " date(document_date) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(document_date) <= " . $conn->DBDate($this->page->filter->to->getDate());

        $where .= " and (meta_name  = 'GoodsIssue' or meta_name  = 'ServiceAct' ) ";

        $status = $this->page->filter->status->getValue();
        if ($status == 0) {
            $where .= " and  state <>   " . Document::STATE_CLOSED;
        }
        if ($status == 1) {
            $where .= " and  state =  " . Document::STATE_NEW;
        }
        if ($status == 2) {
            $where .= " and state = " . Document::STATE_INSHIPMENT;
        }
        if ($status == 4) {
            $where .= " and  amount > payamount";
        }
        if ($status == 5) {
            $where .= " and state = " . Document::STATE_INPROCESS;
        }
        if ($status == 3) {
            
        }

        $st = trim($this->page->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and meta_name  = 'GoodsIssue' and  content like {$st} ";
        }
        $sn = trim($this->page->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " meta_name  = 'GoodsIssue' and document_number like  {$sn} ";
        }
        if ($user->acltype == 2) {


            $where .= " and meta_id in({$user->aclview}) ";
        }
        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "document_date desc,document_id desc", $count, $start);

        //$l = Traversable::from($docs);
        //$l = $l->where(function ($doc) {return $doc->document_id == 169; }) ;
        //$l = $l->select(function ($doc) { return $doc; })->asArray() ;
        return $docs;
    }

    public function getItem($id) {
        
    }

}
