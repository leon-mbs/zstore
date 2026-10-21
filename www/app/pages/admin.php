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
        $form->add(new CheckBox('useexcise',$options['useexcise']??0));
       
        $form->add(new SubmitButton('saveconfig'))->onClick($this, 'saveConfig');
          
        $form = $this->add(new Form('optionsform'));
        $form->add(new CheckBox('capcha',$options['capcha']??0));
        $form->add(new CheckBox('noupdate',$options['noupdate']??0));
        $form->add(new CheckBox('checkip',$options['checkip'] ??0));
        $form->add(new TextArea('iplist',$options['iplist'] ??'' ));
        $form->add(new SubmitButton('save'))->onClick($this, 'saveOptions');
        
        
     
        $this->add(new Form('cdoc'))->onSubmit($this,"onCancelDoc");
        $this->cdoc->add(new TextInput('docn'))  ;

        $modules = System::getOptions("modules");

        $this->add(new Form('modules'))->onSubmit($this, 'onModules');
        $this->modules->add(new CheckBox('modocstore', $modules['ocstore']));
        $this->modules->add(new CheckBox('modshop', $modules['shop']));
        $this->modules->add(new CheckBox('modnote', $modules['note']));
        $this->modules->add(new CheckBox('modissue', $modules['issue']));
        $this->modules->add(new CheckBox('modwoocomerce', $modules['woocomerce']));
        $this->modules->add(new CheckBox('modhoroshop', $modules['horoshop']));
        $this->modules->add(new CheckBox('modnp', $modules['np']));
        $this->modules->add(new CheckBox('moddf', $modules['df']));
        $this->modules->add(new CheckBox('modpromua', $modules['promua']));

        $this->modules->add(new CheckBox('modvdoc', $modules['vdoc']));

