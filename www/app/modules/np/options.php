<?php

namespace App\Modules\NP;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'np') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectHome();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('apiurl', $modules['npapiurl']));
 
        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $form->add(new SubmitButton('check'))->onClick($this, 'checkOnClick');
    }

    public function checkOnClick($sender) {
        $apiurl = $this->cform->apiurl->getText();
  

      

        $this->setSuccess('connected');
    }

    public function saveOnClick($sender) {
        $apiurl = $this->cform->apiurl->getText();
 
   
   

        $modules = System::getOptions("modules");

        $modules['npapiurl'] = $apiurl;
 

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
    }

}
