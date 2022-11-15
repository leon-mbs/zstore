<?php

namespace App\Pages;

use App\Entity\Notify;
use App\Helper as H;
use App\System;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\WebApplication as App;

class SystemLog extends \App\Pages\Base
{

    public  $ds;


    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }
  
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new TextInput('searchtext'));

        $this->ds = new EntityDataSource("\\App\\Entity\\Notify", "  user_id=" .   Notify::SYSTEM, " dateshow desc");
  
        $this->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->nlist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));

        
        
        $this->filterOnSubmit($this->filter)  ;
      \App\Entity\Notify::markRead(\App\Entity\Notify::SYSTEM);
       
    }

  

     public function filterOnSubmit($sender) {
        
        $st = trim( $sender->searchtext->getText() ) ;
          
        $where =   "  user_id=" . Notify::SYSTEM;
        
         if (strlen($st ) > 0) {
             $text = Notify::qstr('%' . $st. '%');
             $where .= " and    message like {$text}   "  ;
         }        
        


        $this->ds->setWhere($where);
        $this->nlist->Reload();  
    } 
    
   public function OnRow($row) {
        $notify = $row->getDataItem();
        
        $row->add(new Label("msg"))->setText($notify->message, true);

        $row->add(new Label("ndate", \App\Helper::fdt($notify->dateshow)));
        $row->add(new Label("newn"))->setVisible($notify->checked == 0);
      
        
   }   
   
  
}
