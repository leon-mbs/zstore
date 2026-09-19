<?php

namespace App\Pages;

use App\Application as App;
use App\Entity\User;
use App\Helper;
use App\System;
use Zippy\Html\Form\TextInput ;

class UserLogin extends \Zippy\Html\WebPage
{
    private $_user = null;
    private $_otpcode = null;
    private $_otptime = null;
    private $_cntlogin = 0;

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

        //другий крок двофакторної авторизації
        $otpform = new \Zippy\Html\Form\Form('otpform');
        $otpform->add(new TextInput('otpinput'));
        $otpform->add(new \Zippy\Html\Link\ClickLink('otpback'))->onClick($this, 'otpbackOnClick');
        $otpform->onSubmit($this, 'onotpsubmit');
        $otpform->setVisible(false);
        $this->add($otpform);
        $this->setError('');


      //  $this->_tvars['curversion'] = \App\System::CURR_VERSION ;


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

        if (strlen($login) > 0 && strlen($password) > 0) {
             
            $this->_user = Helper::login($login, $password);

            if ($this->_user instanceof User) {
                if ($this->_user->otpEnabled()) {
                    $this->_otpcode = (string) rand(100000, 999999);
                    $this->_otptime = time();
                    $errors = $this->_user->sendOtp($this->_otpcode);
                    if (count($errors) > 0) {
                        Helper::log('OTP send failed for ' . $this->_user->userlogin . ': ' . implode('; ', $errors));
                        $this->setError('Не вдалося надіслати код підтвердження: ' . implode('; ', $errors) . '. Можна ввести резервний код з профілю.');
                    }
                    $this->loginform->setVisible(false);
                    $this->otpform->setVisible(true);
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

        $sender->userpasswo->setText('');
    }

    //перевірка коду підтвердження (одноразовий, дійсний 10 хвилин) або резервного коду з профілю
    public function onotpsubmit($sender) {
        $this->setError('');
        $code = trim($sender->otpinput->getText());
        $sender->otpinput->setText('');
        if (!($this->_user instanceof User)) {
            $this->otpbackOnClick(null);
            return;
        }
        if (strlen($code) == 0) {
            $this->setError('Введіть код підтвердження');
            return;
        }
        $fresh = strlen($this->_otpcode ?? '') > 0 && $this->_otptime > time() - 600 && hash_equals((string) $this->_otpcode, $code);
        $backup = strlen($this->_user->otpcode ?? '') > 0 && hash_equals((string) $this->_user->otpcode, $code);
        if (!$fresh && !$backup) {
            $this->setError('Невірний код підтвердження');
            $this->counter();
            return;
        }
        $this->_otpcode = null;
        $this->_otptime = null;
        $this->successLogin();
    }

    public function otpbackOnClick($sender) {
        $this->_user = null;
        $this->_otpcode = null;
        $this->_otptime = null;
        $this->setError('');
        $this->otpform->setVisible(false);
        $this->loginform->setVisible(true);
    }

    public function successLogin() {
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
 
    public function beforeRequest() {
        parent::beforeRequest();

        if (System::getUser()->user_id > 0) {
            App::RedirectHome();
        }
    }

    public function setError($msg) {


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

}
