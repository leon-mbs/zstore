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
use \App\Modules\Issue\Entity\Issue;
use \App\Modules\Issue\Entity\Project;
use \App\Modules\Issue\Entity\TimeLine;
use \ZCL\DB\EntityDataSource; 
use \App\Entity\Doc\Document;
use \App\Helper as H;
use \App\System;
use \App\Application as App;

class Calendar extends \App\Pages\Base {
    private $timerow = null;
    public function __construct() {
        parent::__construct();
        $user = System::getUser() ;
        
        $this->add(new Panel('listpan'));
        
        $this->listpan->add(new ClickLink('addtime',$this,'OnAdd'));
        
        $this->listpan->add(new DataView('timelist',new EntityDataSource("\\App\\Modules\\Issue\\Entity\\TimeLine",'user_id='.$user->user_id,'id desc'),$this,'OnTimeRow'));
        $this->listpan->add(new \Zippy\Html\DataList\Paginator('pag', $this->listpan->timelist));
        $this->listpan->timelist->setPageSize(H::getPG());          
        $this->listpan->timelist->Reload();
         
        $this->listpan->add(new \App\Calendar('calendar'))->setEvent($this, 'OnGal');
        
        $this->add(new Form('editform'))->onSubmit($this,'OnSave');
        $this->editform->setVisible(false);
        $this->editform->add(new ClickLink('cancel',$this,'OnCancel'));
        $this->editform->add(new Date('edate',time() ));
        $this->editform->add(new TextInput('etime' ));
        $this->editform->add(new TextInput('enotes' ));
        $this->editform->add(new DropDownChoice('eproject',Project::findArray('project_name','','project_id desc') ))->onChange($this,'OnProject');
        $this->editform->add(new DropDownChoice('eissue'  ));
         
        
    }

    public function OnTimeRow($row){
        $item = $row->getDataItem();
        $row->add(new Label('date',date('Y-m-d',$item->createdon)) );  
        $row->add(new Label('time',$item->duration/100) );  
        $row->add(new Label('issue','#'.$item->issue_id.' '.$item->issue_name) );  
        $row->add(new Label('project',$item->project_name) );  
        $row->add(new Label('notes',$item->notes) );  
       
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
          
          $issue = $sender->eissue->getValue();
          $h = $sender->etime->getText();
          if($issue==0){
              $this->setError('Не  выбрана задача');
              return;
          }
          if(($h>0 )==false){
              $this->setError('Не указано время');
              return;
          }
          
          $time =  new TimeLine();
          $time->issue_id = $issue;
          $time->user_id = System::getUser()->user_id;
          $time->duration = $h*100;
          $time->createdon = $sender->edate->getDate() ;
          $time->notes = $sender->enotes->getText() ;
          $time->save();
          
          $sender->eissue->setValue(0);
          $sender->etime->setText('') ;
          $this->listpan->timelist->Reload();
    }
   
    public function OnProject($sender){
          $id = $sender->getValue();
          $list = Issue::findArray('issue_name','project_id='.$id,'issue_id desc');
          $opt = array();
          $opt[0] = 'Не выбрана';
          foreach($list as $k=>$v) {
              $opt[$k] = '#'.$k.' '. $v;
          }
          $this->editform->eissue->setOptionList($opt);
          $this->editform->eissue->setValue(0);
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

 