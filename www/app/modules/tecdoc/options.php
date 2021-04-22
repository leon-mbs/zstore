<?php

namespace App\Modules\Tecdoc;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'tecdoc') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));

        $form->add(new TextInput('host', $modules['td_host']));
        $form->add(new TextInput('code', $modules['td_code']));

        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['td_pricetype']));
        $form->add(new DropDownChoice('defstore', \App\Entity\Store::getList(), $modules['td_store']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
    }

    public function saveOnClick($sender) {

        $modules = System::getOptions("modules");

        $modules['td_pricetype'] = $this->cform->defpricetype->getValue();
        $modules['td_store'] = $this->cform->defstore->getValue();

        $modules['td_host'] = $this->cform->host->getText();
        $modules['td_code'] = $this->cform->code->getText();

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
    }

}
