<?php

namespace App\Modules\DF\Admin;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use App\Entity\Doc\Document;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use App\Helper as H;
use App\Application as App;

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
   
  public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();
      //  $doc = $doc->cast();
        $row->add(new ClickLink('number',$this, 'showOnClick'))->setValue($doc->document_number);
        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('amount',  H::fa($doc->amount)));
        $row->add(new Label('customer', $doc->customer_name));
  }  
  
    public function showOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $this->_doc = $doc->cast() ;
        $html=$this->_doc->generateReportDF() ;
        $this->docpan->docview->setText($html,true);
        $this->docpan->setVisible(true) ;      
        $this->goAnkor('docpan');
        
    }
   public function saveOnClick($sender) {
       
      
       $uid= intval( $this->docpan->sform->emps->getValue() );
       if($uid==0)  return;
       $this->_doc->user_id = $uid;
       $this->_doc->setHD('delayinprocess',null);  
       $this->_doc->save();                       
       
       $this->docpan->sform->emps->setValue(9);
       $this->doclist->Reload() ;
       $this->docpan->setVisible(false) ;      
   
    }

}
class DocDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }    
    private function getWhere( ) {
       
     //   $conn = \ZDB\DB::getConnect();
       
        $where  = " meta_name  = 'Order' and state < 5 and  content   like '%<delayinprocess>2</delayinprocess>%' and content like '%<dsff>%'  " ;
         

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