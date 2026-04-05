<?php

namespace App\Modules\DF\Public;

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

       
       
        $form = new Form('profileform');
        $form->onSubmit($this, 'onsubmit');
        $form->add(new TextInput('email', $this->_c->email));
    
        $form->add(new TextInput('customer_name', $this->_c->customer_name));
   
        $this->add($form);



        //форма   пароля

        $form = new Form('passwordform');
        $form->add(new TextInput('userpassword'));
        $form->add(new TextInput('confirmpassword'));
        $form->onSubmit($this, 'onsubmitpass');


    }

    public function onsubmit($sender) {

        $this->_c->email = $sender->email->getText();
        $this->_c->customer_name = $sender->customer_name->getText();
     
        if (!$this->isError()) {

            $this->_c->save();
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
            $this->_c->passw = (\password_hash($pass, PASSWORD_DEFAULT));

            $this->_c->save();



        }


        $sender->userpassword->setText('');
        $sender->confirmpassword->setText('');
    }


}
