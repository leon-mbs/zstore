<?php

namespace App\Pages;

use App\Application as App;
use App\Entity\User;
use App\Helper;
use App\System;
use Zippy\Html\Form\TextInput as TextInput;

class UserLogin extends \Zippy\Html\WebPage
{
    private $cntlogin = 0;

    public function __construct() {
        parent::__construct();

        System::clean() ;
        System::getSession()->clean();


        $common = System::getOptions('common');
        \App\Session::getSession()->clipboard = null;

        $form = new \Zippy\Html\Form\Form('loginform');
        $form->add(new TextInput('userlogin'));
        $form->add(new TextInput('userpassword'));
        $form->add(new TextInput('capchacode'));

        $form->add(new \Zippy\Html\Form\CheckBox('remember'));
        $form->add(new \ZCL\Captcha\Captcha('capcha'));
        $form->onSubmit($this, 'onsubmit');

        $this->add($form);
        $this->setError('');


        $this->_tvars['curversion'] = \App\System::CURR_VERSION ;


        $this->_tvars['appname'] = $common['shopname'];
        $this->_tvars['capcha'] = $common['capcha'] == 1;

        $this->_tvars['cron']  =  \App\System::useCron() ;
          
    }

    public function onsubmit($sender) {
        global $logger, $_config;

        $this->setError('');
        $login = $sender->userlogin->getText();
        $password = $sender->userpassword->getText();
        $sender->userpassword->setText('');
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

            $user = Helper::login($login, $password);

            if ($user instanceof User) {
                \App\Session::getSession()->clean();
                $user->lastactive = time();
                $user->save();
                System::setUser($user);
                $_SESSION['user_id'] = $user->user_id; //для  использования  вне  Application
                $_SESSION['userlogin'] = $user->userlogin; //для  использования  вне  Application
                //App::$app->getResponse()->toBack();
                if ($this->loginform->remember->isChecked()) {
                    setcookie("remember", $user->user_id . '_' . md5($user->user_id . Helper::getSalt()), time() + 60 * 60 * 24 * 14);
                } else {
                    setcookie("remember", '', 0);
                }
                if (($_COOKIE['branch_id'] ?? 0) > 0) {
                    System::getSession()->defbranch = $_COOKIE['branch_id'];
                }
             
  
                if($user->rolename=='admins'   ){
                    $b=0;
                    $phpv =   phpversion()  ;

                    $v = @file_get_contents("https://zippy.com.ua/version.json" );
                    $data = @json_decode($v, true);
                    if(is_array($data)){
                       $b= version_compare($data['version'] , System::CURR_VERSION);
                    }               
                         
                    if(  $b==1 ){
                        $lastshow=intval(Helper::getKeyVal('lastshowupdate')) ;
                        if(strtotime('-7 day') > $lastshow ) {
                            Helper::setKeyVal('lastshowupdate',time()) ;
                            App::Redirect('\App\Pages\Update');
                            return;   
                        }
                    }
                }
                
                $modules = \App\System::getOptions("modules");

                if ($modules['shop'] == 1) {
                    App::Redirect('\App\Pages\Main');
                } else {
                    App::RedirectHome();
                }
                return;
            } else {

                $this->setError('Невірний логін');

                $this->counter();
            }
        }

        $sender->userpassword->setText('');
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
        $this->cntlogin++;
        if ($this->cntlogin == 5) {
            $msg = "Багато невдалих авторизацій";
            $t = $this->loginform->userlogin->getText()  ;
            $t = htmlspecialchars($t) ;
            $msg .= '<br>' . $t. ', ';
            $msg .= $_SERVER['HTTP_HOST'] . ' ' . $_SERVER['SERVER_ADDR'];

            \App\Entity\Notify::toSystemLog($msg) ;
            \App\Entity\Notify::toAdmin($msg) ;


            $this->setError('Багато невдалих авторизацій. Адміністратору системи відправлено повідомлення');
            $this->loginform->setVisible(false);

        }

        //  $this->_tvars['alerterror'] = '';
    }

}
