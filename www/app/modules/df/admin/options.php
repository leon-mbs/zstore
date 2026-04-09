<?php

namespace App\Modules\DF\Admin;

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

        if (  System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");


        $form = $this->add(new Form("cform"));

        $form->add(new TextInput('discprice', $modules['dfdiscprice']??''));
        

        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['dfpricetype']??'price1'));
        $form->add(new DropDownChoice('defstore', \App\Entity\Store::getListAll() , $modules['dfstore']?? \App\Helper::getDefStore()));
       
      
        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
      
    }
   
    public function saveOnClick($sender) {

    

        $modules = System::getOptions("modules");

        $modules['dfdiscprice'] =  $this->cform->discprice->getText();
        $modules['dfpricetype'] = $this->cform->defpricetype->getValue();
        $modules['dfstore'] = $this->cform->defstore->getValue();
       
        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');

        

    }

}