//    
        
        $fisctype=0;
        if($modules['ppo']==1) $fisctype=1;
        if($modules['checkbox']==1) $fisctype=2;
        if($modules['vkassa']==1) $fisctype=3;
        if($modules['freg']==1) $fisctype=4;
        $this->modules->add(new DropDownChoice('modfisctype',[], $fisctype));

        $form = $this->add(new Form("oeform"));
        $form->add(new CheckBox('oecommon' ));
        $form->add(new CheckBox('oeusers' ));
        $form->add(new CheckBox('oemf' ));
        $form->onSubmit($this, "onOExport");

        $form = $this->add(new Form("oiform"));
        $form->add(new \Zippy\Html\Form\File("oifilename"));
        $form->add(new CheckBox('oicommon' ));
        $form->add(new CheckBox('oiusers' ));
        $form->add(new CheckBox('oimf' ));
   
        $form->add(new SubmitButton('oib'))->onClick($this, 'onOImport');
   
        $this->add(new ClickLink('checkdb'))->onClick($this, 'checkDB') ;
        $this->add(new Label('checkdbanswer'))  ;

        

        //API
        $this->add(new Form('api'))->onSubmit($this, 'saveApiOnClick');

  
        $this->api->add(new TextInput('aexp'));
        $this->api->add(new DropDownChoice('atype', array('1' => "Авторизація з JWT (Bearer)", '2' => "Basic авторизація", '3' => "Автоматична авторизація"), 1))->onChange($this, 'onApiType');
        $api = System::getOptions("api",true);
        if (!is_array($api)) {
            $api = array('exp' => 60, 'key' => 'qwerty', 'atype' => 1);
        }
 
        $this->api->aexp->setValue($api['exp']);
        $this->api->atype->setValue($api['atype']);

        $this->onApiType($this->api->atype);        
        
        //телеграм бот

        $this->add(new Form('tbform'))->onSubmit($this, "onBot");
        $this->tbform->add(new TextInput('tbtoken', $options['tbtoken'] ?? ''));

   
        $this->add(new ClickLink('sendbot'))->onClick($this, 'sendBot');
        

 
        //SMS
        $this->add(new Form('sms'));

        $this->sms->add(new SubmitButton('smssubmit'))->onClick($this, 'saveSMSOnClick');
        $this->sms->add(new SubmitButton('smstest'))->onClick($this, 'testSMSOnClick');
 
        $this->sms->add(new TextInput('smsclubtoken'));
        $this->sms->add(new TextInput('smsclublogin'));
        $this->sms->add(new TextInput('smsclubpass'));
        $this->sms->add(new TextInput('smscluban'));
        $this->sms->add(new TextInput('smsclubvan'));
        $this->sms->add(new TextInput('smssemytoken'));
        $this->sms->add(new TextInput('smssemydevid'));
        $this->sms->add(new TextInput('smstestphone'));
    
        $this->sms->add(new TextInput('flysmslogin'));
        $this->sms->add(new TextInput('flysmspass'));
        $this->sms->add(new TextInput('flysmsan'));

        $this->sms->add(new TextArea('smscustscript'));
        $this->sms->add(new DropDownChoice('smscustlang', array('js' => "JavaScript",  'php' => "PHP"), 'js')) ;

        $this->sms->add(new DropDownChoice('smstype', array('1' => "SemySMS",  '2' => "SMSClub",   '3' => 'SMS-Fly', '4' => 'Кастомний скрипт'), 0))->onChange($this, 'onSMSType');
       
        $sms = System::getOptions("sms",true);

        $this->sms->smssemytoken->setText($sms['smssemytoken']);
        $this->sms->smssemydevid->setText($sms['smssemydevid']);
        $this->sms->flysmslogin->setText($sms['flysmslogin']);
        $this->sms->flysmspass->setText($sms['flysmspass']);
        $this->sms->flysmsan->setText($sms['flysmsan']);
        $this->sms->smsclubtoken->setText($sms['smsclubtoken']);
        $this->sms->smsclublogin->setText($sms['smsclublogin']);
        $this->sms->smsclubpass->setText($sms['smsclubpass']);
        $this->sms->smscluban->setText($sms['smscluban']);
        $this->sms->smsclubvan->setText($sms['smsclubvan']);

        $this->sms->smscustlang->setValue($sms['smscustlang']);
        $this->sms->smscustscript->setText( base64_decode($sms['smscustscript'] ));

        $this->sms->smstype->setValue($sms['smstype']);

        $this->onSMSType($this->sms->smstype);

        //email
        
        $opt =   \App\Comm::getEmailOptions();        
     
        $form = $this->add(new Form('emailform'));     
        $form->add(new TextInput('emailfrom',$opt['emailfrom']))  ;

        $form->add(new DropDownChoice('emailtype',[],$opt['usesmtp'] ? 1:0))->onChange($this, 'onEmailType');  ;
        $form->add(new TextInput('emailuser',$opt['user']))  ;
        $form->add(new TextInput('emailhost',$opt['host']))  ;
        $form->add(new TextInput('emailport',$opt['port']))  ;
        $form->add(new TextInput('emailpass',$opt['pass']))  ;
        $form->add(new CheckBox('tls',$opt['tls']))  ;
        $form->add(new CheckBox('usecron',$opt['usecron']?? false))  ;
         
        $form->add(new SubmitButton('saveemail'))->onClick($this, 'saveEmailOnClick');
       
        $form->add(new TextInput('testemail'))  ;
        $form->add(new SubmitButton('emailtest'))->onClick($this, 'sendEmail');
     
        $this->onEmailType($form->emailtype);
    }   

    
    public function saveConfig($sender) {
        $options = System::getOptions("common");       
        $options['usebranch']  =  $this->configform->usebranch->isChecked() ? 1 : 0;
        $options['usefood']  =  $this->configform->usefood->isChecked() ? 1 : 0;
        $options['useprod']  =  $this->configform->useprod->isChecked() ? 1 : 0;
        $options['usends']  =  $this->configform->usends->isChecked() ? 1 : 0;
        $options['useacc']  =  $this->configform->useacc->isChecked() ? 1 : 0;
        $options['useexcise']  =  $this->configform->useexcise->isChecked() ? 1 : 0;
          
        $conn = \ZDB\DB::getConnect();
      
        $where = " where meta_name in( 'ARMFood','DeliveryList','ArmProdFood','OutFood','ARMFoodW') or    menugroup= ".$conn->qstr('Кафе');
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
        /*
        $where = " where meta_name in('TaxInvoiceIncome','TaxInvoice2','TaxInvoice','TaxInvoiceList' )   " ;
        if($options['usends']==1) {
            $sql="update metadata set  disabled=0 ";
        }   else {
            $sql="update metadata set  disabled=1";
        }
        $conn->Execute($sql.$where);
        */
        $where = " where meta_name in( 'AccountList','AccountEntryList','AccountActivity','ManualEntry','ObSaldo','Shahmatka','FinReportSmall','FinResult') or  menugroup= ".$conn->qstr('Бухоблік');
      
        if($options['useacc']==1) {
            $sql="update metadata set  disabled=0 ";
        }   else {
            $sql="update metadata set  disabled=1";
        }
        $conn->Execute($sql.$where);
       
        $where = " where meta_name in( 'ExciseList') "  ;
      
        if($options['useexcise']==1) {
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

    public function onEmailType($sender) {
       $smtp = $this->emailform->emailtype->getValue() == 1 ;
       $this->emailform->emailuser->setVisible($smtp);
       $this->emailform->emailhost->setVisible($smtp);
       $this->emailform->emailport->setVisible($smtp);
       $this->emailform->emailpass->setVisible($smtp);
       $this->emailform->tls->setVisible($smtp);
     
    }
    public function saveEmailOnClick($sender) {
        $email = array();
        $email['emailfrom'] = $this->emailform->emailfrom->getText();
        $email['user'] = $this->emailform->emailuser->getText();
        $email['host'] = $this->emailform->emailhost->getText();
        $email['port'] = $this->emailform->emailport->getText();
        $email['pass'] = $this->emailform->emailpass->getText();
        $email['tls'] = $this->emailform->tls->isChecked();
        $email['usecron'] = $this->emailform->usecron->isChecked();
        $email['usesmtp'] = $this->emailform->emailtype->getValue()=="1";
       
         
        System::setOptions("email", $email);
        $this->setSuccess('Збережено');
    }
 
    public function sendEmail($sender) {
        $email = trim( $this->emailform->testemail->getText() );
        try{
           \App\Comm::sendEmail($email,"test body","test email")   ;
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
            $this->setError("Документ не знайдено")  ;
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
        $this->setSuccess("Документ скасовано")  ;
         
    }        
       
    public function checkDB($sender) {
           $this->checkdbanswer->setText('');
    
           $ver = str_replace('.','',System::REQUIRED_DB) ;        
          
           $origtables =\App\Helper::getContent("https://zippy.com.ua/updates/{$ver}.db") ;
           if($origtables === false) {
               return  ;
           }
          
                         
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
                                  
         
         //  file_put_contents("z:/home/local.site/www/updates/{$ver}.db",serialize($tables)) ;                  
                         
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
        $modules['horoshop'] = $sender->modhoroshop->isChecked() ? 1 : 0;
        $modules['np'] = $sender->modnp->isChecked() ? 1 : 0;
        $modules['df'] = $sender->moddf->isChecked() ? 1 : 0;
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
    
    public function onOExport($sender) {
        $conn= \ZDB\DB::getConnect() ;
        $a=[];
        $a['options']=[];
        $a['users']=[];
        $a['roles']=[];
        $a['mf']=[];
        $a['pos']=[];
        if($sender->oecommon->isChecked()){    
            foreach( $conn->Execute("select * from  options") as $o){
               $a['options'][$o['optname']]=$o['optvalue'] ;
            }
        }
        if($sender->oeusers->isChecked()){    
            foreach( \App\Entity\User::find("userlogin <> 'admin' and  disabled<>1")  as $u){
               $a['users'][]= $u;
            }
            foreach( \App\Entity\UserRole::find("rolename <> 'admins' and disabled<>1")  as $r){
               $a['roles'][]= $r;
            }
        }
        if($sender->oemf->isChecked()){    
            foreach( \App\Entity\MoneyFund::find("disabled<>1")  as $m){
               $a['mf'][]= $m;
            }
            foreach( \App\Entity\Pos::find("")  as $p){
               $a['pos'][]= $p;
            }
            
        }

        $file = serialize($a) ;
         
    
       $filename = 'options_' . date('Y_m_d', time()) . '.bak' ;

          
        header("Content-type: text/plain");
        header("Content-Disposition: attachment;Filename={$filename}");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: '.strlen($file));
 
        echo $file;
        die;        
        
     
        
    }

    public function onOImport($sender) {

        $file = $this->oiform->oifilename->getFile();
        if (strlen($file['tmp_name']) == 0) {
            $this->setError('Не вибраний файл');
            return;
        }

         
        $a = unserialize( file_get_contents( $file['tmp_name'] ) ) ;
        
        if(!is_array($a) ) {
            $this->setError('Некоректний файл');
            return;
        }
            
        $conn= \ZDB\DB::getConnect() ;
 
        $conn->BeginTrans();
        try {
          if($this->oiform->oicommon->isChecked()){    
              Session::getSession()->options =[]; 
              $conn->Execute("delete from options") ;

              foreach ($a['options'] as $n=>$v) {
                 $conn->Execute("insert options (optname,optvalue) values (". $conn->qStr($n)  .",". $conn->qStr($v)   .")  ") ;
              }
          }
          if($this->oiform->oiusers->isChecked()){   
              $conn->Execute("delete from users where  userlogin <>'admin' ") ;
              $conn->Execute("delete from roles where  rolename <>'admins' ") ;
          
           
              foreach ($a['roles'] as  $r) {
                 $r->role_id=0;
                 $r->save() ;
                
              }
              foreach ($a['users'] as  $u) {
                $u->user_id=0;
                $u->save() ;
              }
          }
          if($this->oiform->oimf->isChecked()){    
              $conn->Execute("delete from poslist") ;
              $conn->Execute("delete from mfund") ;
             
              
              foreach ($a['mf'] as  $m) {
                 $m->mf_id=0;   
                 $m->save() ;
              }
              foreach ($a['pos'] as  $p) {
                 $p->pos_id=0; 
                 $p->save() ;
              }
          }
       
          $conn->CommitTrans();

        } catch(\Throwable $ee) {
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            return;
        }


        $this->setSuccess("Імпортовано");
    }
  
    public function onApiType($sender) {
        $type = $this->api->atype->getValue();
        $this->api->aexp->setVisible($type == 1);
     
      
    }

    public function saveApiOnClick($sender) {
        $api = array();
        $api['exp'] = $this->api->aexp->getText();
 
        $api['atype'] = $this->api->atype->getValue();

        System::setOptions("api", $api);
        $this->setSuccess('Збережено');
    }

    public function onBot($sender) {


        $common = System::getOptions("common",true);
        if (!is_array($common)) {
            $common = array();
        }

        $common['tbtoken'] = $sender->tbtoken->getText()  ;
      //  $common['tbname'] = $sender->tbname->getText()  ;
        if( strlen($common['tbtoken'] )==0 ) {
            $this->setWarn("Не задано токен") ;
            System::setOptions("common", $common);            
            return;
        }
        
        $url= _BASEURL. 'chatbot.php' ;

        $bot = new \App\ChatBot($common['tbtoken']) ;
//        $res = $bot->doGet('getWebhookInfo') ;
        $res = $bot->doGet('deleteWebhook') ;
        $res = $bot->doGet('setWebhook', array('url'=>$url)) ;
        if($res['error_code'] == 404) {
            $this->setError("Невірний токен") ;
            return;
        }
        if($res['ok'] != true) {
            $this->setError($res['error_code']. ' ' .$res['description']) ;
            return;
        }

        H::log("set hook ".$url);

        System::setOptions("common", $common);
        $this->setSuccess('Збережено');

    }
  
    public function sendBot($sender) {
       
        $options = System::getOptions("common");       
       
        $chat_id = $options['tbtoken'] ?? '';

        try{
            \App\Comm::sendBot($chat_id,"test bot");
        } catch(\Exception $e) {
            H::logerror($e->getMessage()) ;
            $this->setError($e->getMessage())  ;
        }
        
    }
 

    public function onSMSType($sender) {
        $type = $this->sms->smstype->getValue();
        $this->sms->smssemytoken->setVisible($type == 1);
        $this->sms->smssemydevid->setVisible($type == 1);
   
        $this->sms->smsclubtoken->setVisible($type == 2);
        $this->sms->smsclublogin->setVisible($type == 2);
        $this->sms->smsclubpass->setVisible($type == 2);
        $this->sms->smscluban->setVisible($type == 2);
        $this->sms->smsclubvan->setVisible($type == 2);
     
        $this->sms->flysmslogin->setVisible($type == 3);
        $this->sms->flysmspass->setVisible($type == 3);
        $this->sms->flysmsan->setVisible($type == 3);

     
        $this->sms->smscustlang->setVisible($type == 4);
        $this->sms->smscustscript->setVisible($type == 4);

        $this->sms->smstestphone->setVisible($type >0 );
      
        $this->sms->smstest->setVisible($type >0 );

      //  $this->goAnkor('smstype');
    }

    public function saveSMSOnClick($sender) {
        $sms = array();
        $sms['smstype'] = $this->sms->smstype->getValue();

        $sms['smsclubtoken'] = $this->sms->smsclubtoken->getText();
        $sms['smsclublogin'] = $this->sms->smsclublogin->getText();
        $sms['smsclubpass'] = $this->sms->smsclubpass->getText();
        $sms['smscluban'] = $this->sms->smscluban->getText();
        $sms['smsclubvan'] = $this->sms->smsclubvan->getText();
        $sms['smssemytoken'] = $this->sms->smssemytoken->getText();
        $sms['smssemydevid'] = $this->sms->smssemydevid->getText();
        $sms['flysmslogin'] = $this->sms->flysmslogin->getText();
        $sms['flysmspass'] = $this->sms->flysmspass->getText();
        $sms['flysmsan'] = $this->sms->flysmsan->getText();
        $sms['smscustlang'] = $this->sms->smscustlang->getValue();
        $sms['smscustscript'] = base64_encode($this->sms->smscustscript->getText() );

        System::setOptions("sms", $sms);
        $this->setSuccess('Збережено');
    }

    public function testSMSOnClick($sender) {
     
        $res = \App\Comm::sendSMS($this->sms->smstestphone->getText(), 'Тест');
        if (strlen($res) == 0) {
           
            $res = \App\Comm::sendViber($this->sms->smstestphone->getText(), 'Тест');

        } else {
            $this->setError($res);
        }

    }
  
    
}
 