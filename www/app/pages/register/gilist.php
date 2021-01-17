<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\Entity\Firm;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * журнал  продаж
 */
class GIList extends \App\Pages\Base
{

    private $_doc = null;

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('GIList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
      
        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => H::l('opened'), 1 => H::l('newed'), 2 => H::l('sended'), 4 => H::l('notpayed'), 3 => H::l('all')), 0));
        $this->filter->add(new DropDownChoice('searchcomp', Firm::findArray('firm_name', 'disabled<>1', 'firm_name'), 0));
 
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
        $this->statuspan->statusform->add(new CheckBox('closeorder'));
 
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

        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa($doc->amount)));
        $row->add(new Label('order', $doc->headerdata['order']));
        $row->add(new Label('customer', $doc->headerdata['customer_name']));

        $row->add(new Label('state', Document::getStateName($doc->state)));
        $row->add(new Label('firm', $doc->firm_name));
        $row->add(new Label('waitpay'))->setVisible($doc->payamount > 0 && $doc->payamount > $doc->payed);

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state < Document::STATE_EXECUTED) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
    }

    public function statusOnSubmit($sender) {
        if (\App\Acl::checkChangeStateDoc($this->_doc, true, true) == false) {
            return;
        }

        $state = $this->_doc->state;

        if ($sender->id == "bsend") {
            $dec = $this->statuspan->statusform->ship_number->getText();
            $this->_doc->headerdata['sentdate'] = H::fd(time());
            if (strlen($dec) > 0) {
                $this->_doc->headerdata['ship_number'] = $dec;
            }
            $this->_doc->headerdata['document_date'] = time();
            $this->_doc->save();

            if($this->_doc->state<4) {
               $this->_doc->updateStatus(Document::STATE_EXECUTED);    
            }
            
            $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
  
            $this->_doc->save();
       
            $this->statuspan->statusform->ship_number->setText('');
            $this->setSuccess('sent');
        }

        if ($sender->id == "bdevivered") {
            $this->_doc->updateStatus(Document::STATE_DELIVERED);

         
            if ($this->statuspan->statusform->closeorder->isChecked()) {   //закрываем заказ
                $order = Document::load($this->_doc->parent_id);
                if($order  instanceof \App\Entity\Doc\Document)  {
                    $order = $order->cast();
                    if ($order->payamount > 0   && $order->payamount > $order->payed) {  
                        
                    }  else {    //оплачен
                        if ($order->state == Document::STATE_INPROCESS) {
                            $order->updateStatus(Document::STATE_CLOSED);
                            $this->setSuccess("order_closed ", $order->document_number);
                        }
                    }
                }
            }

           // $this->_doc->updateStatus(Document::STATE_CLOSED);
        }

        if ($sender->id == "bttn") {
            $d = $this->_doc->getChildren('TTN');

            if (count($d) > 0) {
                $this->setWarn('ttn_exists');

            }
            App::Redirect("\\App\\Pages\\Doc\\TTN", 0, $this->_doc->document_id);
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
        
        
        $this->statuspan->statusform->closeorder->setVisible(false);        
        if($this->_doc->headerdata['order_id']>0) {
            $order = Document::load($this->_doc->headerdata['order_id']);
            if(  $order->payamount == $order->payed){
               $this->statuspan->statusform->closeorder->setVisible(true);        
            }            
        } 

        
        
        $this->statuspan->statusform->closeorder->setChecked(false);

        $state = $this->_doc->state;
 
        //готов  к  отправке
        if ($state == Document::STATE_READYTOSHIP) {
             $this->statuspan->statusform->bdevivered->setVisible(false);
             $this->statuspan->statusform->bret->setVisible(false);
             $this->statuspan->statusform->closeorder->setVisible(false);
        }
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
        if ($this->_doc->meta_name == 'TTN') {
            $this->statuspan->statusform->ship_number->setVisible($this->_doc->headerdata['delivery']>2);

            $this->statuspan->statusform->bttn->setVisible(false);
        }
        if ($this->_doc->meta_name == 'GoodsIssue') {

            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
            $this->statuspan->statusform->bsend->setVisible(false);
        }
        if ($this->_doc->meta_name == 'POSCheck') {
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
            $this->statuspan->statusform->bsend->setVisible(false);
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
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }

        App::Redirect("\\App\\Pages\\Doc\\" . $doc->meta_name, $doc->document_id);
    }

    //оплаты

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $header = array();
        $data = array();
        
        $i=0;
        foreach ($list as $d) {
             $i++;
             $data['A'.$i]  =  H::fd($d->document_date) ;
             $data['B'.$i]  =  $d->document_number ;
             $data['C'.$i]  =  $d->headerdata['order'] ;
             $data['D'.$i]  =  $d->customer_name ;
             $data['E'.$i]  =  $d->amount ;
             $data['F'.$i]  =  $d->notes ;
             
        }
        
        H::exportExcel($data,$header,'selllist.xlsx') ;       
  
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class GoodsIssueDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();
     
        $where  = "   meta_name  in('GoodsIssue', 'Invoice','POSCheck','ReturnIssue' ,'Warranty','TTN' ) ";

        $status = $this->page->filter->status->getValue();
        if ($status == 0) {
            $where .= "  and  (  (payamount >0 and payamount > payed and  state >3 )  or( ( meta_name= 'TTN' and  state <> 9) or (meta_name <> 'TTN' and state <>5)  )  )    "  ;
        }
        if ($status == 1) {
            $where .= " and  state =  " . Document::STATE_NEW;
        }
        if ($status == 2) {
            $where .= " and state = " . Document::STATE_INSHIPMENT;
        }
        if ($status == 4) {
            $where .= "  and payamount >0 and  payamount > payed  and  state >3  ";
        }
        $comp = $this->page->filter->searchcomp->getValue();
        if ($comp > 0) {
            $where = $where . " and firm_id = " . $comp;
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

        return $docs;
    }

    public function getItem($id) {

    }

}
