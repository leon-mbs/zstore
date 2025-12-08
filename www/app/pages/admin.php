<?php

namespace App\Pages;

use App\Entity\Employee;
use App\Entity\TimeItem;
use App\Helper as H;
use App\System;
use App\Session;
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
        
        $form = $this->add(new Form('configform'));
        $form->add(new CheckBox('usebranch',$options['usebranch']??0));
        $form->add(new CheckBox('usefood',$options['usefood']??0));
        $form->add(new CheckBox('useprod',$options['useprod']??0));
        $form->add(new CheckBox('usends',$options['usends']??0));
        $form->add(new CheckBox('useacc',$options['useacc']??0));
       
        $form->add(new SubmitButton('saveconfig'))->onClick($this, 'saveConfig');
          
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

        $modules = System::getOptions("modules");

        $this->add(new Form('modules'))->onSubmit($this, 'onModules');
        $this->modules->add(new CheckBox('modocstore', $modules['ocstore']));
        $this->modules->add(new CheckBox('modshop', $modules['shop']));
        $this->modules->add(new CheckBox('modnote', $modules['note']));
        $this->modules->add(new CheckBox('modissue', $modules['issue']));
        $this->modules->add(new CheckBox('modwoocomerce', $modules['woocomerce']));
        $this->modules->add(new CheckBox('modnp', $modules['np']));
        $this->modules->add(new CheckBox('modpromua', $modules['promua']));

        $this->modules->add(new CheckBox('modvdoc', $modules['vdoc']));

