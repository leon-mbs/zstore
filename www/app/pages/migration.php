<?php

namespace App\Pages;

use \Zippy\Html\DataList\DataView;
use \App\Entity\User;
use \App\System;
use \Zippy\WebApplication as App;
use \ZCL\DB\EntityDataSource;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;

class Migration extends \App\Pages\Base {

 

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('К странице имеет  доступ только администратор ');
            App::RedirectHome();
            return false;
        }

 
    }

    
}
