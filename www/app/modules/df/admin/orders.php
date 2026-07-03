<?php

namespace App\Modules\DF\Admin;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use App\Helper as H;
use App\Application as App;
use App\Entity\Doc\Document; 

class Orders extends \App\Pages\Base
{
    private  $_doc;
    
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'df') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");
       
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->filter->add(new TextInput('searchnumber'));
       
        $this->filter->add(new DropDownChoice('status', array(0 => 'Вiдкритi', 1 => 'Закритi',2 => 'Всi'), 0));
        $this->filter->add(new DropDownChoice('fpartner', \App\Entity\Customer::findArray("customer_name","status=0 and (detail like '%<df>1</df>%' or detail like '%<df>2</df>%'  ) ","customer_name"), 0));
        $this->filter->add(new Date('from', time() - (15 * 24 * 3600)));
        $this->filter->add(new Date('to', 0));


        $this->add(new \Zippy\Html\DataList\DataView('doclist', new DocDataSource($this ), $this, 'doclistOnRow'))->Reload() ;

        $this->add(new Panel('docpan'))->setVisible(false);
        $this->docpan->add(new Label('docview')) ;
        $this->docpan->add(new Form('sform'))->onSubmit($this,'saveOnClick') ;

        $users = array() ;

        foreach(\App\Entity\User::find("disabled <> 1", "username asc") as $_u) {
            if($_u->rolename == 'admins') {
                $users[$_u->user_id]=$_u->username;
            } else {
                                 
              //  if( \App\ACL::checkEditDoc($this->_doc,true,false,$_u->user_id) == true ||  \App\ACL::checkExeDoc($this->_doc,true,false,$_u->user_id) == true ||  \App\ACL::checkChangeStateDoc($this->_doc,true,false,$_u->user_id) == true) {
                    $users[$_u->user_id]=$_u->username;
             //   }

            }
        }        
        $this->docpan->sform->add(new DropDownChoice('emps',$users,0)) ;
     
      
    }
    public function filterOnSubmit($sender) {

        $this->docpan->setVisible(false);
      
        $this->doclist->Reload();

    }  
    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();
      //  $doc = $doc->cast();
        $row->add(new ClickLink('number',$this, 'showOnClick'))->setValue($doc->document_number);
        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('amount',  H::fa($doc->amount)));
        $row->add(new Label('customer', $doc->customer_name));
        $stname = Document::getStateName($doc->state);
       
        $row->add(new Label('state', $stname));        
        
  }  
  
    public function showOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $this->_doc = $doc->cast() ;
        $html=$this->_doc->generateReportDF() ;
        $this->docpan->docview->setText($html,true);
        $this->docpan->setVisible(true) ;      
        $this->goAnkor('docpan');
 
        $this->docpan->sform->setVisible($doc->state == \App\Entity\Doc\Document::STATE_WAIT  ) ;   
       
    }

    public function saveOnClick($sender) {
       
      
       $uid= intval( $this->docpan->sform->emps->getValue() );
       if($uid==0)  return;
       $this->_doc->user_id = $uid;
                           
       $this->_doc->updateStatus(Document::STATE_INPROCESS);
         
       $this->doclist->Reload() ;
       $this->docpan->setVisible(false) ;      
       $this->setSuccess('Вiдправлено') ;
    
        $n = new \App\Entity\Notify();
        $n->user_id = $uid;
        $n->sender_id = $user->user_id;
        $n->dateshow = time();
        $n->message = "Вам  призначено замовлення {$this->_doc->document_number}  " ;

        $n->save();      
       
    }

}
class DocDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }    
    private function getWhere( ) {
       
        $conn = \ZDB\DB::getConnect();
        $searchnumber= trim( $this->page->filter->searchnumber->getText());
        $status=  $this->page->filter->status->getValue() ;
        $fpartner=  $this->page->filter->fpartner->getValue() ;
 
        $wherebase  = " meta_name  = 'Order' and content like '%<dsff>%'  " ;
        $where  = $wherebase;
        if($fpartner > 0) {
           $where .= " and customer_id=".$fpartner; 
        }       
        if($status == 0) {
           $where .= " and state not in(9,17) " ; 
        }       
        if($status == 1) {
           $where .= " and state   in(9,17) "  ; 
        }    
        if($filter->from > 0) {
            $where .= " and  document_date >= " . $conn->DBDate($filter->from) ;
        }
        if($filter->to > 0) {
            $where .= " and  document_date <= " . $conn->DBTimeStamp($filter->to+3600*24-1) ;
        }           
        if(strlen($searchnumber) > 0) {
         
           $where  = $wherebase . " and documant_bumber=".$conn->qstr($searchnumber);
           
        }       

        return $where;
    }
 
    public function getItemCount() {
         
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $docs = Document::find($this->getWhere(), "document_id desc", $count, $start);         //         $docs = Document::find($this->getWhere(), "priority desc,document_id desc", $count, $start);

        return $docs;
    }

    public function getItem($id) {

    }

}