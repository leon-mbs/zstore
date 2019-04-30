<?php

namespace App\Modules\OCStore;

use \App\System;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\TextInput;
use \Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct()
    {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('Нет права доступа к  странице');

            App::RedirectHome();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('site', $modules['ocsite']));
        $form->add(new TextInput('apiname', $modules['ocapiname']));
        $form->add(new TextArea('key', $modules['ockey']));
        $form->add(new DropDownChoice('defcust', \App\Entity\Customer::getList()));
        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList()));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $form->add(new SubmitButton('check'))->onClick($this, 'checkOnClick');

    }

    public function checkOnClick($sender)
    {
        $site = $this->cform->site->getText();
        $apiname = $this->cform->apiname->getText();
        $key = $this->cform->key->getText();
        $site = trim($site, '/');

        $url = $site . '/index.php?route=api/login';

        $fields = array(
            'username' => $apiname,
            'key' => $key,
        );

        $json = Helper::do_curl_request($url, $fields);
        $data = json_decode($json);

        if (strlen($data->error) > 0) {
            $this->setError($data->error);
        }

        if (strlen($data->success) > 0) {

            if (strlen($data->api_token) > 0) { //версия 3
                System::getSession()->octoken = "api_token=" . $data->api_token;
            }
            if (strlen($data->token) > 0) { //версия 2.3
                System::getSession()->octoken = "token=" . $data->token;
            }

            $this->setSuccess('Соединение успешно');

            $url = $site . '/index.php?route=api/zstore/statuses/3&' . System::getSession()->octoken;
            $json = Helper::do_curl_request($url, array());
            $data = json_decode($json);

            if ($data->error != "") {
                $this->setError($data->error);
            } else {
                $statuses = array();
                foreach ($data->statuses as $st) {
                    $statuses[$st->order_status_id] = $st->name;
                }
                System::getSession()->statuses = $statuses;
            }

        }
    }

    public function saveOnClick($sender)
    {
        $site = $this->cform->site->getText();
        $apiname = $this->cform->apiname->getText();
        $key = $this->cform->key->getText();
        $customer_id = $this->cform->defcust->getValue();
        $pricetype = $this->cform->defpricetype->getValue();
        if ($customer_id == 0) {
            $this->setError('Не задан  контрагент');
            return;
        }
        if ($pricetype == 0) {
            $this->setError('Не указан тип  цены');
            return;
        }

        $site = trim($site, '/');

        $modules = System::getOptions("modules");

        $modules['ocsite'] = $site;
        $modules['ocapiname'] = $apiname;
        $modules['ockey'] = $key;
        $modules['occustomer_id'] = $customer_id;
        $modules['ocpricetype'] = $pricetype;

        System::setOptions("modules", $modules);
        $this->setSuccess('Сохранено');
    }
}
