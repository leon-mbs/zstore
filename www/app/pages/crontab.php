<?php

namespace App\Pages;

use App\Entity\Notify;
use App\Helper as H;
use App\System;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\WebApplication as App;

class CronTab extends \App\Pages\Base
{
    public $ds;


    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

        $this->_tvars['url']  = _BASEURL.'crontab.php';
        $this->_tvars['lasttime']  = date('Y-m-d H:i', H::getVal('lastcron') );
        
        $this->add(new ClickLink('update',$this,'OnUpdate'));
        $this->add(new ClickLink('ton',$this,'OnToogle'));
        $this->add(new ClickLink('toff',$this,'OnToogle'));

        $this->OnUpdate(null);    
    }
 

    public  function OnUpdate($sender){
        $cron = System::getOption('common','cron') ?? false;
        $this->ton->setVisible($cron==true) ;
        $this->toff->setVisible($cron == false) ;
    } 
    
    public  function OnToogle($sender){
         if($sender->id=='toff'){
            System::setOption('common','cron',true) ;
         } else {
            System::setOption('common','cron',false) ;
         }
         $this->OnUpdate(null);
    }
    

    
}
