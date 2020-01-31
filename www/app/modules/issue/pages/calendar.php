<?php

namespace App\Modules\Issue\Pages;

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
use \App\Entity\Employee;
use \App\Entity\Doc\Document;
use \App\Helper as H;
use \App\System;
use \App\Application as App;

class Calendar extends \App\Pages\Base {
    private $timerow = null;
    public function __construct() {
        parent::__construct();

        $this->add(new Panel('listpan'));
        
        $this->listpan->add(new ClickLink('addtime',$this,'OnAdd'));
        
        $this->listpan->add(new DataView('timelist',new IssueTimeDataSource($this),$this,'OnTimeRow'));
        $this->listpan->add(new \Zippy\Html\DataList\Paginator('pag', $this->listpan->timelist));
        $this->listpan->timelist->setPageSize(H::getPG());          
        $this->listpan->timelist->Reload();
         
        $this->listpan->add(new \App\Calendar('calendar'))->setEvent($this, 'OnGal');
        
        $this->add(new Form('editform'))->onSubmit($this,'OnSave');
        $this->editform->setVisible(false);
        $this->editform->add(new ClickLink('cancel',$this,'OnCancel'));
        
    }

    public function OnTimeRow($row){
        $item = $row->getDataItem();
           
    }
    public function OnCancel($sender){
          $this->listpan->setVisible(true);
          $this->editform->setVisible(false);
          
    }
   public function OnAdd($sender){
          $this->listpan->setVisible(false);
          $this->editform->setVisible(true);
          
    }
   public function OnSave($sender){
          $this->listpan->setVisible(false);
          $this->editform->setVisible(true);
          
    }
   
   public function updateCal() {

        $tasks = array();
        $items = $this->_taskds->getItems();
        foreach ($items as $item) {
 
 
            $tasks[] = new \App\CEvent($item->document_id, $item->document_number, $item->headerdata['start_date'], $item->headerdata['end_date'], "#28a745");
        }



        $this->calendar->setData($tasks);
    }
   
    
    public function OnCal($sender, $action) {
        if ($action['action'] == 'click') {

            $task = Document::load($action['id']);
            //  $type = H::getMetaType($task->meta_id);
            // $class = "\\App\\Pages\\Doc\\" . $type['meta_name'];
            $class = "\\App\\Pages\\Doc\\Task";


            Application::Redirect($class, $task->document_id);
            return;
        }
        if ($action['action'] == 'add') {

            $start = strtotime($action['id'] . ' 10:00');
      
            Application::Redirect("\\App\\Pages\\Doc\\Task", 0,0, $start);

          
        }
        if ($action['action'] == 'move') {
            $task = Task::load($action['id']);
            $task->start_date = $task->start_date + $action['delta'];
            if ($task->state == Document::STATE_CLOSED)
                return;
            $task->save();
            $this->updateCal();
            $this->updateTasks();
        }
        if ($action['action'] == 'resize') {
            $task = Document::load($action['id']);
            $task->hours = $task->hours + ($action['delta'] / 3600);
            $task->end_date = $task->end_date + ($action['delta'] / 3600);
            $task->document_date = $task->end_date;

       
            $task->save();
            $this->updateCal();
            $this->updateTasks();
        }
    }
    
     
}

class IssueTimeDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }
  
    public function getItemCount() {
          
          $conn = \ZDB\DB::getConnect();
          return $conn->GetOne('select coalesce(count(*),0) from issue_time where user_id=' . System::getUser()->user_id ) ;
          
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
          $conn = \ZDB\DB::getConnect();
          $sql = 'select * from issue_time_view where user_id=' . System::getUser()->user_id ;
          $rs = $conn->Execute($sql) ;

          if ($start >= 0 or $count >= 0) {
              $rs = $conn->SelectLimit($sql, $count, $start);
          } else {
              $rs = $conn->Execute($sql);
          }
                    
          $list = array();
          foreach($rs as $row) {
              $list[$row['id']] = new \App\DataItem($row); 
          }
          return $list;
    }

    public function getItem($id) {
        
    }

}