<?php

namespace App\Pages\Register;

use App\Application;
use App\Entity\Doc\Document;
use App\Entity\Doc\Task;
use App\Entity\Employee;
use App\Entity\ProdArea;
use App\Helper as H;
use App\System;
use ZCL\DB\EntityDataSource as EDS;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class TaskList extends \App\Pages\Base
{

    private $_task;
    private $_taskds;
    public  $_users    = array();
    public  $_items    = array();
    public  $_store_id = 0;
    public  $_discount = 0;
    private $_taskscnt = array();

    public function __construct() {


        parent::__construct();

        if (false == \App\ACL::checkShowReg('TaskList')) {
            return;
        }

        $this->_taskds = new EDS('\App\Entity\Doc\Document', "", "priority desc,document_date desc");

        $this->add(new ClickLink('tabc', $this, 'onTab'));
        $this->add(new ClickLink('tabs', $this, 'onTab'));
        $this->add(new Panel('tasktab'));
        $this->add(new Panel('caltab'));

        $this->tasktab->add(new DataView('tasklist', $this->_taskds, $this, 'tasklistOnRow'));

        $this->tasktab->tasklist->setPageSize(H::getPG(H::getPG()));
        $this->tasktab->add(new \Zippy\Html\DataList\Paginator('pag', $this->tasktab->tasklist));

        $this->add(new Form('filterform'))->onSubmit($this, 'OnFilter');

        $this->filterform->add(new DropDownChoice('filterassignedto', Employee::findArray('emp_name', '', 'emp_name'), 0));
        $this->filterform->add(new DropDownChoice('filterpa', ProdArea::findArray('pa_name', '', 'pa_name'), 0));

        $this->filterform->add(new CheckBox('filterfinished'));
        $this->filterform->add(new ClickLink('eraser'))->onClick($this, 'eraseFilter');

        $this->tasktab->add(new Panel("statuspan"))->setVisible(false);

        $this->tasktab->statuspan->add(new Form('statusform'));
        $this->tasktab->statuspan->statusform->add(new SubmitButton('binprocess'))->onClick($this, 'statusOnSubmit');
        $this->tasktab->statuspan->statusform->add(new SubmitButton('bclosed'))->onClick($this, 'statusOnSubmit');
        $this->tasktab->statuspan->statusform->add(new SubmitButton('bshifted'))->onClick($this, 'statusOnSubmit');
        $this->tasktab->statuspan->statusform->add(new SubmitButton('bitems'))->onClick($this, 'statusOnSubmit');
        $this->tasktab->statuspan->statusform->add(new SubmitButton('bgoods'))->onClick($this, 'statusOnSubmit');
        $this->tasktab->statuspan->statusform->add(new SubmitButton('bact'))->onClick($this, 'statusOnSubmit');

        $this->tasktab->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->caltab->add(new \ZCL\Calendar\Calendar('calendar', 'ua'))->setEvent($this, 'OnCal');

        $this->updateTasks();
        $this->updateCal();
        $this->tasktab->add(new ClickLink('csv', $this, 'oncsv'));

        $this->onTab($this->tabs);
    }

    public function onTab($sender) {

        $this->_tvars['tabcbadge'] = $sender->id == 'tabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabsbadge'] = $sender->id == 'tabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;

        $this->caltab->setVisible($sender->id == 'tabc');
        $this->tasktab->setVisible($sender->id == 'tabs');
        $this->updateTasks();
        $this->updateCal();
    }

    public function tasklistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $task = $row->getDataItem();

        $row->add(new Label('tasknumber', $task->document_number));
        $row->add(new Label('taskdesc', $task->notes));

        $row->add(new Label('taskdocument_date', H::fdt($task->headerdata['start'])));
        $row->add(new Label('taskhours', $task->headerdata['taskhours']));
        $stname = Document::getStateName($task->state);
        $row->add(new Label('taskstatus', $stname));

        if ($task->state == Document::STATE_EXECUTED) {
            $row->taskstatus->setText('<span class="badge badge-success">' . $stname . '</span>', true);
        }
        if ($task->state == Document::STATE_INPROCESS) {
            $row->taskstatus->setText('<span class="badge badge-info">' . $stname . '</span>', true);
        }
        if ($task->state == Document::STATE_SHIFTED) {
            $row->taskstatus->setText('<span class="badge badge-warning">' . $stname . '</span>', true);
        }
        if ($task->state == Document::STATE_CLOSED) {
            $row->taskstatus->setText('<span class="badge badge-secondary">' . $stname . '</span>', true);
        }

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
        if ($task->document_id == @$this->_task->document_id) {
            $row->setAttribute('class', 'table-success');
        }
    }

    //панель кнопок
    public function taskshowOnClick($sender) {
        $this->_task = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_task, true)) {
            return;
        }


        $this->tasktab->statuspan->setVisible(true);

        // if ($this->_task->checkStates(array(Document::STATE_EXECUTED)) == false || $this->_task->status == Document::STATE_EDITED || $this->_task->status == Document::STATE_NEW) {
        if ($this->_task->state != Document::STATE_EXECUTED) {
            $this->tasktab->statuspan->statusform->bclosed->setVisible(true);
        } else {
            $this->tasktab->statuspan->statusform->bclosed->setVisible(false);
        }
        if ($this->_task->state < Document::STATE_EXECUTED) {
            $this->tasktab->statuspan->statusform->binprocess->setVisible(true);
            $this->tasktab->statuspan->statusform->bshifted->setVisible(true);
        } else {
            $this->tasktab->statuspan->statusform->binprocess->setVisible(false);
            $this->tasktab->statuspan->statusform->bshifted->setVisible(false);
        }
        if ($this->_task->state == Document::STATE_SHIFTED) {
            $this->tasktab->statuspan->statusform->binprocess->setVisible(true);
        }
        if ($this->_task->state == Document::STATE_INPROCESS) {
            $this->tasktab->statuspan->statusform->bshifted->setVisible(true);
        }
        $this->tasktab->statuspan->statusform->bitems->setVisible($this->_task->state != Document::STATE_CLOSED);
        $this->tasktab->statuspan->statusform->bgoods->setVisible($this->_task->state != Document::STATE_CLOSED);
        $this->tasktab->statuspan->statusform->bact->setVisible($this->_task->state != Document::STATE_CLOSED);

        $this->tasktab->statuspan->docview->setDoc($this->_task);

        $this->tasktab->tasklist->Reload(false);
        $this->goAnkor('dankor');
    }

    public function taskeditOnClick($sender) {
        $task = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($task, true)) {
            return;
        }

        Application::Redirect("\\App\\Pages\\Doc\\Task", $task->document_id);
    }

    public function statusOnSubmit($sender) {

        if (\App\Acl::checkChangeStateDoc($this->_task, true, true) == false) {
            return;
        }


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
        if ($sender->id == 'bgoods') {    //Оприходование гоотовой продукции
            $d = $this->_task->getChildren('ProdReceipt');
            if (count($d) > 0) {

                $this->setWarn('exists_prodreceipt');
            }
            Application::Redirect("\\App\\Pages\\Doc\\ProdReceipt", 0, $this->_task->document_id);
            return;
        }
        if ($sender->id == 'bact') {    //Акт выполненых работ
            $d = $this->_task->getChildren('ServiceAct');
            if (count($d) > 0) {

                $this->setWarn('exists_serviceact');
            }
            Application::Redirect("\\App\\Pages\\Doc\\ServiceAct", 0, $this->_task->document_id);
            return;
        }

        $this->tasktab->statuspan->setVisible(false);

        $this->tasktab->tasklist->Reload(false);
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
        $this->tasktab->tasklist->Reload();

        $this->updateCal();

        $this->tasktab->statuspan->setVisible(false);
    }

    //обновить календар

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
            $d = ($item->headerdata['taskhours']);
            $end_date = $item->headerdata['start'] + round(3600 * $d);

            $tasks[] = new \ZCL\Calendar\CEvent($item->document_id, $item->document_number, $item->headerdata['start'], $end_date, $col);
        }


        $this->caltab->calendar->setData($tasks);
    }

    public function eraseFilter($sender) {


        $this->filterform->clean();

        $this->updateTasks();
    }

    public function OnCal($sender, $action) {
        if ($action['action'] == 'click') {

            $task = Document::load($action['id']);

            $class = "\\App\\Pages\\Doc\\Task";

            Application::Redirect($class, $task->document_id);
            return;
        }
        if ($action['action'] == 'add') {

            $start = strtotime($action['id'] . ' 9:00');

            Application::Redirect("\\App\\Pages\\Doc\\Task", 0, 0, $start);
            return;
        }
        if ($task->state == Document::STATE_CLOSED) {
            return;
        }
        if ($action['action'] == 'move') {
            $task = Task::load($action['id']);

            if ($action['years'] <> 0) {
                $task->headerdata['start'] = strtotime($action['years'] . ' years', $task->headerdata['start']);
            }
            if ($action['months'] <> 0) {
                $task->headerdata['start'] = strtotime($action['months'] . ' months', $task->headerdata['start']);
            }
            if ($action['days'] <> 0) {
                $task->headerdata['start'] = strtotime($action['days'] . ' days', $task->headerdata['start']);
            }
            if ($action['ms'] <> 0) {
                $task->headerdata['start'] = $task->headerdata['start'] + $action['ms'];
            }

            $task->document_date = $task->headerdata['start'];
        }
        if ($action['action'] == 'resize') {
            $task = Document::load($action['id']);
            if ($action['startdelta'] != 0) {
                $task->document_date = $task->document_date + ($action['startdelta']);
                $task->headerdata['start'] = $task->headerdata['start'] + ($action['enddelta'] / 3600);
                $task->headerdata['taskhours'] = $task->headerdata['taskhours'] + (0 - $action['enddelta'] / 3600);
            }
            if ($action['enddelta'] != 0) {
                $task->headerdata['taskhours'] = $task->headerdata['taskhours'] + $action['enddelta'] / 3600;
            }
        }
        $task->save();

        //  $this->updateCal();
        //  $this->updateTasks();
    }

    public function OnFilter($sender) {


        $this->updateTasks();
    }

    public function oncsv($sender) {
        $list = $this->tasktab->tasklist->getDataSource()->getItems(-1, -1, 'document_id');

        $header = array();
        $data = array();

        $i = 0;
        foreach ($list as $task) {
            $i++;
            $data['A' . $i] = $task->document_number;
            $data['B' . $i] = $task->notes;
            $data['C' . $i] = H::fdt($task->document_date);
            $data['D' . $i] = $task->headerdata['taskhours'];
            $data['E' . $i] = Document::getStateName($task->state);
            $data['F' . $i] = $task->notes;
        }

        H::exportExcel($data, $header, 'taskslist.xlsx');
    }

}
