<?php

namespace App\Modules\Tecdoc;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'tecdoc') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectHome();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('prefix', $modules['td_prefix']));
        $form->add(new TextInput('ipath', $modules['td_ipath']));
        
        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        
    }

     
    public function saveOnClick($sender) {
 
        $modules = System::getOptions("modules");

        $modules['td_prefix'] = $this->cform->prefix->getText();
        $modules['td_ipath'] = $this->cform->ipath->getText();
 

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
    }

}
