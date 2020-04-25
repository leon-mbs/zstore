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
        $this->filter->add(new DropDownChoice('status', array(0 => 'Открытые', 1 => 'Новые', 2 => 'Отправленые', 4 => 'Неоплаченные', 3 => 'Все'), 0));


        $doclist = $this->add(new DataView('doclist', new GoodsIssueDataSource($this), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());




        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        $this->statuspan->statusform->add(new SubmitButton('bsend'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bdevivered'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bttn'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bgar'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bret'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new TextInput('ship_number'));




        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function filterOnSubmit($sender) {


        $this->statuspan->setVisible(false);

        $this->doclist->Reload(false);
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa($doc->amount)));
        $row->add(new Label('order', $doc->headerdata['order']));
        $row->add(new Label('customer', $doc->customer_name));

        $row->add(new Label('state', Document::getStateName($doc->state)));

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state < Document::STATE_EXECUTED) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
    }

    public function statusOnSubmit($sender) {
        if (\App\Acl::checkExeDoc($this->_doc, true, true) == false)
            return;

        $state = $this->_doc->state;

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
           // if ($order instanceof Document) {
           //     $order = $order->cast();
           //     $order->updateStatus(Document::STATE_INSHIPMENT);
           // }
            $this->statuspan->statusform->ship_number->setText('');
            $this->setSuccess('sent');
        }

        if ($sender->id == "bdevivered") {
            $this->_doc->updateStatus(Document::STATE_DELIVERED);

            if ($this->_doc->parent_id > 0) {   //закрываем заказ
                if ($this->_doc->payamount > 0 && $this->_doc->payamount > $this->_doc->payed) {
                    
                } else {
                    $order = Document::load($this->_doc->parent_id);
                    if ($order->state == Document::STATE_INPROCESS) {
                        $order->updateStatus(Document::STATE_CLOSED);
                        $this->setSuccess("order_closed ",$order->document_number );
                    }
                }
            }


            $this->_doc->updateStatus(Document::STATE_CLOSED);
        }

        if ($sender->id == "bttn") {
            $d = $this->_doc->getChildren('GoodsReceipt');

            if (count($d) > 0) {
                $this->setWarn('goodsreceipt_exists');
                 
            }
            App::Redirect("\\App\\Pages\\Doc\\GoodsReceipt", 0, $this->_doc->document_id);
        }
        if ($sender->id == "bgar") {
            App::Redirect("\\App\\Pages\\Doc\\Warranty", 0, $this->_doc->document_id);
        }
        if ($sender->id == "bret") {
            App::Redirect("\\App\\Pages\\Doc\\ReturnIssue", 0, $this->_doc->document_id);
        }




        $this->doclist->Reload(false);

        $this->statuspan->setVisible(false);
        //todo  отослать писмо 

        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {



        $this->statuspan->statusform->bdevivered->setVisible(true);
        $this->statuspan->statusform->bttn->setVisible(true);
        $this->statuspan->statusform->bret->setVisible(true);
        $this->statuspan->statusform->bsend->setVisible(true);
        $this->statuspan->statusform->bgar->setVisible(true);
        $this->statuspan->statusform->ship_number->setVisible(true);

        $state = $this->_doc->state;



        //отправлен
        if ($state == Document::STATE_INSHIPMENT) {


            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
        }
        // Доставлен
        if ($state == Document::STATE_DELIVERED) {

            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
        }

        //прячем лишнее
        if ($this->_doc->meta_name == 'GoodsIssue') {

            $this->statuspan->statusform->bttn->setVisible(false);
        }
        if ($this->_doc->meta_name == 'POSCheck') {
            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
        }
        if ($this->_doc->meta_name == 'Invoice') {

            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->bret->setVisible(false);
            $this->statuspan->statusform->bgar->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
        }
        if ($this->_doc->meta_name == 'ReturnIssue') {


            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bret->setVisible(false);
            $this->statuspan->statusform->bgar->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
        }
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true))
            return;

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

        App::Redirect("\\App\\Pages\\Doc\\" . $doc->meta_name, $doc->document_id);
    }

    //оплаты

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $d) {
            $csv .= date('Y.m.d', $d->document_date) . ';';
            $csv .= $d->document_number . ';';
            $csv .= $d->headerdata['order'] . ';';
            $csv .= $d->customer_name . ';';
            $csv .= $d->amount . ';';
            $csv .= str_replace(';', '', $d->notes) . ';';
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

        $where .= " and meta_name  in('GoodsIssue', 'Invoice','POSCheck','ReturnIssue' ,'Warranty' ) ";

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


        $st = trim($this->page->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and    content like {$st} ";
        }
        $sn = trim($this->page->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " meta_name  in('GoodsIssue', 'Invoice','POSCheck','ReturnIssue' )  and document_number like  {$sn} ";
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
