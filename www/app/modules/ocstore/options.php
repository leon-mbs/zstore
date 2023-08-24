<?php

namespace App\Modules\OCStore;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use App\Application as App;

class Options extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

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
        $form->add(new CheckBox('setpayamount', $modules['ocsetpayamount']));
        $form->add(new CheckBox('v4', $modules['ocv4']));
        $form->add(new TextArea('key', $modules['ockey']));

        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['ocpricetype']));
        $form->add(new DropDownChoice('salesource', \App\Helper::getSaleSources(), $modules['ocsalesource']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
    }


    public function saveOnClick($sender) {
        $site = $this->cform->site->getText();
        $apiname = $this->cform->apiname->getText();
        $key = $this->cform->key->getText();

        $pricetype = $this->cform->defpricetype->getValue();
        $salesource = $this->cform->salesource->getValue();
        $outcome = $this->cform->outcome->isChecked() ? 1 : 0;
        $ssl = $this->cform->ssl->isChecked() ? 1 : 0;
        $v4 = $this->cform->v4->isChecked() ? 1 : 0;
        $insertcust = $this->cform->insertcust->isChecked() ? 1 : 0;
        $setpayamount = $this->cform->setpayamount->isChecked() ? 1 : 0;

        if (strlen($pricetype) < 2) {

            $this->setError('Не вказано тип ціни');
            return;
        }

        $site = trim($site, '/');

        $modules = System::getOptions("modules");

        $modules['ocsite'] = $site;
        $modules['ocapiname'] = $apiname;
        $modules['ockey'] = $key;

        $modules['ocpricetype'] = $pricetype;
        $modules['ocsalesource'] = $salesource;
        $modules['ocssl'] = $ssl;
        $modules['ocv4'] = $v4;
        $modules['ocinsertcust'] = $insertcust;
        $modules['ocoutcome'] = $outcome;
        $modules['ocsetpayamount'] = $setpayamount;

        System::setOptions("modules", $modules);


        $this->setSuccess('Збережено');

        \App\Modules\OCStore\Helper::connect();


    }

}
