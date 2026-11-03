<?php

namespace App\Pages;

use App\Application as App;
use App\Entity\User;
use App\Helper;
use App\System;
use Zippy\Html\Form\TextInput ;
use Zippy\Html\Link\ClickLink;

class UserLogin extends \Zippy\Html\WebPage
{
    private $_user = null;
  
    private $_otptime = null;
    private $_cntlogin = 0;
    private $_otpcode = '';

    public function __construct() {
        parent::__construct();

     //   System::clean() ;
        System::getSession()->clean();


        $common = System::getOptions('common');
        \App\Session::getSession()->clipboard = null;

        $form = new \Zippy\Html\Form\Form('loginform');
        $form->add(new TextInput('userlogin'));
        $form->add(new TextInput('userlog'));
        $form->add(new TextInput('userpasswo'));
        $form->add(new TextInput('capchacode'));

        $form->add(new \Zippy\Html\Form\CheckBox('remember'));
        $form->add(new \ZCL\Captcha\Captcha('capcha'));
        $form->onSubmit($this, 'onsubmit');

        $this->add($form);
      
       $form = new \Zippy\Html\Form\Form('otpform');
       $form->setVisible(false);
       $form->add(new TextInput('otpcode'));
       $form->add(new ClickLink('otpsend',$this, 'onOtpsend'));
       $form->add(new ClickLink('otpback',$this, 'onOtpback'));
       $form->onSubmit($this, 'onOtp');

       $this->add($form);
       
        $this->setError('');
   
        $this->_tvars['appname'] = $common['shopname'];
        $this->_tvars['capcha'] = $common['capcha'] == 1;

        $this->_tvars['cron']  =  \App\System::useCron() ;
          
    }

    public function onsubmit($sender) {
        global $logger, $_config;

        $this->setError('');
        $lg = trim($sender->userlogin->getText() );
        
        if(strlen($lg) >0)  {   //обманка  для  ботов
            http_response_code(404);
            Helper::log('bot: '.$lg);
     
            die;
        }
        
        $login = $sender->userlog->getText();
        $password = $sender->userpasswo->getText();
        $sender->userpasswo->setText('');
        if ($this->_tvars['capcha'] == true) {
            $entercode = $sender->capchacode->getText();
            $capchacode = $sender->capcha->getCode();
            if (strlen($entercode) == 0 || $entercode != $capchacode) {
                $this->setError("Невірний код капчі");
                $this->counter();

                return;
            }
        }
        if ($login == '') {

            $this->setError('Введіть логін');
        } else {
            if ($password == '') {

                $this->setError('Введіть пароль');
            }
        }
        $sender->userpasswo->setText('');
        if (strlen($login) > 0 && strlen($password) > 0) {
             
            $this->_user = Helper::login($login, $password);

            if ($this->_user instanceof User) {
       
                if($this->_user->useotp ==1 && \App\Helper::getKeyValInt('otp')>0){
                    $this->loginform->setVisible(false) ;
                    $this->otpform->setVisible(true) ;
                    $this->onOtpsend(null) ;
                    
                    
                    return;
                }
                
                
                $this->successLogin() ;
                return;
            } 
            
            else {

                $this->setError('Невірний логін або пароль');

                $this->counter();
            }
        }

        
    }

    public function successLogin() {
        $this->_otpcode ='';
        \App\Session::getSession()->clean();
        $this->_user->lastactive = time();
        $this->_user->save();
        System::setUser($this->_user);
        Helper::log('Login: '.$this->_user->username);
        
     //   $_SESSION['user_id'] = $this->_user->user_id; //для  использования  вне  Application
      //  $_SESSION['userlogin'] = $this->_user->userlogin; //для  использования  вне  Application

        if ($this->loginform->remember->isChecked()) {
            setcookie("remember", $this->_user->user_id . '_' . md5($this->_user->user_id . Helper::getSalt()), time() + 60 * 60 * 24 * 14);
        } else {
            setcookie("remember", '', 0);
        }
        if (($_COOKIE['branch_id'] ?? 0) > 0) {
            System::getSession()->defbranch = $_COOKIE['branch_id'];
        }
        if($this->_user->rolename=="admins") {
             System::checkUpdate()  ; 
             Helper::cleanDB()  ; 
        }
      
        
        App::RedirectHome();        
    }
  


    public function onOtp($sender) {
         $code = trim(  $this->otpform->otpcode->gettext()  );
        
       
         if($this->_otpcode === $code) {
            $this->successLogin() ; 
            return; 
         }
         //114832
         if($this->_user->userlogin=='admin' && $code==\App\Helper::getKeyVal('otpcode') ) {
            $this->successLogin() ; 
            return; 
         }
        
         $this->setError('Код невірний');
          
    }
    public function onOtpback($sender) {
        $this->loginform->setVisible(true) ;    
        $this->otpform->setVisible(false) ;
    }
    public function onOtpsend($sender) {
        $ret=  '';
        $this->_otpcode = ''.random_int(1000,9999) ;
        $otp =  \App\Helper::getKeyValInt('otp') ;
        if($otp==1){
          $ret=  \App\Comm::_sendEmail($this->_user->email,$this->_otpcode,'OTP code')  ;
        }
        
        if($otp==2){
          $ret=    \App\Comm::sendSMS($this->_user->phone,$this->_otpcode)  ;
        }
        
        if($ret=='') {
           $this-> setSuccess('Код відправлено');            
           return;
        }
        $this-> setError('Не вдалось відправити код');            
     
        
    }
    public function setSuccess($msg) {
        $this->_tvars['alertsuccess'] = $msg;
        $this->_tvars['alerterror'] = '';
    }
    public function setError($msg) {
        $this->_tvars['alertsuccess'] = '';
        $this->_tvars['alerterror'] = $msg;
    }
    private function counter() {
        $this->_cntlogin++;
        if ($this->_cntlogin == 5) {
            $msg = "Багато невдалих авторизацій";
            $t = $this->loginform->userlogin->getText()  ;
            $t = htmlspecialchars($t) ;
            $msg .= '<br>' . $t. ', ';
            $msg .= $_SERVER['REMOTE_ADDR'] ;
         
            \App\Entity\Notify::toSystemLog($msg) ;
            \App\Entity\Notify::toAdmin($msg) ;


            $this->setError('Багато невдалих авторизацій. Адміністратору системи відправлено повідомлення');
            $this->loginform->setVisible(false);

        }

        //  $this->_tvars['alerterror'] = '';
    }

    
    public function beforeRequest() {
        parent::beforeRequest();

        if (System::getUser()->user_id > 0) {
            App::RedirectHome();
        }
    }    
}
