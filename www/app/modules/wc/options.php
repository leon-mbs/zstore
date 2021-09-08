<?php

namespace App\Modules\WC;

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

        if (strpos(System::getUser()->modules, 'woocomerce') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('site', $modules['wcsite']));
        $form->add(new TextInput('keyc', $modules['wckeyc']));
        $form->add(new TextInput('keys', $modules['wckeys']));
        $form->add(new DropDownChoice('defcust', \App\Entity\Customer::getList(), $modules['wccustomer_id'] > 0 ? $modules['occustomer_id'] : 0));
        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['wcpricetype']));
        $form->add(new DropDownChoice('api', array('v3' => 'v3', 'v2' => 'v2', 'v1' => 'v1'), $modules['wcapi']));
        $form->add(new CheckBox('ssl', $modules['wcssl']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        
    }

  

    public function saveOnClick($sender) {
        $site = $this->cform->site->getText();
        $keyc = $this->cform->keyc->getText();
        $keys = $this->cform->keys->getText();
        $api = $this->cform->api->getValue();
        $ssl = $this->cform->ssl->isChecked() ? 1 : 0;
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

        $modules['wcsite'] = $site;
        $modules['wckeyc'] = $keyc;
        $modules['wckeys'] = $keys;
        $modules['wcapi'] = $api;
        $modules['wccustomer_id'] = $customer_id;
        $modules['wcpricetype'] = $pricetype;
        $modules['wcssl'] = $ssl;

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
        
        \App\Modules\WC\Helper::connect()  ;
         
        
    }

}
