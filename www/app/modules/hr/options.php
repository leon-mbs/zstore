<?php

namespace App\Modules\HR;

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

        if (strpos(System::getUser()->modules, 'horoshop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");


        $form = $this->add(new Form("cform"));

        $form->add(new TextInput('site', $modules['hrsite']));
        $form->add(new TextInput('login', $modules['hrlogin']));
        $form->add(new TextInput('password', $modules['hrpassword']));


        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['hrpricetype']));
        $form->add(new DropDownChoice('defmf',\App\Entity\MoneyFund::getList(), $modules['hrmf']??0));
        $form->add(new DropDownChoice('defstore',\App\Entity\Store::getList(), $modules['hrstore']??0));
      
        $pt=[];
        $pt[1] = 'Оплата зразу (передплата)';
        $pt[2] = 'Постоплата';
        $pt[3] = 'Оплата в Чеку або ВН';
        
        $form->add(new DropDownChoice('defpaytype',$pt, $modules['hrpaytype']??0));



        $form->add(new DropDownChoice('salesource', \App\Helper::getSaleSources(), $modules['hrsalesource']));
        $form->add(new CheckBox('ssl', $modules['hrssl']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $form->add(new CheckBox('insertcust', $modules['hrinsertcust']));

    }
    //584ac4cc9096eb799cf6664ce977b22c6f463cba

    public function saveOnClick($sender) {

        $site = $this->cform->site->getText();
        $login = $this->cform->login->getText();
        $password = $this->cform->password->getText();
      
        $pricetype = $this->cform->defpricetype->getValue();
        $mf = $this->cform->defmf->getValue();
        $store = $this->cform->defstore->getValue();
        $paytype = intval($this->cform->defpaytype->getValue() );
        $salesource = $this->cform->salesource->getValue();
        $insertcust = $this->cform->insertcust->isChecked() ? 1 : 0;

        if (strlen($pricetype) < 2) {
            $this->setError('Не вказано тип ціни');
            return;
        }
        if ( $paytype==0) {

            $this->setError('Не вказано тип оплати');
            return;
        }
        if ( $paytype==1 && $mf==0) {

            $this->setError('Не вказано касу');
            return;
        }
        $site = trim($site, '/');

        $modules = System::getOptions("modules");

        $modules['hrsite'] = $site;
        $modules['hrlogin'] = $login;
        $modules['hrpassword'] = $password;

        $modules['hrmf'] = $mf;
        $modules['hrstore'] = $store;
        $modules['hrpaytype'] = $paytype;
        $modules['hrpricetype'] = $pricetype;
        $modules['hrsalesource'] = $salesource;
        $modules['hrinsertcust'] = $insertcust;
        $modules['hrssl'] = $this->cform->ssl->isChecked() ? 1 : 0;

        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');

        $token=  \App\Modules\HR\Helper::connect();
        if(strlen($token)>0) {
           $this->setSuccess('Успішне з`єднання ');
        }

    }

}
