<?php

namespace App\Pages\Register;

use \ZCL\DB\EntityDataSource as EDS;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\File;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
 
 
use \App\Entity\Customer;
use \App\Entity\Stock;
use \App\Entity\Item;
use \App\Entity\Service;
use \App\Entity\Employee;
use \App\Entity\Doc\Document;
use \App\Helper as H;
use \App\System;
use \App\Application;

class TaskList extends \App\Pages\Base
{

 
    private $_task;
    private $_taskds;
    public $_users = array();
    public $_items = array();
    public $_store_id = 0;
    public $_discount = 0;
    private $_taskscnt = array();

    
    public function __construct() {
        
        
        parent::__construct();
        $this->_taskds = new EDS('\App\Entity\Doc\Document', "", "document_date desc");

        
        $this->add(new DataView('tasklist', $this->_taskds, $this, 'tasklistOnRow'));
        $this->tasklist->setPageSize(15);
        $this->add(new \Zippy\Html\DataList\Paginator('pag', $this->tasklist));

        $this->add(new Form('filterform'))->onSubmit($this, 'OnFilter');
        
        $this->filterform->add(new DropDownChoice('filterassignedto', Employee::findArray('emp_name', '', 'emp_name'), 0));
        $this->filterform->add(new AutocompleteTextInput('filterclient'))->onText($this, 'OnAutoCustomer');
        $this->filterform->add(new CheckBox('filterfinished'));
        $this->filterform->add(new ClickLink('eraser'))->onClick($this, 'eraseFilter');
   
     
 
     
        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        $this->add(new \App\Calendar('calendar'))->setEvent($this, 'OnGal');
        
        $this->updateTasks();

         
    }

     
    public function tasklistOnRow($row) {
        $task = $row->getDataItem();


 
        $row->add(new Label('tasknumber', $task->document_number));
        $row->add(new Label('taskdesc', $task->notes));
        
       

        $row->add(new Label('taskstartdate', date('Y-m-d H:i', $task->headerdata['start_date'])));
        $row->add(new Label('taskhours', $task->headerdata['hours']));

        
        $row->add(new Label('taskstatus' ));
        
        if($task->state==Document::STATE_INPROCESS )$row->taskstatus->setText('<span class="badge badge-success">Выполняется</span>',true);         
        if($task->state==Document::STATE_SHIFTED)$row->taskstatus->setText('<span class="badge badge-warning">Отложена</span>',true);         
        if($task->state==Document::STATE_CLOSED)$row->taskstatus->setText('<span class="badge badge-default">Закончено</span>',true);         
        if($task->state==Document::STATE_CLOSED && $task->amount > $task->datatag)$row->taskstatus->setText('<span class="badge badge-danger">Неоплачено</span>',true);         
        
         
           
      
      
        $emps = array();
        foreach($task->detaildata as $ser){
           
           if(strlen($ser['employee_id']) > 0) $emps[] = $ser['emp_name'];
        }
        
    
        $row->add(new Label('taskemps', implode(', ',$emps)));
        $row->add(new Label('taskclient', $task->headerdata['customer_name']));
  

        $row->add(new ClickLink('taskshow'))->onClick($this, 'taskshowOnClick');
        $row->add(new ClickLink('taskedit'))->onClick($this, 'taskeditOnClick');
        $row->add(new ClickLink('taskdelete'))->onClick($this, 'taskdeleteOnClick');
        $row->taskdelete->setVisible($task->state != Document::STATE_CLOSED);
       
    }

    
    public function taskshowOnClick($sender){
        $task = $sender->getOwner()->getDataItem();
        $this->docview->setVisible(true);
        $this->docview->setDoc($task);        
    }
    public function taskeditOnClick($sender){
        $task = $sender->getOwner()->getDataItem();
        
          $type = H::getMetaType($task->meta_id);
        $class = "\\App\\Pages\\Doc\\" . $type['meta_name'];
 

        Application::Redirect($class, $task->document_id);      
    }
    public function taskdeleteOnClick($sender){
        $task = $sender->getOwner()->getDataItem();
        $task->updateStatus(Document::STATE_CANCELED);
        $this->updateTasks();
    }
 
    
    public function updateTasks() {
        $client = $this->filterform->filterclient->getKey();
        
        $sql = "meta_name='Task' ";
        if ($this->filterform->filterfinished->isChecked() == false) {
            $sql = $sql . " and (state in(7,16) or (state=9 and amount > datatag )) ";   
        }else {
            $sql = $sql . " and state in(7,16,9)  ";   
        }
        if ($client > 0) {
            $sql = $sql . " and content like '%<customer>{$client}</customer>%'";
        }
        if ($this->filterform->filterassignedto->getValue() > 0) {
            $sql = $sql . " and  content  like '%<employee_id>" . $this->filterform->filterassignedto->getValue() . "</employee_id>%' ";
        }

    
        $this->_taskds->setWhere($sql);
        $this->tasklist->Reload();
        $this->updateCal();
        $this->docview->setVisible(false);
    }

    //обновить календар
    public function updateCal() {
        
        $tasks = array();
        $items = $this->_taskds->getItems();
        foreach ($items as $item) {
            
            
           if($item->state==Document::STATE_INPROCESS )$col="#28a745";         
           if($item->state==Document::STATE_SHIFTED)$col="#ffc107";         
           if($item->state==Document::STATE_CLOSED)$col="#dddddd";         
           if($item->state==Document::STATE_CLOSED && $item->amount > $task->datatag)$col="#ff0000";         

           
           
           $tasks[] = new \App\CEvent($item->document_id, $item->document_number  , $item->headerdata['start_date'],  $item->headerdata['end_date'], $col);
        }

        
        
        $this->calendar->setData($tasks);
    }


   
    public function eraseFilter($sender) {
      
    
        $this->filterform->clean();
        
        $this->updateTasks();
        
    }
  

    public function OnGal($sender,$action) {
        if($action['action']=='click'){
            
            $task = Document::load($action['id']) ;
          //  $type = H::getMetaType($task->meta_id);
           // $class = "\\App\\Pages\\Doc\\" . $type['meta_name'];
            $class = "\\App\\Pages\\Doc\\Task"  ;
     

            Application::Redirect($class, $task->document_id);           
            return;   
        }
        if($action['action']=='add'){
           
            $start = strtotime( $action['id'].'10:00');
            $end = strtotime( $action['id'].'18:00');
           
            $class = "\\App\\Pages\\Doc\\Task"  ;
     
            $task= Document::create($class) ;
            $task->document_date =  $end;
            $task->headerdata['start_date'] =  $start;
            $task->headerdata['end_date'] =  $end ;
            $task->document_number = $task->nextNumber();
  
            $task->save();
            Application::Redirect($class, $task->document_id);           
          
           
           return;
            
        }
        if($action['action']=='move'){
           $task=Task::load($action['id']);
           $task->start_date = $task->start_date + $action['delta'] ;
           if($task->state== Document::STATE_CLOSED) return;
           $task->save();
           $this->updateCal();
           $this->updateTasks();
        }
        if($action['action']=='resize'){
           $task=Document::load($action['id']);
           $task->hours = $task->hours + ($action['delta']/3600) ;
           $task->end_date = $task->end_date + ($action['delta']/3600) ;
           $task->document_date = $task->end_date ;
           
           if($task->state==Document::STATE_CLOSED) return;
           $task->save();
           $this->updateCal();
           $this->updateTasks();
        }
    }
 
    public function OnFilter($sender) {
        
     
        $this->updateTasks();
        
    }

  public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "customer_name like " . $text);
    }


}
