<?php

namespace App\Modules\DF\Public;

use App\Application as App;
use App\Entity\User;
use App\Entity\Customer;
use App\Helper;
use App\System;
use Zippy\Html\Form\TextInput as TextInput;
use Zippy\Html\Link\ClickLink;

class UserLogin extends \Zippy\Html\WebPage
{
    public function __construct() {
        parent::__construct();

        $form = new \Zippy\Html\Form\Form('loginform');
        $form->add(new TextInput('useremail'));
        $form->add(new TextInput('userpassword'));
        $form->add(new \Zippy\Html\Form\CheckBox('remember'));
        $form->add(new ClickLink('recall', $this, 'onRecall'));
      
        $form->onSubmit($this, 'onsubmit');

        $this->add($form);

        $form = new \Zippy\Html\Form\Form('recallform');
        $form->add(new TextInput('rcuseremail'));
        $form->setVisible(false);
        $form->onSubmit($this, 'onrcsubmit');
        $this->add($form);

      


        $this->setError('');
        $this->setSuccess('');
    }

    public function onrcsubmit($sender) {
        $email = $sender->rcuseremail->getText();
     
        $c = Customer::getByEmail($email);
        if ($c == null) {
            $this->setError("Користувач з таким телефоном не знайдений");
            return;
        }
        $p= substr(base64_encode(md5(time())), 0, 8);
        $c->passw = $p;
        $c->save();
        $ret = \App\Comm::sendSMS($phone, "ZStore: новий пароль " . $p);
        if(strlen($ret)  >0) {
            \App\Helper::logerror($ret) ;
            $this->setError('SMS error') ;
            return ;
        }
        $this->setSuccess("Пароль відправлено")  ;
        $this->loginform->setVisible(true) ;
        $this->recallform->setVisible(false) ;

    }

   
    public function onsubmit($sender) {
        global $logger, $_config;
        $df = System::getOptions("df");

        $this->setError('');
        $email = $sender->useremail->getText();
        $password = $sender->userpassword->getText();
       
        $sender->userpassword->setText('');

      
        $c = Customer::getByEmail($email);
        if ($c == null) {
            $this->setError("Користувач з таким email не знайдений");
            return;
        }
        if ( ($c->df ??0 )  ==0) {
            $this->setError("Нема  права  доступу");
            return;
        }
        if (strlen($password)==0 ||  $c->passw != $password) {
            $this->setError("Введіть пароль");
            return;
        }
    
        System::setCustomer($c->customer_id)  ;
        System::getSession()->custname = $c->customer_name;
        if ($sender->remember->isChecked()) {
            setcookie("remembercust", $c->customer_id . '_' . md5($c->customer_id . Helper::getSalt()), time() + 60 * 60 * 24 * 30);
        } else {
            setcookie("remembercust", '', 0);
        }
        App::Redirect("\\App\\Modules\\DF\\Public\\Main", 0);


    }

    public function beforeRequest() {
        parent::beforeRequest();

        if (System::getCustomer()  > 0) {
            App::Redirect("\\App\\Modules\\Modules\\DF\\Public\\Main", 0);

        }
    }

    public function setError($msg, $p=null) {


        $this->_tvars['alerterror'] = $msg;
    }

    public function setSuccess($msg) {

        $this->_tvars['alertsuccess'] = $msg;
    }

    public function onRecall($sender) {

        $this->loginform->setVisible(false) ;
        $this->recallform->setVisible(true) ;
    }

 

}
