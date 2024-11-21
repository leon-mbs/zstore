<?php

namespace App\Modules\Tecdoc;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\WebApplication as App;
use Zippy\Html\Link\ClickLink;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'tecdoc') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectHome();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));

        $form->add(new TextInput('ipath', $modules['td_ipath']));
        $form->add(new TextInput('dbhost', $modules['td_dbhost']));
        $form->add(new TextInput('dbname', $modules['td_dbname']));
        $form->add(new TextInput('dbuser', $modules['td_dbuser']));
        $form->add(new TextInput('dbpass', $modules['td_dbpass']));

        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['td_pricetype']));
        $form->add(new DropDownChoice('defstore', \App\Entity\Store::getList(), $modules['td_store']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $form->add(new ClickLink('chconn'))->onClick($this, 'chconnOnClick');

    }


    public function chconnOnClick($sender) {
       try{
           $api = new DBHelper();
           $this->setSuccess("З'єднання успiшно") ;
       } catch (\Exception $e){
            $this->setError($e->getMessage()) ; 
       }
    }
    
    public function saveOnClick($sender) {

        $modules = System::getOptions("modules");

        $modules['td_pricetype'] = $this->cform->defpricetype->getValue();
        $modules['td_store'] = $this->cform->defstore->getValue();
        $modules['td_ipath'] = $this->cform->ipath->getText();
        $modules['td_dbhost'] = $this->cform->dbhost->getText();
        $modules['td_dbname'] = $this->cform->dbname->getText();
        $modules['td_dbuser'] = $this->cform->dbuser->getText();
        $modules['td_dbpass'] = $this->cform->dbpass->getText();



        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
    }

}
