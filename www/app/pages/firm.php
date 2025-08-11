<?php

namespace App\Pages;

 
use App\Helper as H;
use App\System;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource ;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Application as App;

class Firm extends \App\Pages\Base
{
     

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

        $firm = System::getOptions("firm");
        if (count($firm)==0) {
            $firm = H::getFirmData() ;
        }
        $this->add(new Form('firmform'))->onSubmit($this, 'OnSubmit');
        $this->firmform->add(new TextInput('firm_name',$firm['firm_name']));
        $this->firmform->add(new TextInput('inn',$firm['inn']));
        $this->firmform->add(new TextInput('tin',$firm['tin']));
        $this->firmform->add(new TextInput('phone',$firm['phone']));
        $this->firmform->add(new TextInput('address',$firm['address']));
        $this->firmform->add(new TextInput('city',$firm['address']));
        $this->firmform->add(new TextInput('vdoc',$firm['vdoc']));
      
        $this->firmform->add(new TextInput('sign',$firm['sign']));
        $this->firmform->add(new TextInput('stamp',$firm['stamp']));
        $this->firmform->add(new TextInput('logo',$firm['logo']));

        $this->firmform->add(new TextInput('koatuu',$firm['koatuu']));
        $this->firmform->add(new TextInput('kopfg',$firm['kopfg']));
        $this->firmform->add(new TextInput('kodu',$firm['kodu']));
        $this->firmform->add(new TextInput('gni',$firm['gni']));
        $this->firmform->add(new TextInput('kved',$firm['kved']));
      
     
    }


 
    public function OnSubmit($sender) {
        $firm = System::getOptions("");
        if (!is_array($firm)) {
            $firm = array();
        }   
        
        $firm['firm_name'] = $this->firmform->firm_name->getText();
        $firm['inn'] = $this->firmform->inn->getText();
        $firm['tin'] = $this->firmform->tin->getText();
        $firm['phone'] = $this->firmform->phone->getText();
        $firm['address'] = $this->firmform->address->getText();
        $firm['vdoc'] = $this->firmform->vdoc->getText();
  
        $firm['sign'] = $this->firmform->sign->getText();
        $firm['stamp'] = $this->firmform->stamp->getText();
        $firm['logo'] = $this->firmform->logo->getText();

        $firm['koatuu'] = $this->firmform->koatuu->getText();
        $firm['kopfg'] = $this->firmform->kopfg->getText();
        $firm['gni'] = $this->firmform->gni->getText();
        $firm['kved'] = $this->firmform->kved->getText();
        $firm['kodu'] = $this->firmform->kodu->getText();
      
        $firm['logo'] = $this->firmform->logo->getText();
        $firm['stamp'] = $this->firmform->stamp->getText();
        $firm['sign'] = $this->firmform->sign->getText();
         
         
         
        System::setOptions("firm", $firm);
      
        $this->setSuccess('Збережено');

      
    
    } 
   
    

}
