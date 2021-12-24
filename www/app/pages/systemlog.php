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

 


    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

  
    }

  

     public function getItems($args, $post) {
        
         
        $f = json_decode($post) ;
         
        $where =   "dateshow <= now() and user_id=" . Notify::SYSTEM;
        
         if (strlen($f->search ) > 0) {
             $text = Notify::qstr('%' . $f->search . '%');
             $where .= " and    message like {$text}   "  ;
         }        
        
        $cnt =   Notify::findCnt($where) ;
        $itemlist = array();
        foreach( Notify::find($where,"dateshow desc",$f->count,$f->start ) as $n) {
           $item = array() ;
           $item['message'] = $n->message;
           $item['date'] =  \App\Helper::fdt($n->dateshow);
           $item['isnew'] =  $n->checked == 0;
           
           $itemlist[]=$item;    
        }
        Notify::markRead(Notify::SYSTEM); 
        return json_encode(array('items'=>$itemlist,'cnt'=>$cnt), JSON_UNESCAPED_UNICODE);     
    } 
    
    
public function getItems2($args, $post) {
        
        $sort ="";
        $pageno = -1;  
        $pagesize = -1;         
        
        if($args[3] =="asc" || $args[3] =="desc" ) $sort = $args[2].' '.$args[3];
        if( is_numeric($args[1] ) && $args[1] >0 ){
            $pageno = $args[0];  
            $pagesize = $args[1];  
        } 
       
        
        $where =   "dateshow <= now() and user_id<>" . Notify::SYSTEM;
          
        $cnt =   Notify::findCnt($where) ;
        $itemlist = array();
        foreach( Notify::find($where,$sort, $pagesize,$pageno) as $n) {
           $item = array() ;    
           $item['notify_id'] = "<i class='fa fa-eye'></i>";
           $item['message'] = $n->message;
           $item['dateshow'] = \App\Helper::fdt($n->dateshow); 
        
           
           $itemlist[]=$item;    
        }
         
        return json_encode(array('items'=>$itemlist,'allrows'=>$cnt), JSON_UNESCAPED_UNICODE);     
    } 
    
    
        
  
}