//    
        
        $fisctype=0;
        if($modules['ppo']==1) $fisctype=1;
        if($modules['checkbox']==1) $fisctype=2;
        if($modules['vkassa']==1) $fisctype=3;
        if($modules['freg']==1) $fisctype=4;
        $this->modules->add(new DropDownChoice('modfisctype',[], $fisctype));

   
    }   

    
    public function saveConfig($sender) {
        $options = System::getOptions("common");       
        $options['usebranch']  =  $this->configform->usebranch->isChecked() ? 1 : 0;
        $options['usefood']  =  $this->configform->usefood->isChecked() ? 1 : 0;
        $options['useprod']  =  $this->configform->useprod->isChecked() ? 1 : 0;
        $options['usends']  =  $this->configform->usends->isChecked() ? 1 : 0;
        $options['useacc']  =  $this->configform->useacc->isChecked() ? 1 : 0;
          
        $conn = \ZDB\DB::getConnect();
      
        $where = " where meta_name in( 'ARMFood','DeliveryList','ArmProdFood','OutFood') or    menugroup= ".$conn->qstr('Кафе');
        if($options['usefood']==1) {
            $sql="update metadata set  disabled=0 ";
        }   else {
            $sql="update metadata set  disabled=1";
        }
        $conn->Execute($sql.$where);
     
        $where = " where meta_name in('TaskList','Task','EmpTask','ProdReceipt','ProdReceipt','ProdIssue','ProdAreaList','ProdProcList','ProdStageList','ProdReturn','Prod','EmpTask' ) or    menugroup= ".$conn->qstr('Виробництво');
        if($options['useprod']==1) {
            $sql="update metadata set  disabled=0 ";
        }   else {
            $sql="update metadata set  disabled=1";
        }
        $conn->Execute($sql.$where);
        
        $where = " where meta_name in('TaxInvoiceIncome','TaxInvoice2','TaxInvoice','TaxInvoiceList' )   " ;
        if($options['usends']==1) {
            $sql="update metadata set  disabled=0 ";
        }   else {
            $sql="update metadata set  disabled=1";
        }
        $conn->Execute($sql.$where);
     
        $where = " where meta_name in( 'AccountList','AccountEntryList','AccountActivity','ManualEntry','ObSaldo','Shahmatka','FinReportSmall','FinResult') or  menugroup= ".$conn->qstr('Бухоблiк');
      
        if($options['useacc']==1) {
            $sql="update metadata set  disabled=0 ";
        }   else {
            $sql="update metadata set  disabled=1";
        }
        $conn->Execute($sql.$where);
       
         
        System::setOptions("common",$options) ;
        
        Session::getSession()->menu = [];       
        
        $this->setSuccess('Збережено')  ;  
        App::Redirect("\\App\\Pages\\Admin");
              
    }
    
    public function saveOptions($sender) {
        $options = System::getOptions("common");       
        $options['capcha']  =  $this->optionsform->capcha->isChecked() ? 1 : 0;
        $options['noupdate']  =  $this->optionsform->noupdate->isChecked() ? 1 : 0;
        $options['checkip']  =  $this->optionsform->checkip->isChecked() ? 1 : 0;
        $options['iplist']  =  $this->optionsform->iplist->getText();


        System::setOptions("common",$options) ;
        Session::getSession()->menu = [];       
        
        $this->setSuccess('Збережено')  ; 
        App::Redirect("\\App\\Pages\\Admin");               
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
            \App\Helper::logerror($ee->getMessage())  ;
            $this->setError($ee->getMessage());

            return;
        }
        $this->setSuccess("Документ скасoвано")  ;
         
    }        


       
    public function checkDB($sender) {
           $this->checkdbanswer->setText('');
    
           $ver = str_replace('.','',System::REQUIRED_DB) ;        
           $origtables =    file_get_contents("https://zippy.com.ua/updates/{$ver}.db" ) ;  
                            
           if(strlen($origtables) == 0 ) {
               $this->setError('Структура для '.System::REQUIRED_DB.' не завантажена') ;
               return; 
           }  
        
         
        
           $conn = \ZDB\DB::getConnect();
           $tables=[];
           foreach( $conn->getCol("SHOW TABLES ") as $t){
               $tables[$t]=[];
           }
           foreach( array_keys( $tables) as $t){
               foreach( $conn->Execute("SHOW COLUMNS FROM  ".$t) as $c){
                   $tables[$t][]=$c['Field'];
               }        
           }
         
         
      //     file_put_contents("z:/{$ver}.db",serialize($tables)) ;                  
                        
           $origtables = unserialize($origtables) ;
      
               
           //проверка
           $answer="";
           
           foreach($origtables as $i=>$o)  {
              if( !is_array($tables[$i] ?? null) ){
                 $answer .= "Таблиця {$i} не знайдена<br>"; 
              } 
           }
       
          
       
   
           foreach($origtables as $i=>$o)  { 
               if(!isset($tables[$i])) continue;
               $cc=$tables[$i] ;
               foreach($origtables[$i] as $c)  {
                 
                  if( !in_array($c,$cc)  ){
                     $answer .= "Поле {$c} в {$i} не знайдено<br>"; 
                  } 
               }
           }
           if($answer !='' ) {
              $this->checkdbanswer->setText($answer,true);
          
              return; 
           }           
           
          $this->setSuccess('Структура OK')  ;
    }    
    
   public function onModules($sender) {
        $modules = System::getOptions("modules");
        $modules['ocstore'] = $sender->modocstore->isChecked() ? 1 : 0;
        $modules['shop'] = $sender->modshop->isChecked() ? 1 : 0;
        $modules['woocomerce'] = $sender->modwoocomerce->isChecked() ? 1 : 0;
        $modules['np'] = $sender->modnp->isChecked() ? 1 : 0;
        $modules['promua'] = $sender->modpromua->isChecked() ? 1 : 0;

        $modules['vdoc'] = $sender->modvdoc->isChecked() ? 1 : 0;

        $modules['issue'] = $sender->modissue->isChecked() ? 1 : 0;
        $modules['note'] = $sender->modnote->isChecked() ? 1 : 0;

 
        $fisctype = (int)$sender->modfisctype->getValue();
   
        $modules['ppo']   = $fisctype == 1 ? 1:0;
        $modules['checkbox']   = $fisctype == 2 ? 1:0;
        $modules['vkassa']   = $fisctype == 3 ? 1:0;
        $modules['freg']   = $fisctype == 4 ? 1:0;
 
        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');
        App::Redirect("\\App\\Pages\\Admin");

    }
    
    
}
 