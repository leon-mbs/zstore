<?php

namespace App\Pages;

use App\Entity\Employee;
use App\Entity\TimeItem;
use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
 

class Admin extends \App\Pages\Base
{
    
     public function __construct() {
   
        parent::__construct();
       
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('До сторінки має доступ тільки адміністратор');
            \App\Application::RedirectError();
            return false;
        }  
        $options = System::getOptions("common");       
              
        $this->_tvars['issms'] = $options['smstype']>0;
        $this->_tvars['isbot'] = strlen($options['tbtoken'])>0;
        
        $form = $this->add(new Form('sendform'));
        $form->add(new TextInput('email'))  ;
        $form->add(new SubmitButton('sendemail'))->onClick($this, 'sendEmail');
        $form->add(new TextInput('phone'))  ;
        $form->add(new SubmitButton('sendphone'))->onClick($this, 'sendSms');
        $form->add(new TextInput('chat_id'))  ;
        $form->add(new SubmitButton('sendbot'))->onClick($this, 'sendBot');
 
    }   

    
    public function sendEmail($sender) {
        $email = trim( $this->sendform->email->getText() );
        try{
          H::sendLetter($email,"test body","test email")   ;
        } catch(\Exception $e) {
        
            $this->setError($e->getMessage())  ;
        }
   
    }
  
    public function sendSms($sender) {
        $phone = trim( $this->sendform->phone->getText() );
        try{
            \App\Entity\Subscribe::sendSMS($phone,"test sms");
        } catch(\Exception $e) {
        
            $this->setError($e->getMessage())  ;
        }
        
    }

    public function sendBot($sender) {
        $chat_id = trim( $this->sendform->chat_id->getText() );
        try{
            \App\Entity\Subscribe::sendBot($chat_id,"test bot");
        } catch(\Exception $e) {
        
            $this->setError($e->getMessage())  ;
        }
        
    }
    
}
 