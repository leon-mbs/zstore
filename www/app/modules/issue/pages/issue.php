<?php

namespace App\Modules\Issue\Pages;

use \App\Application as App;
use \App\Helper;
use \App\System;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \App\Modules\Issue\Entity\Issue;
 


/**
 * страница задачи
 */
class Issue extends \App\Pages\Base 
{
   
    public function __construct($issue_id) {
        
        parent::__construct();
        
        $allow = (strpos(System::getUser()->modules, 'issue') !== false || System::getUser()->userlogin == 'admin');
        if(!$allow){
            System::setErrorMsg('Нет права  доступа  к   модулю ');
            App::RedirectHome();
            return false;
        }
        
        
          
       
    }

    
     
}
