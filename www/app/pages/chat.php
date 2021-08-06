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

class Chat extends \App\Pages\Base
{

    public  $user = null;
 
    private $users;
    private $offset=0;
    
    public function __construct() {
        parent::__construct();
        $this->user = System::getUser();
        if ($this->user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }
        $this->users = \App\Entity\User::findArray("username", "disabled <>1");
    
        $this->add(new Form('msgform'))->onSubmit($this, 'OnSend');
        $this->msgform->add(new TextArea('msgtext'));
        $this->msgform->add(new DropDownChoice('msgpersonal',\App\Entity\User::findArray("username","disabled<>1 and user_id<>".$this->user->user_id,"username"),0));
   
        $this->Reload( ) ;
     
       $this->goAnkor('endchat') ;
    }

    public function Reload( ) {
       
        $this->_tvars['chat']=array();
        
        $nlist = Notify::find("","notify_id desc",  H::getPG(),$this->offset) ;
        $nlist = array_reverse( $nlist) ;
        
        foreach($nlist  as $n) {
            $item = array();
            $item['isme']= $n->sender_id==$this->user->user_id;
            $item['message'] =  nl2br($n->message);
            $item['sender'] = $this->users[$n->sender_id] ;
             
            $item['msgdate'] =  date('Y-m-d H:i',$n->dateshow) ;
            if(date('Y-m-d',$n->dateshow)==date('Y-m-d')) {
                $item['msgdate'] =  date('H:i',$n->dateshow) ;    
            }
                
             $this->_tvars['chat'][]= $item;
        }
 
    }

    
    public function OnSend($sender) {
            
        
            $n = new  Notify();
            $n->user_id = Notify::CHAT ;
            $n->message = $sender->msgtext->getText();
            $n->sender_id = System::getUser()->user_id;
            
            $up = $sender->msgpersonal->getValue();
            if($up>0) {
               $n->user_id = $up;  //личное  
               $this->setSuccess("sent") ;
            }
            $n->save();
            $sender->clean();
            
            $this->Reload( ) ;
          
            $this->goAnkor('endchat') ;
    }


}
