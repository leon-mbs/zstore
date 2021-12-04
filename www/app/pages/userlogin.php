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
        global $_config;

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

        $version =  "v5.6.2";
        $this->_tvars['curversion'] = $version ;

        //проверка  новой версии        
        $this->_tvars['isnewversion'] = false;

        $v = @file_get_contents("https://zippy.com.ua/version.json?t=" . time());
        $v = @json_decode($v, true);
        if (strlen($v['version']) > 0) {
            $c = (int)str_replace(".", "", str_replace("v", "", $version));
            $n = (int)str_replace(".", "", str_replace("v", "", $v['version']));
            if ($n > $c) {
                $this->_tvars['isnewversion'] = true;
            }

            $this->_tvars['newversion'] = $v['version'];
        }

        $this->_tvars['appname'] = $common['shopname'];
        $this->_tvars['capcha'] = $common['capcha'] == 1;
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
                $this->setError("invalidcapcha");
                $this->counter();

                return;
            }
        }
        if ($login == '') {

            $this->setError('enterlogin');
        } else {
            if ($password == '') {

                $this->setError('enterpassword');
            }
        }

        if (strlen($login) > 0 && strlen($password) > 0) {

            $user = Helper::login($login, $password);

            if ($user instanceof User) {
                \App\Session::getSession()->clean();
                $user->lastlogin = time();
                $user->save();
                System::setUser($user);
                $_SESSION['user_id'] = $user->user_id; //для  использования  вне  Application
                $_SESSION['userlogin'] = $user->userlogin; //для  использования  вне  Application
                //App::$app->getResponse()->toBack();
                if ($this->loginform->remember->isChecked()) {

                    setcookie("remember", $user->user_id . '_' . md5($user->user_id . $_config['common']['salt']), time() + 60 * 60 * 24 * 30);
                }
                if ($_COOKIE['branch_id'] > 0) {
                    System::getSession()->defbranch = $_COOKIE['branch_id'];
                }

                if ($_config['modules']['shop'] == 1) {
                    App::Redirect('\App\Pages\Main');
                } else {
                    App::RedirectHome();
                }
                return;
            } else {

                $this->setError('invalidlogin');

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

        $msg = Helper::l($msg);
        $this->_tvars['alerterror'] = $msg;
    }

    private function counter() {
        $this->cntlogin++;
        if ($this->cntlogin == 5) {
            $msg = Helper::l("extralogin");
            $msg .= '<br>' . $this->loginform->userlogin->getText() . ', ';
            $msg .= $_SERVER['HTTP_HOST'] . ' ' . $_SERVER['SERVER_ADDR'];
            $admin = \App\Entity\User::getByLogin('admin');

            $n = new \App\Entity\Notify();
            $n->user_id = \App\Entity\Notify::SYSTEM;
            $n->dateshow = time();
            $n->message = $msg;
            $n->save();

            $n = new \App\Entity\Notify();
            $n->user_id = $admin->user_id;
            $n->sender_id = \App\Entity\Notify::SYSTEM;
            $n->dateshow = time();
            $n->message = $msg;
            $n->save();

            $this->setError('invalidloginalert');
            $this->loginform->setVisible(false);
            if (strlen($admin->email) > 0) {
                Helper::sendLetter($msg, '', $admin->email, "Zippy Store alert");
            }
        }

        //  $this->_tvars['alerterror'] = ''; 
    }

}
