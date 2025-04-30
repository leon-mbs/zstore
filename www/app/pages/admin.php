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
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
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
            return  ;
        }  
        $options = System::getOptions("common");       
              
        $this->_tvars['issms'] = ($options['smstype'] ?? 0)>0;
        $this->_tvars['isbot'] = strlen($options['tbtoken']??'')>0;
        
        $form = $this->add(new Form('optionsform'));
        $form->add(new CheckBox('capcha',$options['capcha']??0));
        $form->add(new CheckBox('noupdate',$options['noupdate']??0));
        $form->add(new CheckBox('checkip',$options['checkip'] ??0));
        $form->add(new TextArea('iplist',$options['iplist'] ??'' ));
        $form->add(new SubmitButton('save'))->onClick($this, 'saveOptions');
        
        
        $form = $this->add(new Form('sendform'));
        $form->add(new TextInput('email'))  ;
        $form->add(new SubmitButton('sendemail'))->onClick($this, 'sendEmail');
        $form->add(new TextInput('phone'))  ;
        $form->add(new SubmitButton('sendphone'))->onClick($this, 'sendSms');
        $form->add(new TextInput('chat_id'))  ;
        $form->add(new SubmitButton('sendbot'))->onClick($this, 'sendBot');
        
        $form->add(new SubmitButton('checkdb'))->onClick($this, 'checkDB');
        $this->add(new Label('checkdbanswer'))  ;


        $this->add(new Form('cdoc'))->onSubmit($this,"onCancelDoc");
        $this->cdoc->add(new TextInput('docn'))  ;

   
    }   

    
    public function saveOptions($sender) {
        $options = System::getOptions("common");       
        $options['capcha']  =  $this->optionsform->capcha->isChecked() ? 1 : 0;
        $options['noupdate']  =  $this->optionsform->noupdate->isChecked() ? 1 : 0;
        $options['checkip']  =  $this->optionsform->checkip->isChecked() ? 1 : 0;
        $options['iplist']  =  $this->optionsform->iplist->getText();


        System::setOptions("common",$options) ;
        $this->setSuccess('Збережено')  ;        
    }
    public function sendEmail($sender) {
        $email = trim( $this->sendform->email->getText() );
        try{
          H::sendLetter($email,"test body","test email")   ;
        } catch(\Exception $e) {
            H::logerror($e->getMessage()) ;
            $this->setError($e->getMessage())  ;
        }
   
    }
  
    public function sendSms($sender) {
        $phone = trim( $this->sendform->phone->getText() );
        try{
            \App\Entity\Subscribe::sendSMS($phone,"test sms");
        } catch(\Exception $e) {
            H::logerror($e->getMessage()) ;
            $this->setError($e->getMessage())  ;
        }
        
    }

        
        
    public function sendBot($sender) {
        $chat_id = trim( $this->sendform->chat_id->getText() );
        try{
            \App\Entity\Subscribe::sendBot($chat_id,"test bot");
        } catch(\Exception $e) {
            H::logerror($e->getMessage()) ;
            $this->setError($e->getMessage())  ;
        }
        
    }




    public function onCancelDoc($sender) {
        $dn = trim($this->cdoc->docn->getText() );
        $conn = \ZDB\DB::getConnect();

        $dn = $conn->qstr($dn);
     
        $doc =  \App\Entity\doc\Document::getFirst("document_number=".$dn);
        if($doc==null){
            $this->setError("Документ не знвйдено")  ;
            return;
        }
        if($doc->state <5){
            $this->setError("Документ не проведений")  ;
            return;
        }
        $conn->BeginTrans();

        try {
            $doc->updateStatus( \App\Entity\doc\Document::STATE_CANCELED);
            $doc->payed = 0;
            $doc->save();
            $conn->CommitTrans();

        } catch(\Throwable $ee) {
            
            $conn->RollbackTrans();
            \App\Helper::logerror(ee->getMessage())  ;
            $this->setError($ee->getMessage());

            return;
        }
        $this->setSuccess("Документ скасoвано")  ;
         
    }        


       
    public function checkDB($sender) {
          $answer="OK<br>";
        
        
          $this->checkdbanswer->setText($answer,true);
    }    
}
 