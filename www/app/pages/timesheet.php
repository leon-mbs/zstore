<?php

namespace App\Pages;

use App\Entity\Employee;
use App\Entity\TimeItem;
use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;


class TimeSheet extends \App\Pages\Base
{

    private $_time_id = 0;

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
     

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
 
        
        $dt = new \Carbon\Carbon;
         
        $from = $dt->startOfMonth()->timestamp;
        $to = $dt->endOfMonth()->timestamp;
     
        $this->filter->add(new Date('from', $from));
        $this->filter->add(new Date('to', $to));
        
        
        $this->add(new Panel('tpanel'))->setVisible(false) ;
        
        $tcal = $this->tpanel->add(new Panel('tcal')) ;
        $tagen = $this->tpanel->add(new Panel('tagen')) ;
        $tstat = $this->tpanel->add(new Panel('tstat')) ;

 
        $this->tpanel->add(new ClickLink('tabc', $this,'onTab'));
        $this->tpanel->add(new ClickLink('taba', $this,'onTab'));
        $this->tpanel->add(new ClickLink('tabs', $this,'onTab'));
        
        $this->tpanel->add(new ClickLink('addnew', $this,'AddNew'));
        
     
        $tcal->add(new \App\Calendar('calendar'))->setEvent($this, 'OnCal');

          
        $this->add(new Form('editform'))->onSubmit($this, 'timeOnSubmit');
        $this->editform->setVisible(false);
        $this->editform->add(new DropDownChoice('edittype', TimeItem::getTypeTime() , TimeItem::TIME_WORK ));
        $this->editform->add(new TextInput('editnote' ));
        $this->editform->add(new TextInput('editfrom' ));
        $this->editform->add(new TextInput('editto' ));
        $this->editform->add(new Date('editdate',time()));
        $this->editform->add(new Button('cancel'  ))->onClick($this,'onCancel');
           
        
        $this->onTab($this->tpanel->tabc);       
        $this->filterOnSubmit($this->filter);       
        
    }

    public  function onTab($sender){
        
        $this->_tvars['tabcbadge']  = $sender->id =='tabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  " ;
        $this->_tvars['tababadge']  = $sender->id =='taba' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  " ;;
        $this->_tvars['tabsbadge']  = $sender->id =='tabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  " ;;
       
        $this->tpanel->tcal->setVisible($sender->id  =='tabc');
        $this->tpanel->tagen->setVisible($sender->id =='taba');
        $this->tpanel->tstat->setVisible($sender->id =='tabs');
        
     }    
 
     public function filterOnSubmit($sender) {
        $emp_id = $this->filter->emp->getValue();
        $this->tpanel->setVisible($emp_id>0);  
     }
   
     public function onCancel($sender) {
        $this->filter->setVisible(true);     
        $this->tpanel->setVisible(true);     
        $this->editform->setVisible(false);          
     }

     public function AddNew($sender) {
        
          $this->filter->setVisible(false);     
          $this->tpanel->setVisible(false);     
          $this->editform->setVisible(true);        
          $this->editform->editfrom->setText('09:00');
          $this->editform->editto->setText('18:00');
          $this->editform->editnote->setText('');
          $this->editform->edittype->setValue(TimeItem::TIME_WORK);
          $this->_time_id  =0;
     }
     public function onEdit($sender) {
        
          $this->filter->setVisible(false);     
          $this->tpanel->setVisible(false);     
          $this->editform->setVisible(true);  
          
          $time = $sender->getOwner()->getDataItem();
          $this->_time_id  =  $time->time_id; 
               
          $this->editform->editfrom->setText('09:00');
          $this->editform->editto->setText('18:00');
          $this->editform->editnote->setText('');
          $this->editform->edittype->setValue(TimeItem::TIME_WORK);
           
     }
  
     public function timeOnSubmit($sender) {
         $time = new  TimeItem();
         $time->description = $sender->editnote->getText();
         $time->emp_id = $this->filter->emp->getValue();
         if($time->emp_id ==0) {
             $setError('Не  выбран  сотрудник');
             return;
         }
         $time->t_type = $sender->edittype->getValue();
         $from   = $sender->editdate->getText(). ' ' .$sender->editfrom->getText();
         $to   = $sender->editdate->getText(). ' ' .$sender->editom->getText();
         $time->t_start = strtotime($from) ;
         $time->t_end = strtotime($to) ;
         $v = $time->isValid() ;
         if($strlen($v)>0){
             $this->setError($v);
             return;             
         }
         $time->save();
         
         $this->filter->setVisible(true);     
         $this->tpanel->setVisible(true);     
         $this->editform->setVisible(false);           
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

     private function updateCal() {

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


         $this->tpanel->tcal->calendar->setData($tasks);
    }

    
     private function updateList(){
         
     } 
}
