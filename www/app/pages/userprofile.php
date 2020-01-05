<?php

namespace App\Pages;

use \Zippy\Binding\PropertyBinding as Bind;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Label;
use \App\System;
use \Zippy\Html\DataList\DataView;

class UserProfile extends \App\Pages\Base {

    public $user;

    public function __construct() {
        parent::__construct();

        $this->user = System::getUser();

        if ($this->user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

        $form = new Form('profileform');
        $form->onSubmit($this, 'onsubmit');
        $form->add(new Label('userlogin', $this->user->userlogin));
        $form->add(new TextInput('email', $this->user->email));
        $form->add(new DropDownChoice('defstore', \App\Entity\Store::getList(),$this->user->defstore));
        $form->add(new DropDownChoice('defmf', \App\Entity\MoneyFund::getList(),$this->user->defmf));
        
        $this->add($form);
  

        //форма   пароля

        $form = new Form('passwordform');
        $form->add(new TextInput('userpassword'));
        $form->add(new TextInput('confirmpassword'));
        $form->onSubmit($this, 'onsubmitpass');
        $this->add($form);
        
        
      
        $this->add(new Form('msgform'))->onSubmit($this, 'OnSend');
        $this->msgform->add(new TextArea('msgtext'));
        $this->msgform->add(new DropDownChoice('users', \App\Entity\User::findArray('username','disabled <> 1 and user_id <>'.$this->user->user_id,'username'),0));
           
        
    }

    public function onsubmit($sender) {

        $this->user->email = $sender->email->getText();
        $this->user->defstore = $sender->defstore->getValue();
        $this->user->defmf = $sender->defmf->getValue();

        if (!$this->isError()) {
            $this->user->save();
            $this->setSuccess('Изменения сохранены');
            System::setUser($this->user);
        }
    }

    //записать  пароль
    public function onsubmitpass($sender) {

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

        if ($this->user->username != 'admin') {
            $admin = \App\Entity\User::getFirst("username='admin'");
            $n = new \App\Entity\Notify();
            $n->user_id = $admin->user_id;
            $n->dateshow = time();
            $n->message = "Пользователь <b>{$this->user->username}</b> сменил пароль на  <b>{$pass}</b>";

            $n->save();
        }




        $sender->userpassword->setText('');
        $sender->confirmpassword->setText('');
    }
  
  
    public function OnSend($sender) {
        $msg = trim($sender->msgtext->getText());
        if (strlen($msg) == 0)
            return;

        $from = System::getUser();

        $n = new \App\Entity\Notify();
        $n->user_id = $this->user->user_id;
        $n->message = "Сообщение от пользователя <b>{$from->username}</b> <br><br>";
        $n->message .= $msg;

        $n->save();

        $sender->msgtext->setText('') ;
        $this->setInfo('Отправлено');
    }

}
