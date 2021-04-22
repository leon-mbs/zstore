<?php

namespace App\Modules\Shop\Pages;

use App\Application as App;
use App\Entity\User;
use App\Helper;
use App\System;
use Zippy\Html\Form\TextInput as TextInput;

class UserLogin extends \Zippy\Html\WebPage
{

    public function __construct() {
        parent::__construct();

        $form = new \Zippy\Html\Form\Form('loginform');
        $form->add(new TextInput('userlogin'));
        $form->add(new TextInput('userpassword'));
        $form->add(new \Zippy\Html\Form\CheckBox('remember'));
        $form->onSubmit($this, 'onsubmit');

        $this->add($form);
        $this->setError('');
    }

    public function onsubmit($sender) {
        global $logger, $_config;

        $this->setError('');
        $login = $sender->userlogin->getText();
        $password = $sender->userpassword->getText();
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

                if (strpos($user->modules, 'shop') === false && $user->rolename != 'admins') {
                    System::setErrorMsg('invalidlogin');
                    App::RedirectHome();
                    return;
                }

                $user->lastlogin = time();
                $user->save();

                System::setUser($user);
                $_SESSION['user_id'] = $user->user_id; //для  использования  вне  Application
                $_SESSION['userlogin'] = $user->userlogin; //для  использования  вне  Application
                //App::$app->getResponse()->toBack();
                if ($this->loginform->remember->isChecked()) {

                    setcookie("remember", $user->user_id . '_' . md5($user->user_id . $_config['common']['salt']), time() + 60 * 60 * 24 * 30);
                }

                App::RedirectHome();
            } else {

                $this->setError('invalidlogin');
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

}
