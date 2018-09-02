<?php

namespace App\Pages;

use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use App\System;
use \Zippy\Html\DataList\DataView;

class UserProfile extends \App\Pages\Base
{

    public $user;

    public function __construct() {
        parent::__construct();

        $this->user = System::getUser();

        if ($this->user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }


        //форма   пароля
        $form = new Form('passwordform');
        $form->add(new TextInput('userpassword'));
        $form->add(new TextInput('confirmpassword'));
        $form->onSubmit($this, 'onsubmitpass');
        $this->add($form);
    }

    //записать  пароль
    public function onsubmitpass($sender) {
        $this->setError('');
        $pass = $sender->userpassword->getText();
        $confirm = $sender->confirmpassword->getText();

        if ($pass == '') {
            $this->setError('Введите пароль');
        } else
        if ($confirm == '') {
            $this->setError('Подтвердите пароль');
        } else
        if ($confirm != $pass) {
            $this->setError('Неверное подтверждение');
        }


        if (!$this->isError()) {
            $this->user->userpass = (\password_hash($pass, PASSWORD_DEFAULT));
            $this->user->save();
            $this->setSuccess('Пароль сохранен');
        }

        $sender->userpassword->setText('');
        $sender->confirmpassword->setText('');
    }

}
