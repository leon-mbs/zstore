<?php

namespace App\Pages;

use \App\Entity\User;
use \App\System;
use \Zippy\WebApplication as App;
use \Zippy\WebApplication as H;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\File;
use \App\Entity\Customer;
use \App\Entity\Employee;

class Migration extends \App\Pages\Base {

 

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('К странице имеет  доступ только администратор ');
            App::RedirectHome();
            return false;
        }

        $this->add(new Form('expform'))->onSubmit($this, 'onExport');
        $storelist = \App\Entity\Store::getList();
        $storelist[10000]='По всем складам';
        $this->expform->add(new DropDownChoice('storeexp',$storelist,0));
        $this->expform->add(new CheckBox('custexp'));
        $this->expform->add(new CheckBox('empexp'));
        $this->expform->add(new CheckBox('serexp'));
        $this->expform->add(new CheckBox('eqexp'));
        $this->expform->add(new CheckBox('itemexp'));
        
        $this->add(new Form('impform'))->onSubmit($this, 'onImport');
 
        $this->impform->add(new DropDownChoice('storeimp',\App\Entity\Store::getList(),0));
        $this->impform->add(new CheckBox('custimp'));
        $this->impform->add(new CheckBox('empimp'));
        $this->impform->add(new CheckBox('serimp'));
        $this->impform->add(new CheckBox('eqimp'));
        $this->impform->add(new CheckBox('itemimp'));
        
    }

    
    public  function onExport($sender) {
       $data = new MigrationData();
       if($sender->custexp->isChecked())   {
           $data->cust = Customer::find('status <> ' .Customer::STATUS_DISABLED);
       }
       if($sender->empexp->isChecked())   {
           $data->emp = Employee::find('disabled <> 1'  );
       }
       
         
    }
    public  function onImport($sender) {
        
    }
    
}

class  MigrationData {
    public $emp = array();
    public $cust = array();
    public $eq = array();
    public $ser = array();
    public $items= array();
    
    
}
