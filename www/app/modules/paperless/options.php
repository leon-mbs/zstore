<?php

namespace App\Modules\Paperless;

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

         
    }

    public function saveapiOnClick($sender) {

        $modules = System::getOptions("modules");

        $modules['plemail'] = $this->cform->email->getText();
        $modules['plpassword'] = $this->cform->password->getText();
        $modules['plclientid'] = $this->cform->clientid->getText();
        $modules['plsecret'] = $this->cform->secret->getText();

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
         
    }

    public function savedataOnClick($sender) {
        $arearef = $this->oform->area->getValue();
        $area = $this->oform->area->getValueName();
        $cityref = $this->oform->city->getValue();
        $city = $this->oform->city->getValueName();
        $pointref = $this->oform->point->getValue();
        $point = $this->oform->point->getValueName();

        $modules = System::getOptions("modules");

        $modules['nparea'] = $area;
        $modules['nparearef'] = $arearef;
        $modules['npcity'] = $city;
        $modules['npcityref'] = $cityref;
        $modules['nppoint'] = $point;
        $modules['nppointref'] = $pointref;
        $modules['nptel'] = $this->oform->tel->getText();

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
    }

  
}
