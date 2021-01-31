<?php

namespace App\Modules\NP;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'np') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('apikey', $modules['npapikey']));
 
        $form->onSubmit($this, 'saveapiOnClick');
        
        $form = $this->add(new Form("oform"));
        $form->add(new DropDownChoice('area' ))->onChange($this,'onArea');
        $form->add(new DropDownChoice('city' ))->onChange($this,'onCity');
        $form->add(new DropDownChoice('point' ));
        $form->add(new TextInput('lastname' ));
        $form->add(new TextInput('firstname' ));
        $form->add(new TextInput('middlename' ));
        $form->add(new TextInput('tel' ));
 
        $form->onSubmit($this, 'savedataOnClick');
        
        
        $this->updateData() ;
        
    }

 

    public function saveapiOnClick($sender) {
        $apikey = $this->cform->apikey->getText();
         
        $modules = System::getOptions("modules");
        
        $modules['npapikey'] = $apikey;

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
        $this->updateData() ;
    }

    public function savedataOnClick($sender) {
        $arearef = $this->oform->area->getValue();
        $area = $this->oform->area->getValueName();
        $cityref = $this->oform->city->getValue();
        $city = $this->oform->city->getValueName();
        $pointref = $this->oform->point->getValue();
        $point = $this->oform->point->getValueName();
        $lastname = $this->oform->lastname->getText();
        $firstnameo = $this->oform->firstname->getText();
        $middlename = $this->oform->middlename->getText();
        $tel = $this->oform->tel->getText();
         
        $modules = System::getOptions("modules");
        
        $modules['nparea']     = $area;
        $modules['nparearef']  = $arearef;
        $modules['npcity']     = $city;
        $modules['npcityref']  = $cityref;
        $modules['nppoint']    = $point;
        $modules['nppointref'] = $pointref;
        $modules['nplastname']      = $lastname;
        $modules['npfirstname']     = $firstname;
        $modules['npmiddlename']    = $middlename;
        $modules['nptel']      = $tel;

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
        
    }
     
    private function  updateData(){
          $modules = System::getOptions("modules");
          if(strlen($modules['npapikey'])==0)  return;

          $this->oform->lastname->setText($modules['nplastname']) ;
          $this->oform->firstname->setText($modules['npfirstname']) ;
          $this->oform->middlename->setText($modules['npmiddlename']) ;
          $this->oform->tel->setText($modules['nptel']) ;
           
          $api =  new  Helper()  ;
       
          $areas = $api->getAreaList() ;
          
          $this->oform->area->setOptionList($areas) ;
     
          $this->oform->area->setValue($modules['nparearef']) ;
          
          if(strlen($modules['nparearef'])>0) {
              $this->onArea($this->oform->area) ;
              $this->oform->city->setValue($modules['npcityref']);
          }
          if(strlen($modules['npcityref'])>0) {
              $this->onCity($this->oform->city) ;
              $this->oform->point->setValue($modules['nppointref']);              
          }
           
    }
    
    public function onArea($sender) {
  
        $api =  new  Helper()  ;
        $list = $api->getCityList($sender->getValueName()) ;
 
         
        $this->oform->city->setOptionList($list) ;
      
    }
    public function onCity($sender) {
  
        $api =  new  Helper()  ;
        $list = $api->getPointList($sender->getValue()) ;
 
         
        $this->oform->point->setOptionList($list) ;
      
    }
    
}
