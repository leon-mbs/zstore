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
        $storelist[10000]='По всем';
        $this->common->add(new DropDownChoice('storeexp',0));
        $this->common->add(new CheckBox('custexp'));
        $this->common->add(new CheckBox('empexp'));
        $this->common->add(new CheckBox('serexp'));
        $this->common->add(new CheckBox('eqexp'));
        $this->common->add(new CheckBox('storeexp'));
        
        $this->add(new Form('impform'))->onSubmit($this, 'onImport');
 
        $this->common->add(new DropDownChoice('storeimp',\App\Entity\Store::getList(),0));
        $this->common->add(new CheckBox('custimp'));
        $this->common->add(new CheckBox('empimp'));
        $this->common->add(new CheckBox('serimp'));
        $this->common->add(new CheckBox('eqimp'));
        $this->common->add(new CheckBox('storeimp'));
        
    }

    
}
