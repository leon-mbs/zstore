<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
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
 * журнал  заявок
 */
class OrderCustList extends \App\Pages\Base
{

    private $_doc = null;

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('OrderCustList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time() + (1 * 24 * 3600)));

        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => 'Открытые', 3 => 'Все'), 0));


        $doclist = $this->add(new DataView('doclist', new OrderCustDataSource($this), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());


        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));


        $this->statuspan->statusform->add(new SubmitButton('bclose'))->onClick($this, 'statusOnSubmit');


        $this->statuspan->statusform->add(new SubmitButton('bttn'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('binp'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('binv'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bcan'))->onClick($this, 'statusOnSubmit');

        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function filterOnSubmit($sender) {


        $this->statuspan->setVisible(false);

        $this->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('customer', $doc->customer_name));
        $row->add(new Label('amount', H::fa($doc->amount)));

        $row->add(new Label('state', Document::getStateName($doc->state)));
        // if($doc->state == Document::STATE_EXECUTED) $row->state->setText('Выполняется');

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

        if ($doc->state < Document::STATE_EXECUTED) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
    }

    public function statusOnSubmit($sender) {
        if (\App\Acl::checkExeDoc($this->_doc, true, true) == false) {
            return;
        }

        $state = $this->_doc->state;
        $payed = $this->_doc->payamount >= $this->_doc->amount; //оплачен
        //проверяем  что есть ТТН
        $d = $this->_doc->getChildren('GoodsReceipt');
        $ttn = count($d) > 0;
        if ($sender->id == "bttn") {

            if ($ttn) {
                $this->setWarn('goodsreceipt_exists');
            }
            App::Redirect("\\App\\Pages\\Doc\\GoodsReceipt", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "binv") {

            if ($ttn) {
                $this->setWarn('goodsreceipt_exists');
            }
            $d = $this->_doc->getChildren('InvoiceCust');
            if (count($d) > 0) {

                $this->setWarn('invoice_exists');
            }

            App::Redirect("\\App\\Pages\\Doc\\InvoiceCust", 0, $this->_doc->document_id);
            return;
        }


        if ($sender->id == "binp") {
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
        }
        if ($sender->id == "bcan") {
            $this->_doc->updateStatus(Document::STATE_CANCELED);
        }
        if ($sender->id == "bclose") {

            $this->_doc->payamount = $this->_doc->amount;
            $this->_doc->save();

            $this->_doc->updateStatus(Document::STATE_CLOSED);
            $this->statuspan->setVisible(false);
        }

        $this->doclist->Reload(false);
        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {

        $this->statuspan->statusform->bclose->setVisible(true);

        $state = $this->_doc->state;

        $payed = $this->_doc->payamount >= $this->_doc->amount; //оплачен
        //доставлен
        $sent = $this->_doc->checkStates(array(Document::STATE_DELIVERED));

        //проверяем  что есть ТТН
        $d = $this->_doc->getChildren('GoodsReceipt');
        $ttn = count($d) > 0;

        $this->statuspan->statusform->binp->setVisible(false);

        //новый     
        if ($state < Document::STATE_EXECUTED) {
            $this->statuspan->statusform->bclose->setVisible(false);

            $this->statuspan->statusform->binp->setVisible(true);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->binv->setVisible(false);
            $this->statuspan->statusform->bcan->setVisible(false);
        } else {
            $this->statuspan->statusform->bclose->setVisible(true);
            $this->statuspan->statusform->bcan->setVisible(true);
        }

        if ($state == Document::STATE_WA) {
            $this->statuspan->statusform->binv->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->binp->setVisible(false);
            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->bcan->setVisible(false);
        }
        if ($state == Document::STATE_APPROVED) {
            $this->statuspan->statusform->binv->setVisible(true);
            $this->statuspan->statusform->bttn->setVisible(true);
            $this->statuspan->statusform->binp->setVisible(true);
            $this->statuspan->statusform->bclose->setVisible(true);
            $this->statuspan->statusform->bcan->setVisible(true);
        }

        if ($state == Document::STATE_INPROCESS) {
            $this->statuspan->statusform->binv->setVisible(true);
            $this->statuspan->statusform->bttn->setVisible(true);
            $this->statuspan->statusform->binp->setVisible(false);
            $this->statuspan->statusform->bcan->setVisible(false);
        }
        if ($state == Document::STATE_REFUSED) {

            $this->statuspan->statusform->binv->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->binp->setVisible(false);
            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->bcan->setVisible(true);
        }
        //закрыт
        if ($state == Document::STATE_CLOSED) {

            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->binv->setVisible(false);
            $this->statuspan->statusform->bcan->setVisible(false);

            $this->statuspan->statusform->setVisible(false);
        }
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(true);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
        $this->_tvars['askclose'] = false;
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }


        App::Redirect("\\App\\Pages\\Doc\\OrderCust", $doc->document_id);
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $d) {
            $csv .= date('Y.m.d', $d->document_date) . ';';
            $csv .= $d->document_number . ';';
            $csv .= $d->customer_name . ';';
            $csv .= $d->amount . ';';
            $csv .= Document::getStateName($d->state) . ';';
            $csv .= str_replace(';', '', $d->notes) . ';';
            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=ordercustlist.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class OrderCustDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = " date(document_date) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(document_date) <= " . $conn->DBDate($this->page->filter->to->getDate());

        $where .= " and meta_name  = 'OrderCust' ";

        $status = $this->page->filter->status->getValue();
        if ($status == 0) {
            $where .= " and  state <> 9 ";
        }


        $st = trim($this->page->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and meta_name  = 'OrderCust' and  content like {$st} ";
        }
        $sn = trim($this->page->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " meta_name  = 'OrderCust' and document_number like  {$sn} ";
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
