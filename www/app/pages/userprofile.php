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
        $form->add(new DropDownChoice('defstore', \App\Entity\Store::getList(), $this->user->defstore));
        $form->add(new DropDownChoice('defmf', \App\Entity\MoneyFund::getList(), $this->user->defmf));
        $form->add(new DropDownChoice('pagesize', array(15 => 15, 25 => 25, 50 => 50, 100 => 100, 200 => 200), $this->user->pagesize));

        $w = "";
        if ($this->user->acltype == 2) {
            if (strlen($this->user->aclview) > 0) {
                $w = " and meta_id in ({$this->user->aclview})";
            } else {
                $w = " and meta_id in (0)";
            }
        }

        $form->add(new DataView('mlist', new \ZCL\DB\EntityDataSource("\\App\\Entity\\MetaData", "disabled<>1  {$w}", "meta_type,description"), $this, 'metarowOnRow'));

        $this->add($form);
        $form->mlist->Reload();

        //форма   пароля

        $form = new Form('passwordform');
        $form->add(new TextInput('userpassword'));
        $form->add(new TextInput('confirmpassword'));
        $form->onSubmit($this, 'onsubmitpass');
        $this->add($form);

        $this->add(new Form('msgform'))->onSubmit($this, 'OnSend');
        $this->msgform->add(new TextArea('msgtext'));
        $this->msgform->add(new DropDownChoice('users', \App\Entity\User::findArray('username', 'disabled <> 1 and user_id <>' . $this->user->user_id, 'username'), 0));
        $this->msgform->add(new CheckBox('sendall'))->setVisible($this->user->username == 'admin');
    }

    public function onsubmit($sender) {

        $this->user->email = $sender->email->getText();
        $this->user->defstore = $sender->defstore->getValue();
        $this->user->defmf = $sender->defmf->getValue();
        $this->user->pagesize = $sender->pagesize->getValue();

        $smartmenu = array();

        foreach ($sender->mlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->mview == true)
                $smartmenu[] = $item->meta_id;
        }
        $this->user->smartmenu = implode(',', $smartmenu);



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
            $admin = \App\Entity\User::getByLogin('admin');
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


        $all = $sender->sendall->isChecked();

        $list = array();
        if ($all) {
            foreach ($sender->users->getOptionList() as $id => $n) {
                $list[] = $id;
            }
        } else {
            $id = $sender->users->getValue();
            if ($id == 0) {
                $this->setError('Не  выбран  получатель');
                return;
            }
            $list[] = $id;
        }


        foreach ($list as $id) {
            $n = new \App\Entity\Notify();
            $n->user_id = $id;
            $n->message .= $text;
            $n->sender_name = $this->user->username;
            $n->save();
        }
        $this->setSuccess('Отправлено');
        $sender->clean();
    }

    public function metarowOnRow($row) {
        $item = $row->getDataItem();
        switch ($item->meta_type) {
            case 1:
                $title = "Документ";
                break;
            case 2:
                $title = "Отчет";
                break;
            case 3:
                $title = "Журнал";
                break;
            case 4:
                $title = "Справочник";
                break;
            case 5:
                $title = "Сервис ";
                break;
        }
        $smartmenu = @explode(',', $this->user->smartmenu);
        if (is_array($smartmenu)) {
            $item->mview = in_array($item->meta_id, $smartmenu);
        }


        $row->add(new Label('meta_desc', $item->description));
        $row->add(new Label('meta_name', $title));

        $row->add(new CheckBox('mshow', new Bind($item, 'mview')));
    }

}
