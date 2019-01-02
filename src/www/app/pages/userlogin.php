<?php

namespace App\Pages;

use \Zippy\Binding\PropertyBinding as Bind;
use \Zippy\Html\Form\TextInput as TextInput;
use \App\Application as App;
use \App\Helper;
use \App\System;
use \App\Entity\User;
use \Zippy\Html\Label;

class UserLogin extends \Zippy\Html\WebPage
{

    public $_errormsg;

    public function __construct() {
        parent::__construct();

        $form = new \Zippy\Html\Form\Form('loginform');
        $form->add(new TextInput('userlogin'));
        $form->add(new TextInput('userpassword'));
        $form->add(new \Zippy\Html\Form\CheckBox('remember'));
        $form->onSubmit($this, 'onsubmit');

        $this->add($form);
    }

    public function onsubmit($sender) {
        global $logger, $_config;

        $this->setError('');
        $login = $sender->userlogin->getText();
        $password = $sender->userpassword->getText();
        if ($login == '') {
            $this->setError('Введите логин');
        } else
        if ($password == '') {
            $this->setError('Введите пароль');
        }

        if (strlen($login) > 0 && strlen($password) > 0) {

            $user = Helper::login($login, $password);

            if ($user instanceof User) {
                $user->lastlogin = time();
                $user->save();
                System::setUser($user);
                $_SESSION['user_id'] = $user->user_id; //для  использования  вне  Application
                $_SESSION['userlogin'] = $user->userlogin; //для  использования  вне  Application
                //App::$app->getResponse()->toBack();
                if ($this->loginform->remember->isChecked()) {

                    setcookie("remember", $user->user_id . '_' . md5($user->user_id . $_config['common']['salt']), time() + 60 * 60 * 24 * 30);
                }
                $logger->info('Logined ' . $user->userlogin . ', ' . $_SERVER["REMOTE_ADDR"]);
                if (\App\Session::getSession()->topage == null) {
                    App::RedirectURI('/store');
                } else {
                    App::RedirectURI(\App\Session::getSession()->topage);
                }
            } else {
                $this->setError('Неверный логин');
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
        $this->_errormsg = $msg;
    }

    protected function afterRender() {

        if (strlen($this->_errormsg) > 0)
            App::$app->getResponse()->addJavaScript("toastr.error('{$this->_errormsg}')        ", true);

        $this->setError('');
    }

}
