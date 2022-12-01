<?php

namespace App\Modules\Paperless;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Link\ClickLink;
use Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'paperless') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('email', $modules['plemail']));
        $form->add(new TextInput('password', $modules['plpassword']));
        $form->add(new TextInput('clientid', $modules['plclientid']));
        $form->add(new TextInput('secret', $modules['plsecret']));

        $form->onSubmit($this, 'saveapiOnClick');

        $this->add(new ClickLink("check",$this,"onCheck")) ;
         
    }

    public function saveapiOnClick($sender) {

        $modules = System::getOptions("modules");

        $modules['plemail'] = trim($this->cform->email->getText());
        $modules['plpassword'] = trim($this->cform->password->getText());
        $modules['plclientid'] = trim($this->cform->clientid->getText());
        $modules['plsecret'] =trim($this->cform->secret->getText());

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
         
    }

    public function onCheck($sender) {
        list($code,$result) =        Helper::connect();
        if($code=='ok') {
             $token = $result;
        }
        if($code=='error') {
           $this->setError($result) ;
           
           return;
        }

        $doc = file_get_contents("c:/Users/leonm/Downloads/Підписаний_api.pdf") ;
        
        
         Helper::send($token,$doc) ;

        
    }
  
  
}
