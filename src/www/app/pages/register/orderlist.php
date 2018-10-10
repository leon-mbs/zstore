<?php

namespace App\Pages\Register;

use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\Application as App;
use App\System;

/**
 * журнал  заказов
 */
class OrderList extends \App\Pages\Base
{

    private $_doc = null;

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct($docid = 0) {
        parent::__construct();
        if(false ==\App\ACL::checkShowReg('OrderList'))return;       

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time() + (3 * 24 * 3600)));

        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => 'Открытые', 1 => 'Новые', 2 => 'Неоплаченые', 3 => 'Все'), 0));


        $doclist = $this->add(new DataView('doclist', new OrderDataSource($this), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(25);

        $doclist->Reload();

        $this->add(new Form('statusform'))->onSubmit($this, 'statusOnSubmit');
        $this->statusform->setVisible(false);
        $this->statusform->add(new SubmitButton('binprocess'))->onClick($this, 'statusOnSubmit');
        $this->statusform->add(new SubmitButton('bpayed'))->onClick($this, 'statusOnSubmit');
        $this->statusform->add(new SubmitButton('binshipment'))->onClick($this, 'statusOnSubmit');
        $this->statusform->add(new SubmitButton('bdelivered'))->onClick($this, 'statusOnSubmit');
        $this->statusform->add(new SubmitButton('bclose'))->onClick($this, 'statusOnSubmit');
        $this->statusform->add(new SubmitButton('bcancel'))->onClick($this, 'statusOnSubmit');

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);
        if ($docid > 0) {
            $this->statusform->setVisible(true);
            $this->docview->setVisible(true);
            $this->docview->setDoc(Document::load($docid));
            //$this->doclist->setSelectedRow($docid);
            $doclist->Reload();
        }
    }

    public function filterOnSubmit($sender) {

        $this->docview->setVisible(false);
        $this->statusform->setVisible(false);

        $this->doclist->setCurrentPage(1);

        $this->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();
        $doc = $doc->cast();
         
        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('delivery', $doc->headerdata['delivery_name']));
        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('amount', ($doc->amount > 0) ? $doc->amount : ""));

        $row->add(new Label('state', Document::getStateName($doc->state)));
        $row->add(new Label('sship'))->setVisible($doc->headerdata['inshipment'] == 1);
        $row->add(new Label('spay'))->setVisible($doc->headerdata['incredit'] == 1);
        ;
        $row->add(new Label('splan'))->setVisible($doc->headerdata['planned'] == 1);
        ;


        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        // $user = System::getUser();
        //$row->delete->setVisible($user->userlogin == 'admin' || $user->user_id = $item->user_id);

        if ($doc->state == Document::STATE_CANCELED || $doc->state == Document::STATE_EDITED || $doc->state == Document::STATE_NEW) {
            $row->sship->setVisible(false);
            $row->spay->setVisible(false);
            $row->splan->setVisible(false);
        }
    }

    public function statusOnSubmit($sender) {
        if ($sender->id == "bcancel") {
            $this->_doc->updateStatus(Document::STATE_CANCELED);
        }
        if ($sender->id == "binprocess") {
            $this->_doc->updateStatus(Document::STATE_EXECUTED);
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
        }
        if ($sender->id == "binprocess") {
            $this->_doc->updateStatus(Document::STATE_EXECUTED);
        }
        if ($sender->id == "bclose") {
            $this->_doc->headerdata['inshipment'] = '0';
            $this->_doc->headerdata['incredit'] = '0';
            $this->_doc->datatag = $this->_doc->amount;
            $this->_doc->save();

            $this->_doc->updateStatus(Document::STATE_CLOSED);
        }
        if ($sender->id == "bpayed") {
            $this->_doc->headerdata['incredit'] = '0';
            $this->_doc->datatag = $this->_doc->amount;
            $this->_doc->save();
            $this->_doc->updateStatus(Document::STATE_PAYED);
        }
        if ($sender->id == "binshipment") {
            $this->_doc->headerdata['inshipment'] = '1';
            $this->_doc->save();
            $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
        }
        if ($sender->id == "bdelivered") {
            $this->_doc->headerdata['inshipment'] = '0';
            $this->_doc->save();
            $this->_doc->updateStatus(Document::STATE_DELIVERED);
        }


        //todo  отослать писмо 
        $this->doclist->Reload();
        $this->updateStatussButtons();
    }

    public function updateStatussButtons() {

        $this->statusform->bclose->setVisible(true);

        $state = $this->_doc->state;
        $delivery = $this->_doc->headerdata['delivery'];    //доставка
        $payed = $this->_doc->datatag >= $this->_doc->amount; //оплачен
        $insh = $this->_doc->checkStates(array(Document::STATE_INSHIPMENT));
        $deli = $this->_doc->checkStates(array(Document::STATE_DELIVERED));

        //оплата
        $this->statusform->bpayed->setVisible(!$payed);

        //отмена   если  не было оплат и доставки
        if ($this->_doc->datatag == 0 && $insh) {
            $this->statusform->bcancel->setVisible(false);
        } else {
            $this->statusform->bcancel->setVisible(true);
        }


        //доставка
            $this->statusform->binshipment->setVisible(false);
            $this->statusform->bdelivered->setVisible(false);        
        if ($delivery == '1' || $delivery == '2') {
            if (false == $insh) {   // не отправлялся
                $this->statusform->binshipment->setVisible(true);
            }
            if ($insh == true && $deli == false) { //отправлен но не получен
                $this->statusform->bdelivered->setVisible(true);
            }
        }

        //новый     
        if ($state == Document::STATE_CANCELED || $state == Document::STATE_EDITED || $state == Document::STATE_NEW) {
            $this->statusform->binprocess->setVisible(true);
            $this->statusform->bpayed->setVisible(false);
            $this->statusform->binshipment->setVisible(false);
            $this->statusform->bdelivered->setVisible(false);
            $this->statusform->bclose->setVisible(false);
            $this->statusform->bcancel->setVisible(false);
        } else {
            $this->statusform->binprocess->setVisible(false);
        }
        //закрыт
        if ($state == Document::STATE_CLOSED) {
            $this->statusform->binprocess->setVisible(false);
            $this->statusform->bpayed->setVisible(false);
            $this->statusform->binshipment->setVisible(false);
            $this->statusform->bdelivered->setVisible(false);
            $this->statusform->bclose->setVisible(false);
            $this->statusform->bcancel->setVisible(false);
        }
    }

    //просмотр
    public function showOnClick($sender) {
        $this->_doc = $sender->owner->getDataItem();
        if(false ==\App\ACL::checkShowDoc($this->_doc,true))return;       
        
        $this->statusform->setVisible(true);
        $this->docview->setVisible(true);
        $this->docview->setDoc($this->_doc);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload();
        $this->updateStatussButtons();
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class OrderDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
       $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = " date(document_date) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(document_date) <= " . $conn->DBDate($this->page->filter->to->getDate());

        $where .= " and meta_name  = 'Order' ";




        $status = $this->page->filter->status->getValue();
        if ($status == 0) {
            $where .= " and  state <> 9 ";
        }
        if ($status == 1) {
            $where .= " and  state =1 ";
        }
        if ($status == 2) {
            $where .= " and  amount > datatag";
        }
        if ($status == 3) {
            
        }
    
        $st = trim($this->page->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where  .= " and meta_name  = 'Order' and  content like {$st} ";
        }    
        $sn = trim($this->page->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where   = " meta_name  = 'Order' and document_number like  {$sn} ";
        }       
        if($user->acltype == 2){
  
          
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
