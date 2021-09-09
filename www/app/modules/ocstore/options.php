<?php

namespace App\Modules\OCStore;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('site', $modules['ocsite']));
        $form->add(new TextInput('apiname', $modules['ocapiname']));
        $form->add(new CheckBox('ssl', $modules['ocssl']));
        $form->add(new CheckBox('outcome', $modules['ocoutcome']));
        $form->add(new CheckBox('insertcust', $modules['ocinsertcust']));
        $form->add(new TextArea('key', $modules['ockey']));
        $form->add(new DropDownChoice('defcust', \App\Entity\Customer::getList(), $modules['occustomer_id'] > 0 ? $modules['occustomer_id'] : 0));
        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['ocpricetype']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
     }

 
    public function saveOnClick($sender) {
        $site = $this->cform->site->getText();
        $apiname = $this->cform->apiname->getText();
        $key = $this->cform->key->getText();
        $customer_id = $this->cform->defcust->getValue();
        $pricetype = $this->cform->defpricetype->getValue();
        $outcome = $this->cform->outcome->isChecked() ? 1 : 0;
        $ssl = $this->cform->ssl->isChecked() ? 1 : 0;
        $insertcust = $this->cform->insertcust->isChecked() ? 1 : 0;
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

        $modules['ocsite'] = $site;
        $modules['ocapiname'] = $apiname;
        $modules['ockey'] = $key;
        $modules['occustomer_id'] = $customer_id;
        $modules['ocpricetype'] = $pricetype;
        $modules['ocssl'] = $ssl;
        $modules['ocinsertcust'] = $insertcust;
        $modules['ocoutcome'] = $outcome;

        System::setOptions("modules", $modules);
        
        
        $this->setSuccess('saved');
        
        \App\Modules\OCStore\Helper::connect()  ;
        
        
        
    }

}
