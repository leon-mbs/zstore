<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Application as App;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use App\Entity\Customer;

class UserProfile extends Base
{
    public $c;

    public function __construct() {
        parent::__construct();

        $id = System::getCustomer();

        if ($id == 0) {
            App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Userlogin");
            return;
        }
        $this->c  = Customer::load($id);

        $form = new Form('profileform');
        $form->onSubmit($this, 'onsubmit');
        $form->add(new TextInput('email', $this->c->email));
        $form->add(new TextInput('phone', $this->c->phone));
        $form->add(new TextInput('firstname', $this->c->firstname));
        $form->add(new TextInput('lastname', $this->c->lastname));
        $form->add(new TextArea('address', $this->c->address));
        $this->add($form);



        //форма   пароля

        $form = new Form('passwordform');
        $form->add(new TextInput('userpassword'));
        $form->add(new TextInput('confirmpassword'));
        $form->onSubmit($this, 'onsubmitpass');


    }

    public function onsubmit($sender) {

        $this->c->email = $sender->email->getText();
        $this->c->phone = $sender->phone->getText();
        $this->c->address = $sender->address->getText();
        $this->c->firstname = $sender->firstname->getText();
        $this->c->lastname = $sender->lastname->getText();
        $this->c->customer_name = $this->c->firstname.' '.$this->c->lastname;
        if (strlen($this->c->phone) > 0 && strlen($this->c->phone) != H::PhoneL()) {
            $this->setError("Довжина номера телефона повинна бути ".\App\Helper::PhoneL()." цифр");
            return;
        }
        if (!$this->isError()) {

            $this->c->save();
            $this->setSuccess('Збережено');

        }
    }

    //записать  пароль
    public function onsubmitpass($sender) {

        $pass = $sender->userpassword->getText();
        $confirm = $sender->confirmpassword->getText();

        if ($pass == '') {
            $this->setError('Введіть пароль');
        } else {
            if ($confirm == '') {
                $this->setError('Підтвердіть пароль');
            } else {
                if ($confirm != $pass) {

                    $this->setError('Невірне підтвердження');
                }
            }
        }


        if (!$this->isError()) {
            $this->c->passw = (\password_hash($pass, PASSWORD_DEFAULT));

            $this->c->save();



        }


        $sender->userpassword->setText('');
        $sender->confirmpassword->setText('');
    }


}
