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
use \App\Entity\Doc\Task;
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
 

    public function __construct() {


        parent::__construct();

        if (false == \App\ACL::checkShowReg('TaskList'))
            return;

        $this->_taskds = new EDS('\App\Entity\Doc\Document', "", "document_date desc");

        $this->add(new DataView('tasklist', $this->_taskds, $this, 'tasklistOnRow'));
        $this->tasklist->setSelectedClass('table-success');

        $this->tasklist->setPageSize(H::getPG(H::getPG()));
        $this->add(new \Zippy\Html\DataList\Paginator('pag', $this->tasklist));

        $this->add(new Form('filterform'))->onSubmit($this, 'OnFilter');

        $this->filterform->add(new DropDownChoice('filterassignedto', Employee::findArray('emp_name', '', 'emp_name'), 0));
        $this->filterform->add(new DropDownChoice('filterpa', ProdArea::findArray('pa_name', '', 'pa_name'), 0));

        $this->filterform->add(new CheckBox('filterfinished'));
        $this->filterform->add(new ClickLink('eraser'))->onClick($this, 'eraseFilter');
 
        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));
        $this->statuspan->statusform->add(new SubmitButton('binprocess'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bclosed'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bshifted'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bitems'))->onClick($this, 'statusOnSubmit');
 
        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->add(new \App\Calendar('calendar'))->setEvent($this, 'OnCal');

        $this->updateTasks();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function tasklistOnRow($row) {
        $task = $row->getDataItem();

        $row->add(new Label('tasknumber', $task->document_number));
        $row->add(new Label('taskdesc', $task->notes));

        $row->add(new Label('taskstartdate', date('Y-m-d', $task->headerdata['start_date'])));
        $row->add(new Label('taskhours', $task->headerdata['taskhours']));

        $row->add(new Label('taskstatus', Document::getStateName($task->state)));

        if ($task->state == Document::STATE_EXECUTED)
            $row->taskstatus->setText('<span class="badge badge-success">Выполнен</span>', true);
        if ($task->state == Document::STATE_INPROCESS)
            $row->taskstatus->setText('<span class="badge badge-info">Выполняется</span>', true);
        if ($task->state == Document::STATE_SHIFTED)
            $row->taskstatus->setText('<span class="badge badge-warning">Отложена</span>', true);
        if ($task->state == Document::STATE_CLOSED)
            $row->taskstatus->setText('<span class="badge badge-default">Закончено</span>', true);
 
        $emps = array();
        foreach ($task->unpackDetails('emplist') as $emp) {
             $emps[] = $emp->emp_name;
        }

        $row->add(new Label('taskemps', implode(', ', $emps)));
        $sers = array();
        foreach ($task->unpackDetails('detaildata') as $ser) {
             $sers[] = $ser->service_name;
        }

        $row->add(new Label('taskservices', implode(', ', $sers)));
 
        $row->add(new ClickLink('taskshow'))->onClick($this, 'taskshowOnClick');
        $row->add(new ClickLink('taskedit'))->onClick($this, 'taskeditOnClick');
        if ($task->state == Document::STATE_CLOSED || $task->state == Document::STATE_EXECUTED) {
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
        if ($this->_task->state < Document::STATE_EXECUTED) {
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
        $this->statuspan->statusform->bitems->setVisible($this->_task->state != Document::STATE_CLOSED);


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

    public function statusOnSubmit($sender) {

        if (\App\Acl::checkExeDoc($this->_task, true, true) == false)
            return;


        $this->_task = $this->_task->cast();

        if ($sender->id == 'binprocess') {
            $this->_task->updateStatus(Document::STATE_INPROCESS);
        }
        if ($sender->id == 'bshifted') {
            $this->_task->updateStatus(Document::STATE_SHIFTED);
        }
        if ($sender->id == 'bclosed') {
            //    $this->_task->updateStatus(Document::STATE_EXECUTED);
            $this->_task->updateStatus(Document::STATE_CLOSED);
        }
        if ($sender->id == 'bitems') {    //списание материалов
            $d = $this->_task->getChildren('ProdIssue');
            if (count($d) > 0) {
            
                $this->setWarn('exists_prodissue');
            }
            Application::Redirect("\\App\\Pages\\Doc\\ProdIssue", 0, $this->_task->document_id);
            return;
        }

        $this->statuspan->setVisible(false);

        $this->tasklist->Reload(false);
    }

    public function updateTasks() {
        $user = System::getUser();
 
        $sql = "meta_name='Task' ";
        if ($this->filterform->filterfinished->isChecked() == false) {
            $sql = $sql . " and state<>9 ";
        }

        if ($this->filterform->filterassignedto->getValue() > 0) {
            $sql = $sql . " and  content  like '%<employee_id>" . $this->filterform->filterassignedto->getValue() . "</employee_id>%' ";
        }
        if ($this->filterform->filterpa->getValue() > 0) {
            $sql = $sql . " and  content  like '%<parea>" . $this->filterform->filterpa->getValue() . "</parea>%' ";
        }
        $c = Document::getConstraint();
        if (strlen($c) > 0) {
            $sql = $sql . " and ({$c})";
        }
 
        $this->_taskds->setWhere($sql);
        $this->tasklist->Reload();
 
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


            $tasks[] = new \App\CEvent($item->document_id, $item->document_number, $item->headerdata['start_date'], $item->headerdata['end_date'], $col);
        }



        $this->calendar->setData($tasks);
    }

    public function eraseFilter($sender) {


        $this->filterform->clean();

        $this->updateTasks();
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

            $start = strtotime($action['id'] . ' 9:00');

            Application::Redirect("\\App\\Pages\\Doc\\Task", 0, 0, $start);
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

    public function oncsv($sender) {
        $list = $this->tasklist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $task) {
            $csv .= $task->document_number . ',';

            $csv .= str_replace(',', '', $task->notes) . ';';
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
