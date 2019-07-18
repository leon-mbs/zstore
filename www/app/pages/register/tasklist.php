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
use \App\Entity\ProdArea;
use \App\Entity\Doc\Document;
use \App\Helper as H;
use \App\System;
use \App\Application;

class TaskList extends \App\Pages\Base {

    private $_task;
    private $_taskds;
    public $_users = array();
    public $_items = array();
    public $_store_id = 0;
    public $_discount = 0;
    private $_taskscnt = array();
   
    public $_tamount = 0;
   

    public function __construct() {


        parent::__construct();

        if (false == \App\ACL::checkShowReg('TaskList'))
            return;

        $this->_taskds = new EDS('\App\Entity\Doc\Document', "", "document_date desc");

        $this->add(new DataView('tasklist', $this->_taskds, $this, 'tasklistOnRow'));
        $this->tasklist->setSelectedClass('table-success');

        $this->tasklist->setPageSize(15);
        $this->add(new \Zippy\Html\DataList\Paginator('pag', $this->tasklist));

        $this->add(new Form('filterform'))->onSubmit($this, 'OnFilter');

        $this->filterform->add(new DropDownChoice('filterassignedto', Employee::findArray('emp_name', '', 'emp_name'), 0));
        $this->filterform->add(new DropDownChoice('filterpa', ProdArea::findArray('pa_name', '', 'pa_name'), 0));
        $this->filterform->add(new AutocompleteTextInput('filterclient'))->onText($this, 'OnAutoCustomer');
        $this->filterform->add(new CheckBox('filterfinished'));
        $this->filterform->add(new ClickLink('eraser'))->onClick($this, 'eraseFilter');


        $this->add(new Label("tamount"));
     

  

        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));
        $this->statuspan->statusform->add(new SubmitButton('binprocess'))->onClick($this, 'onStatus');
        $this->statuspan->statusform->add(new SubmitButton('bclosed'))->onClick($this, 'onStatus');
        $this->statuspan->statusform->add(new SubmitButton('bshifted'))->onClick($this, 'onStatus');


        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->add(new \App\Calendar('calendar'))->setEvent($this, 'OnGal');

        $this->updateTasks();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function tasklistOnRow($row) {
        $task = $row->getDataItem();



        $row->add(new Label('tasknumber', $task->document_number));
        $row->add(new Label('taskdesc', $task->notes));



        $row->add(new Label('taskstartdate', date('Y-m-d H:i', $task->headerdata['start_date'])));
        $row->add(new Label('taskhours', $task->headerdata['taskhours']));


        $row->add(new Label('taskstatus'));

        if ($task->state == Document::STATE_INPROCESS)
            $row->taskstatus->setText('<span class="badge badge-success">Выполняется</span>', true);
        if ($task->state == Document::STATE_SHIFTED)
            $row->taskstatus->setText('<span class="badge badge-warning">Отложена</span>', true);
        if ($task->state == Document::STATE_CLOSED)
            $row->taskstatus->setText('<span class="badge badge-default">Закончено</span>', true);






        $emps = array();
        foreach ($task->detaildata as $ser) {

            if (strlen($ser['employee_id']) > 0)
                $emps[] = $ser['emp_name'];
        }


        $row->add(new Label('taskemps', implode(', ', $emps)));
        $row->add(new Label('taskclient', $task->customer_name));
        $row->add(new Label('taskamount', $task->amount));
   
        $this->_tamount = $this->_tamount + $task->amount;
    
        $row->add(new ClickLink('taskshow'))->onClick($this, 'taskshowOnClick');
        $row->add(new ClickLink('taskedit'))->onClick($this, 'taskeditOnClick');
         if ($task->state == Document::STATE_CLOSED) {
            $row->taskedit->setVisible(false);
        }
    }

    //панель кнопок
    public function taskshowOnClick($sender) {
        $this->_task = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_task, true))
            return;

       
        $this->statuspan->setVisible(true);

        // if ($this->_task->checkStates(array(Document::STATE_EXECUTED)) == false || $this->_task->status == Document::STATE_EDITED || $this->_task->status == Document::STATE_NEW) {
        if ($this->_task->state != Document::STATE_EXECUTED) {
            $this->statuspan->statusform->bclosed->setVisible(true);
        } else {
            $this->statuspan->statusform->bclosed->setVisible(false);
        }
        if ($this->_task->state == Document::STATE_CANCELED || $this->_task->state == Document::STATE_EDITED || $this->_task->state == Document::STATE_NEW) {
            $this->statuspan->statusform->binprocess->setVisible(true);
            $this->statuspan->statusform->bshifted->setVisible(true);
        } else {
            $this->statuspan->statusform->binprocess->setVisible(false);
            $this->statuspan->statusform->bshifted->setVisible(false);
        }
        if ($this->_task->state == Document::STATE_SHIFTED) {
            $this->statuspan->statusform->binprocess->setVisible(true);
        }
        if ($this->_task->state == Document::STATE_INPROCESS) {
            $this->statuspan->statusform->bshifted->setVisible(true);
        }


        $this->statuspan->docview->setDoc($this->_task);
        $this->tasklist->setSelectedRow($sender->getOwner());
        $this->tasklist->Reload(false);
        $this->goAnkor('dankor');
    }

    public function taskeditOnClick($sender) {
        $task = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($task, true))
            return;



        Application::Redirect("\\App\\Pages\\Doc\\Task", $task->document_id);
    }

    public function onStatus($sender) {
        if ($sender->id == 'binprocess') {
            $this->_task->updateStatus(Document::STATE_INPROCESS);
        }
        if ($sender->id == 'bshifted') {
            $this->_task->updateStatus(Document::STATE_SHIFTED);
        }
        if ($sender->id == 'bclosed') {
            $this->_task->updateStatus(Document::STATE_EXECUTED);
            if ($this->_task->amount == $this->_task->payamount) { //если оплачен
                $this->_task->updateStatus(Document::STATE_CLOSED);
                $this->setSuccess('Наряд закрыт');
            }
        }

        $this->statuspan->setVisible(false);

        $this->tasklist->Reload(false);
    }

 
 
    public function updateTasks() {
        $user = System::getUser();

        $client = $this->filterform->filterclient->getKey();

        $sql = "meta_name='Task' ";
        if ($this->filterform->filterfinished->isChecked() == false) {
            $sql = $sql . " and state<>9 ";
        }
        if ($client > 0) {
            $sql = $sql . " and customer_id=" . $client;
        }
        if ($this->filterform->filterassignedto->getValue() > 0) {
            $sql = $sql . " and  content  like '%<employee_id>" . $this->filterform->filterassignedto->getValue() . "</employee_id>%' ";
        }
        if ($this->filterform->filterpa->getValue() > 0) {
            $sql = $sql . " and  content  like '%<parea>" . $this->filterform->filterpa->getValue() . "</parea>%' ";
        }
        if ($user->acltype == 2) {
            if ($user->onlymy == 1) {

                $sql .= " and user_id  = " . $user->user_id;
            }

            $sql .= " and meta_id in({$user->aclview}) ";
        }
        $this->_tamount = 0;
      

        $this->_taskds->setWhere($sql);
        $this->tasklist->Reload();
        $this->tamount->setText($this->_tamount);
        

        $this->updateCal();

        $this->statuspan->setVisible(false);
         
    }

    //обновить календар

    public function updateCal() {

        $tasks = array();
        $items = $this->_taskds->getItems();
        foreach ($items as $item) {


            if ($item->state == Document::STATE_INPROCESS)
                $col = "#28a745";
            if ($item->state == Document::STATE_SHIFTED)
                $col = "#ffc107";
            if ($item->state == Document::STATE_CLOSED)
                $col = "#dddddd";
            if ($item->state == Document::STATE_CLOSED && $item->amount > $task->payamount)
                $col = "#ff0000";



            $tasks[] = new \App\CEvent($item->document_id, $item->document_number, $item->headerdata['start_date'], $item->headerdata['end_date'], $col);
        }



        $this->calendar->setData($tasks);
    }

    public function eraseFilter($sender) {


        $this->filterform->clean();

        $this->updateTasks();
    }

    public function OnGal($sender, $action) {
        if ($action['action'] == 'click') {

            $task = Document::load($action['id']);
            //  $type = H::getMetaType($task->meta_id);
            // $class = "\\App\\Pages\\Doc\\" . $type['meta_name'];
            $class = "\\App\\Pages\\Doc\\Task";


            Application::Redirect($class, $task->document_id);
            return;
        }
        if ($action['action'] == 'add') {

            $start = strtotime($action['id'] . '10:00');
            $end = strtotime($action['id'] . '18:00');

            $class = "\\App\\Pages\\Doc\\Task";

            $task = Document::create($class);
            $task->document_date = $end;
            $task->headerdata['start_date'] = $start;
            $task->headerdata['end_date'] = $end;
            $task->document_number = $task->nextNumber();

            $task->save();
            Application::Redirect($class, $task->document_id);


            return;
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

            if ($task->state == Document::STATE_CLOSED)
                return;
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

    public function oncsv($sender) {
        $list = $this->tasklist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $task) {
            $csv .= $task->document_number . ';';
            $csv .= $task->customer_name . ';';
            $csv .= $task->notes . ';';
            $csv .= date('Y-m-d H:i', $task->headerdata['start_date']) . ';';
            $csv .= $task->headerdata['taskhours'] . ';';
            $csv .= Document::getStateName($task->state) . ';';
            $csv .= $task->amount . ';';

            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=taskslist.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}
