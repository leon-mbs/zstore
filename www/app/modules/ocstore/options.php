<?php

namespace App\Modules\OCStore;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct() {
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
        $form->add(new DropDownChoice('defcust', \App\Entity\Customer::getList(), $modules['occustomer_id'] > 0 ? $modules['occustomer_id'] : 0));
        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['ocpricetype']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $form->add(new SubmitButton('check'))->onClick($this, 'checkOnClick');
    }

    public function checkOnClick($sender) {
        $site = $this->cform->site->getText();
        $apiname = $this->cform->apiname->getText();
        $key = $this->cform->key->getText();
        $site = trim($site, '/');

        $url = $site . '/index.php?route=api/login';

        $fields = array(
            'username' => $apiname,
            'key' => $key
        );

        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {

            return;
        }

        $data = json_decode($json, true);
        if ($data == null) {
            $this->setError($json);
            return;
        }
        if (is_array($data) && count($data) == 0) {

            $this->setError('nodataresponse');
            return;
        }

        if (is_array($data['error'])) {
            $this->setError(implode(' ', $data['error']));
        } else {
            if (strlen($data['error']) > 0) {
                $this->setError($data['error']);
            }
        }

        if (strlen($data['success']) > 0) {

            if (strlen($data['api_token']) > 0) { //версия 3
                System::getSession()->octoken = "api_token=" . $data['api_token'];
            }
            if (strlen($data['token']) > 0) { //версия 2.3
                System::getSession()->octoken = "token=" . $data['token'];
            }


            $this->setSuccess('connected');

            //загружаем список статусов
            $url = $site . '/index.php?route=api/zstore/statuses&' . System::getSession()->octoken;
            $json = Helper::do_curl_request($url, array());
            $data = json_decode($json, true);

            if ($data['error'] != "") {
                $this->setError($data['error']);
            } else {

                System::getSession()->statuses = $data['statuses'];
            }
            //загружаем список категорий
            $url = $site . '/index.php?route=api/zstore/cats&' . System::getSession()->octoken;
            $json = Helper::do_curl_request($url, array());
            $data = json_decode($json, true);

            if ($data['error'] != "") {
                $this->setError($data['error']);
            } else {

                System::getSession()->cats = $data['cats'];
            }
        }
    }

    public function saveOnClick($sender) {
        $site = $this->cform->site->getText();
        $apiname = $this->cform->apiname->getText();
        $key = $this->cform->key->getText();
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

        $modules['ocsite'] = $site;
        $modules['ocapiname'] = $apiname;
        $modules['ockey'] = $key;
        $modules['occustomer_id'] = $customer_id;
        $modules['ocpricetype'] = $pricetype;

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
    }

}
