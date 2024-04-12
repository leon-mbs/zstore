<?php

namespace App\Pages;

use App\Entity\CronTask;
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
use App\Application as App;

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
        $this->_tvars['lasttime']  = '--';
        $lt= H::getKeyValInt('lastcron') ;
        if($lt>0) {
            $this->_tvars['lasttime']  = date('Y-m-d H:i',  $lt );
        }

        $this->add(new ClickLink('update', $this, 'OnUpdate'));
        $this->add(new ClickLink('ton', $this, 'OnToogle'));
        $this->add(new ClickLink('toff', $this, 'OnToogle'));

        $this->ds = new EntityDataSource("\\App\\Entity\\CronTask", "", " id asc");

        $this->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->nlist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));

        $this->OnUpdate(null);
    }


    public function OnUpdate($sender) {
        $cron =H::getKeyVal('cron') ?? false;
        $this->ton->setVisible($cron==true) ;
        $this->toff->setVisible($cron == false) ;

        $this->nlist->Reload() ;

    }

    public function OnToogle($sender) {
        if($sender->id == 'toff') {
            H::setKeyVal('cron', true) ;
            H::setKeyVal('stopcron', true) ; //на случай  если  повис

        } else {
            H::setKeyVal('cron', false) ;
        }
        $this->OnUpdate(null);
    }

    public function OnRow($row) {
        $task = $row->getDataItem();
        $names = CronTask::getTypes() ;
        $row->add(new Label("ndate"))->setText(\App\Helper::fdt($task->starton));
        $row->add(new Label("ntype"))->setText($names[$task->tasktype]);
        $row->add(new ClickLink("nexec", $this, 'OnExec'));
        $row->add(new ClickLink("ndel", $this, 'OnDel'));


    }

    public function OnExec($sender) {
        $t = $sender->getOwner()->getDataItem();
        CronTask::doQueue($t->id)  ;
        
        $this->nlist->Reload() ;       
    }    
    public function OnDel($sender) {
        $t = $sender->getOwner()->getDataItem();
        
        CronTask::delete($t->id) ; 
        
        $this->nlist->Reload() ;       
    }    
    
}
