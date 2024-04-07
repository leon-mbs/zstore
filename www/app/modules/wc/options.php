<?php

namespace App\Modules\WC;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use App\Application as App;

class Options extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'woocomerce') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('site', $modules['wcsite']));
        $form->add(new TextInput('keyc', $modules['wckeyc']));
        $form->add(new TextInput('keys', $modules['wckeys']));
        $form->add(new CheckBox('insertcust', $modules['wcinsertcust']));
        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['wcpricetype']));
        $form->add(new DropDownChoice('api', array('v3' => 'v3', 'v2' => 'v2', 'v1' => 'v1'), $modules['wcapi']));
        $form->add(new CheckBox('ssl', $modules['wcssl']));
        $form->add(new CheckBox('setpayamount', $modules['wcsetpayamount']));
        $form->add(new DropDownChoice('salesource', \App\Helper::getSaleSources(), $modules['wcsalesource']));
        $form->add(new DropDownChoice('defmf',\App\Entity\MoneyFund::getList(), $modules['wcmf']??0));


        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');

    }


    public function saveOnClick($sender) {
        $site = $this->cform->site->getText();
        $keyc = $this->cform->keyc->getText();
        $keys = $this->cform->keys->getText();
        $api = $this->cform->api->getValue();
        $ssl = $this->cform->ssl->isChecked() ? 1 : 0;
        $setpayamount = $this->cform->setpayamount->isChecked() ? 1 : 0;
        $insertcust = $this->cform->insertcust->isChecked() ? 1 : 0;

        $pricetype = $this->cform->defpricetype->getValue();
        $defmf = $this->cform->defmf->getValue();
        $salesource = $this->cform->salesource->getValue();

        if (strlen($pricetype) < 2) {
            $this->setError('Не вказано тип ціни');
            return;
        }

        $site = trim($site, '/');

        $modules = System::getOptions("modules");

        $modules['wcsite'] = $site;
        $modules['wckeyc'] = $keyc;
        $modules['wckeys'] = $keys;
        $modules['wcapi'] = $api;
        $modules['wcinsertcust'] = $insertcust;

        $modules['wcpricetype'] = $pricetype;
        $modules['wcmf'] = $defmf;
        $modules['wcsalesource'] = $salesource;
        $modules['wcssl'] = $ssl;
        $modules['wcsetpayamount'] = $setpayamount;

        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');

        \App\Modules\WC\Helper::connect();


    }

}
// ck_30c7fa4fb5f46185ac948add02c7da923de66059
// cs_2d9b3be72c78dc6752908205520abd0305cc3378