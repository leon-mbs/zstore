<?php

namespace App\Pages;

use App\Entity\Employee;
use App\Entity\TimeItem;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
use Zippy\WebApplication as App;

class TimeSheet extends \App\Pages\Base
{

    public $user = null;
    public $ds;

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
     
        $dt = new \Carbon\Carbon;
         
        $from = $dt->startOfMonth()->timestamp;
        $to = $dt->endOfMonth()->timestamp;
     
     
        $this->filter->add(new Date('from', $from));
        $this->filter->add(new Date('to', $to));

        $def = 0;
        $list=array();
        $emps = Employee::findArray('emp_name','disabled<>1','emp_name') ;

        $user = System::getUser() ;
        if($user->employee_id>0) {
            $def = $user->employee_id;
            $list = array($user->employee_id=>$emps[$user->employee_id]) ;
        }
        if($user->rolename='admins') {
            $list =  $emps; 
        }  
        
        $this->filter->add(new DropDownChoice('emp', $list , $def));
       
        
        $this->add(new Panel('tpanel'))->setVisible(false) ;
        
        $this->tpanel->add(new Panel('tcal')) ;
        $this->tpanel->add(new Panel('tagen')) ;
        $this->tpanel->add(new Panel('tstat')) ;
 
        $this->tpanel->add(new ClickLink('tabc', $this,'onTab'));
        $this->tpanel->add(new ClickLink('taba', $this,'onTab'));
        $this->tpanel->add(new ClickLink('tabs', $this,'onTab'));
        
        $this->tpanel->add(new ClickLink('addnew', $this,'AddNew'));
        
     
        $this->tpanel->tabc->add(new \App\Calendar('calendar'))->setEvent($this, 'OnCal');

        
        $this->onTab($this->tpanel->tabc);       
        $this->filterOnSubmit($this->filter);       
        
    }

    public  function onTab($sender){
        
        $this->_tvars['tabcbadge']  = $sender->id =='tabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  " ;
        $this->_tvars['tababadge']  = $sender->id =='taba' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  " ;;
        $this->_tvars['tabsbadge']  = $sender->id =='tabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  " ;;
       
        $this->tpanel->tcal->setVisible($sender->id =='tabc');
        $this->tpanel->tagen->setVisible($sender->id =='taba');
        $this->tpanel->tstat->setVisible($sender->id =='tabs');
        
     }    
 
     public function filterOnSubmit($sender) {
        $emp_id= $this->filter->emp->getValue();
        
        $this->tpanel->setVisible($emp_id>0);  
         
     }
     public function AddNew($sender) {
        
        $this->tpanel->setVisible(false);  
         
     }
     public function OnCal($sender, $action) {
        if ($action['action'] == 'click') {

            $task = Document::load($action['id']);
       
        }
        if ($action['action'] == 'add') {

            $start = strtotime($action['id'] . ' 9:00');

            Application::Redirect("\\App\\Pages\\Doc\\Task", 0, 0, $start);
        }
     
      
    }
     public function updateCal() {

        $tasks = array();
        $items = $this->_taskds->getItems();
        foreach ($items as $item) {

            $col = "#aaa";
            if ($item->state == Document::STATE_INPROCESS) {
                $col = "#28a745";
            }
            if ($item->state == Document::STATE_SHIFTED) {
                $col = "#ffc107";
            }
            if ($item->state == Document::STATE_CLOSED) {
                $col = "#dddddd";
            }
            if (strlen($item->headerdata['taskhours']) == 0) {
                $item->headerdata['taskhours'] = 0;
            }
            $d = floor($item->headerdata['taskhours'] / 8);
            $end_date = $item->document_date + (3600 * 24 * $d);

            $tasks[] = new \App\CEvent($item->document_id, $item->document_number, $item->document_date, $end_date, $col);
        }


        $this->calendar->setData($tasks);
    }

}
