<?php

namespace App\Modules\PU;

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

        if (strpos(System::getUser()->modules, 'promua') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('site', $modules['pusite']));
        $form->add(new TextInput('apitoken', $modules['puapitoken']));
        
        $form->add(new DropDownChoice('defcust', \App\Entity\Customer::getList(), $modules['pucustomer_id'] > 0 ? $modules['pucustomer_id'] : 0));
        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['pupricetype']));

        $form->add(new CheckBox('setpayamount', $modules['pusetpayamount']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');

    }


    public function saveOnClick($sender) {
        $site = $this->cform->site->getText();
        $apitoken = $this->cform->apitoken->getText();
        $setpayamount = $this->cform->setpayamount->isChecked() ? 1 : 0;
        $customer_id = $this->cform->defcust->getValue();
        $pricetype = $this->cform->defpricetype->getValue();
        if ($customer_id == 0) {
            $this->setError('noselcust');
            return;
        }
        if (strlen($pricetype) < 2) {
            $this->setError('noselpricetype');
            return;
        }

        $site = trim($site, '/');

        $modules = System::getOptions("modules");

        $modules['pusite'] = $site;
        $modules['puapitoken'] = $apitoken;
        $modules['pucustomer_id'] = $customer_id;
        $modules['pupricetype'] = $pricetype;
        $modules['pusetpayamount'] = $setpayamount;

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');

      //  \App\Modules\PU\Helper::connect();


    }

}
