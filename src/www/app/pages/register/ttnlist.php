<?php

namespace App\Pages\Register;

use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Prop;
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
 * журнал  ТТН
 */
class TTNList extends \App\Pages\Base
{

    private $_doc = null;
 
              
    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct( ) {
        parent::__construct();
        if(false ==\App\ACL::checkShowReg('OrderList'))return;       

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time() + (1 * 24 * 3600)));

        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => 'Открытые', 1 => 'Новые', 2 => 'Отправленые', 3 => 'Все'), 0));


        $doclist = $this->add(new DataView('doclist', new TTNDataSource($this), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(25);

      
        
        
        $this->add(new Panel("statuspan"))->setVisible(false);
        
        $this->statuspan->add(new Form('statusform'));
        
        $this->statuspan->statusform->add(new SubmitButton('bsend'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bclose'))->onClick($this, 'statusOnSubmit');
       $this->statuspan->statusform->add(new TextInput('ship_number'));
        
        
        

        $this->statuspan->add(new \App\Widgets\DocView('docview'));
 
       $this->doclist->Reload();
    }

    public function filterOnSubmit($sender) {
   
  
        $this->statuspan->setVisible(false);               

        $this->doclist->Reload(false); 
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();
         
        $row->add(new Label('number', $doc->document_number));
        
        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('onotes', $doc->notes ));
        $row->add(new Label('amount', $doc->amount ));
        $row->add(new Label('order',  $doc->headerdata['order']   ));
        $row->add(new Label('customer',  $doc->customer_name   ));

        $row->add(new Label('state', Document::getStateName($doc->state)));
  
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state == Document::STATE_CANCELED || $doc->state == Document::STATE_EDITED || $doc->state == Document::STATE_NEW) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }        

 
    }

    public function statusOnSubmit($sender) {
         
        $state = $this->_doc->state;
        $order = Document::load($this->_doc->headerdata['order_id']) ;
    
 
        if ($sender->id == "bsend") {
            $dec= $this->statuspan->statusform->ship_number->getText() ;
            $this->_doc->headerdata['sentdate'] = date('Y-m-d', time());
            if(strlen($dec)>0)$this->_doc->headerdata['ship_number'] = $dec;
            $this->_doc->headerdata['document_date'] = time();
            $this->_doc->save();
            
            $this->_doc->updateStatus(Document::STATE_EXECUTED);
            $this->_doc->updateStatus(Document::STATE_INSHIPMENT);


            $this->_doc->save();
            if($order instanceof Document){
               $order=  $order->cast(); 
               $order->updateStatus(Document::STATE_INSHIPMENT);
            }
            $this->statuspan->statusform->ship_number->setText('');
            $this->setSuccess('Отправлено');
     
        }
      
        if ($sender->id == "bclose") {
        $this->_doc->updateStatus(Document::STATE_CLOSED);
        $msg = 'Отправка  доставлена.';

        if($order instanceof Document){
            $order=  $order->cast(); 
            if ($order->state != Document::STATE_CLOSED && $order->amount == $order->datatag  ) { //если  все  отправлено закрываем  заказ
                $order->updateStatus(Document::STATE_CLOSED);
                $msg .= " Заказ {$order->document_number} закрыт";
            }           
        }

        $this->setSuccess($msg);      
           
            
            //$this->_doc->save();

            $this->_doc->updateStatus(Document::STATE_CLOSED);
            
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
            
            $this->statuspan->statusform->bclose->setVisible(false);
            
        } else {
            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bclose->setVisible(true);
        }
        //отправлен
        if ($state == Document::STATE_INSHIPMENT) {
            
            $this->statuspan->statusform->bclose->setVisible(true);
            $this->statuspan->statusform->bsend->setVisible(false);
            
        }
        //закрыт
        if ($state == Document::STATE_CLOSED) {
            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->setVisible(false);
        }
        
 
       
        
    }

    //просмотр
    public function showOnClick($sender) {
        
        $this->_doc = $sender->owner->getDataItem();
        if(false ==\App\ACL::checkShowDoc($this->_doc,true))return;       
        
        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor'); 
         
    }
    
    public function  editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
         if(false ==\App\ACL::checkEditDoc($doc,true))return;     

   
        App::Redirect("\\App\\Pages\\Doc\\TTN", $doc->document_id);
    }
    
  
    
    
    
}

/**
 *  Источник  данных  для   списка  документов
 */
class TTNDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
       $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = " date(document_date) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(document_date) <= " . $conn->DBDate($this->page->filter->to->getDate());

        $where .= " and meta_name  = 'TTN' ";




        $status = $this->page->filter->status->getValue();
        if ($status == 0) {
            $where .= " and  state <> 9 ";
        }
        if ($status == 1) {
            $where .= " and  state =1 ";
        }
        if ($status == 2) {
            $where .= " state =11";
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
            $where   = " meta_name  = 'TTN' and document_number like  {$sn} ";
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
