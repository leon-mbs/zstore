<?php

namespace App\Modules\Paperless;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Link\ClickLink;
use App\Application as App;

class Options extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'paperless') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('clientid', $modules['plclientid']));
        $form->add(new TextInput('secret', $modules['plsecret']));

        $form->onSubmit($this, 'saveapiOnClick');

        $this->add(new ClickLink("check", $this, "onCheck")) ;

    }

    public function saveapiOnClick($sender) {

        $modules = System::getOptions("modules");

        $modules['plclientid'] = trim($this->cform->clientid->getText());
        $modules['plsecret'] =trim($this->cform->secret->getText());

        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');

    }

    public function onCheck($sender) {
        list($code, $result) =        Helper::connect();
        if($code=='ok') {
            System::getSession()->pltoken = $result;
            $this->setSuccess("Успішне з`єднання") ;
        }
        if($code=='error') {
            $this->setError($result) ;

            return;
        }

        //  $docdata = file_get_contents("c:/Users/leonm/Downloads/Підписаний_api.pdf") ;


        //  list($code,$result) =    Helper::send(System::getSession()->pltoken,$docdata,"Підписаний_api.pdf","softman@ukr.net") ;
        //   if($code =='ok'){

        //   }

    }


}
