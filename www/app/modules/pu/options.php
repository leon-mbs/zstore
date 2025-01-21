<?php

namespace App\Modules\PU;

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

        if (strpos(System::getUser()->modules, 'promua') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");


        $form = $this->add(new Form("cform"));

        $form->add(new TextInput('apitoken', $modules['puapitoken']));


        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['pupricetype']));
        $form->add(new DropDownChoice('defmf',\App\Entity\MoneyFund::getList(), $modules['pumf']??0));


        $form->add(new CheckBox('setpayamount', $modules['pusetpayamount']));
        $form->add(new DropDownChoice('salesource', \App\Helper::getSaleSources(), $modules['pusalesource']));
        $form->add(new CheckBox('ssl', $modules['pussl']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $form->add(new CheckBox('insertcust', $modules['puinsertcust']));

    }
    //584ac4cc9096eb799cf6664ce977b22c6f463cba

    public function saveOnClick($sender) {

        $apitoken = $this->cform->apitoken->getText();
        $setpayamount = $this->cform->setpayamount->isChecked() ? 1 : 0;

        $pricetype = $this->cform->defpricetype->getValue();
        $defmf = $this->cform->defmf->getValue();
        $salesource = $this->cform->salesource->getValue();
        $insertcust = $this->cform->insertcust->isChecked() ? 1 : 0;

        if (strlen($pricetype) < 2) {
            $this->setError('Не вказано тип ціни');
            return;
        }

        //   $site = trim($site, '/');

        $modules = System::getOptions("modules");

        // $modules['pusite'] = "http://my.prom.ua/";
        $modules['puapitoken'] = $apitoken;

        $modules['pupricetype'] = $pricetype;
        $modules['pumf'] = $defmf;
        $modules['pusalesource'] = $salesource;
        $modules['pusetpayamount'] = $setpayamount;
        $modules['puinsertcust'] = $insertcust;
        $modules['pussl'] = $this->cform->ssl->isChecked() ? 1 : 0;

        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');

        \App\Modules\PU\Helper::connect();


    }

}
