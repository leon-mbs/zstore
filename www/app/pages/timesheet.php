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
use Zippy\Html\DataList\ArrayDataSource;

class TimeSheet extends \App\Pages\Base
{

    private $_time_id = 0;
    public  $_list    = array();
    public  $_stat    = array();

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $def = 0;
        $list = array();
        $emps = Employee::findArray('emp_name', 'disabled<>1', 'emp_name');

        $user = System::getUser();
        if ($user->employee_id > 0) {
            $def = $user->employee_id;
            $list = array($user->employee_id => $emps[$user->employee_id]);
        }
        if ($user->rolename == 'admins') {
            $list = $emps;
        }

        $this->filter->add(new DropDownChoice('emp', $list, $def));

        $dt = new \App\DateTime();
     
        $from = $dt->startOfMonth()->getTimestamp();
        $to = $dt->endOfMonth()->getTimestamp();

        $this->filter->add(new Date('from', $from));
        $this->filter->add(new Date('to', $to));

        $this->add(new Panel('tpanel'))->setVisible(false);

        $tcal = $this->tpanel->add(new Panel('tcal'));
        $tagen = $this->tpanel->add(new Panel('tagen'));
        $tstat = $this->tpanel->add(new Panel('tstat'));

        $this->tpanel->add(new ClickLink('tabc', $this, 'onTab'));
        $this->tpanel->add(new ClickLink('taba', $this, 'onTab'));
        $this->tpanel->add(new ClickLink('tabs', $this, 'onTab'));

        $this->tpanel->add(new ClickLink('addnew', $this, 'AddNew'));

        $tagen->add(new DataView('llist', new ArrayDataSource($this, '_list'), $this, 'listOnRow'));
        $tstat->add(new DataView('lstat', new ArrayDataSource($this, '_stat'), $this, 'statOnRow'));

        $tcal->add(new  \ZCL\Calendar\Calendar('calendar', $this->lang))->setEvent($this, 'OnCal');

        $this->add(new Form('editform'))->onSubmit($this, 'timeOnSubmit');
        $this->editform->setVisible(false);
        $this->editform->add(new DropDownChoice('edittype', TimeItem::getTypeTime(), TimeItem::TIME_WORK));
        $this->editform->add(new TextInput('editnote'));
        $this->editform->add(new \Zippy\Html\Form\Time('editfrom'));
        $this->editform->add(new \Zippy\Html\Form\Time('editto'));
        $this->editform->add(new TextInput('editbreak'));
        $this->editform->add(new Date('editdate', time()));
        $this->editform->add(new Button('cancel'))->onClick($this, 'onCancel');

        $this->onTab($this->tpanel->tabc);
        $this->filterOnSubmit($this->filter);
    }

    public function onTab($sender) {

        $this->_tvars['tabcbadge'] = $sender->id == 'tabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tababadge'] = $sender->id == 'taba' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;
        $this->_tvars['tabsbadge'] = $sender->id == 'tabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;

        $this->tpanel->tcal->setVisible($sender->id == 'tabc');
        $this->tpanel->tagen->setVisible($sender->id == 'taba');
        $this->tpanel->tstat->setVisible($sender->id == 'tabs');
    }

    public function filterOnSubmit($sender) {
        $emp_id = $this->filter->emp->getValue();
        $this->tpanel->setVisible($emp_id > 0);
        if ($emp_id > 0) {
            $this->updateList();
        }
    }

    public function onCancel($sender) {
        $this->filter->setVisible(true);
        $this->tpanel->setVisible(true);
        $this->editform->setVisible(false);
    }

    public function AddNew($sender) {

        $common = System::getOptions("common");

        $this->filter->setVisible(false);
        $this->tpanel->setVisible(false);
        $this->editform->setVisible(true);
        $this->editform->editdate->setDate(time());
        $this->editform->editfrom->setText($common['ts_start'] == null ? '09:00' : $common['ts_start']);
        $this->editform->editto->setText($common['ts_end'] == null ? '18:00' : $common['ts_end']);
        $this->editform->editbreak->setText($common['ts_break'] == null ? '60' : $common['ts_break']);
        $this->editform->editnote->setText('');

        $this->editform->edittype->setValue(TimeItem::TIME_WORK);
        $this->_time_id = 0;
    }

    public function editOnClick($sender) {
        $time = $sender->getOwner()->getDataItem();
        $this->_time_id = $time->time_id;
        $this->edit();
    }

    private function edit() {

        $this->filter->setVisible(false);
        $this->tpanel->setVisible(false);
        $this->editform->setVisible(true);

        $time = TimeItem::load($this->_time_id);

        $this->editform->editdate->setDate($time->t_start);
        $this->editform->editfrom->setText(date('H:i', $time->t_start));
        $this->editform->editto->setText(date('H:i', $time->t_end));
        $this->editform->editnote->setText($time->description);
        $this->editform->editbreak->setText($time->t_break);
        $this->editform->edittype->setValue($time->t_type);
    }

    public function timeOnSubmit($sender) {
        $time = new TimeItem();
        $time->time_id = $this->_time_id;
        $time->description = $sender->editnote->getText();
        $time->t_break = $sender->editbreak->getText();
        $time->emp_id = $this->filter->emp->getValue();
        if ($time->emp_id == 0) {
            $this->setError('noempselected');
            return;
        }
        $time->t_type = $sender->edittype->getValue();
        $from = $sender->editdate->getText() . ' ' . $sender->editfrom->getText();
        $to = $sender->editdate->getText() . ' ' . $sender->editto->getText();
        $time->t_start = strtotime($from);
        $time->t_end = strtotime($to);

        $v = $time->isValid();
        if (strlen($v) > 0) {
            $this->setError($v);
            return;
        }

        if ($this->_tvars["usebranch"]) {
            if ($this->branch_id == 0) {
                $this->setError('selbranch');
                return;
            }
            $time->branch_id = $this->branch_id;
        }


        $time->save();

        $this->updateList();

        $this->filter->setVisible(true);
        $this->tpanel->setVisible(true);
        $this->editform->setVisible(false);
    }

    public function OnCal($sender, $action) {
        if ($action['action'] == 'click') {

            $this->_time_id = $action['id'];
            if ($this->_time_id > 0) {
                $this->edit();
            }
        }
        if ($action['action'] == 'add') {

            $start = strtotime($action['id']);
            $this->AddNew(null);
            $this->editform->editdate->setDate($start);
        }
    }

    private function updateCal() {

        $tasks = array();

        foreach ($this->_list as $item) {

            $col = "#bbb";

            if ($item->t_type == TimeItem::TIME_WORK) {
                $col = "#007bff";
            }
            if ($item->t_type == TimeItem::TINE_BT) {
                $col = "#17a2b8";
            }
            if ($item->t_type == TimeItem::TINE_HL) {
                $col = "#28a745";
            }
            if ($item->t_type == TimeItem::TINE_ILL) {
                $col = "#ffc107";
            }
            if ($item->t_type == TimeItem::TINE_OVER) {
                $col = "#dc3545";
            }
            if ($item->t_type == TimeItem::TINE_WN) {
                $col = "#dc3545";
            }
            if ($item->t_type == TimeItem::TINE_OTHER) {
                $col = "#bbb";
            }


            $tasks[] = new \ZCL\Calendar\CEvent($item->time_id, '', $item->t_start, $item->t_end, $col);
        }


        $this->tpanel->tcal->calendar->setData($tasks);
    }

    private function updateList() {
        $emp_id = $this->filter->emp->getValue();
        $conn = \ZDB\DB::getConnect();
        $t_start = $conn->DBDate($this->filter->from->getDate());
        $t_end = $conn->DBDate($this->filter->to->getDate(true));
        $w = "emp_id = {$emp_id} and  t_start>={$t_start} and   t_start<{$t_end} ";

        if ($this->_tvars["usebranch"] && $this->branch_id > 0) {
            $w .= " and branch_id=" . $this->branch_id;
        }


        $this->_list = TimeItem::find($w, 't_start');
        $this->tpanel->tagen->llist->Reload();

        $tn = TimeItem::getTypeTime();
        $this->_stat = array();
        $stat = $conn->Execute("select t_type,sum(tm) as tm  from (select t_type,  (UNIX_TIMESTAMP(t_end)-UNIX_TIMESTAMP(t_start)  - t_break*60)   as  tm from timesheet where  emp_id = {$emp_id} and  t_start>={$t_start} and   t_start<{$t_end} ) t  group by t_type order by t_type ");
        foreach ($stat as $row) {
            $t = new \App\DataItem();
            $t->t_type = $row['t_type'];
            $t->val = $row['tm'];
            $t->name = $tn[$row['t_type']];

            $this->_stat[] = $t;
        }

        $this->tpanel->tstat->lstat->Reload();

        $this->updateCal();
    }

    public function listOnRow($row) {
        $item = $row->getDataItem();
        $tl = TimeItem::getTypeTime();
        $row->add(new Label('ldate', date('Y-m-d', $item->t_start)));
        $row->add(new Label('lfrom', date('H:i', $item->t_start)));
        $row->add(new Label('lto', date('H:i', $item->t_end)));
        $row->add(new Label('ltypename', $tl[$item->t_type]));
        $row->add(new Label('ldesc', $item->description));
        $row->add(new Label('lbranch', $item->branch_id > 0 ? $item->branch_name : ''));

        $diff = $item->t_end - $item->t_start - ($item->t_break * 60);
        $diff = number_format($diff / 3600, 2, '.', '');

        $row->add(new Label('ldur', $diff));
        if ($item->t_type == TimeItem::TIME_WORK) {
            $row->ldur->setAttribute('class', 'badge badge-primary');
        }
        if ($item->t_type == TimeItem::TINE_BT) {
            $row->ldur->setAttribute('class', 'badge badge-info');
        }
        if ($item->t_type == TimeItem::TINE_HL) {
            $row->ldur->setAttribute('class', 'badge badge-success');
        }
        if ($item->t_type == TimeItem::TINE_ILL) {
            $row->ldur->setAttribute('class', 'badge badge-warning');
        }
        if ($item->t_type == TimeItem::TINE_OVER) {
            $row->ldur->setAttribute('class', 'badge badge-danger');
        }
        if ($item->t_type == TimeItem::TINE_WN) {
            $row->ldur->setAttribute('class', 'badge badge-danger');
        }
        if ($item->t_type == TimeItem::TINE_OTHER) {
            $row->ldur->setAttribute('class', 'badge badge-light');
        }
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function statOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('stypename', $item->name));
        $row->add(new Label('scnt', number_format($item->val / 3600, 2, '.', '')));

        if ($item->t_type == TimeItem::TIME_WORK) {
            $row->scnt->setAttribute('class', 'badge badge-primary');
        }
        if ($item->t_type == TimeItem::TINE_BT) {
            $row->scnt->setAttribute('class', 'badge badge-info');
        }
        if ($item->t_type == TimeItem::TINE_HL) {
            $row->scnt->setAttribute('class', 'badge badge-success');
        }
        if ($item->t_type == TimeItem::TINE_ILL) {
            $row->scnt->setAttribute('class', 'badge badge-warning');
        }
        if ($item->t_type == TimeItem::TINE_OVER) {
            $row->scnt->setAttribute('class', 'badge badge-danger');
        }
        if ($item->t_type == TimeItem::TINE_WN) {
            $row->scnt->setAttribute('class', 'badge badge-danger');
        }
        if ($item->t_type == TimeItem::TINE_OTHER) {
            $row->scnt->setAttribute('class', 'badge badge-light');
        }
    }

    public function deleteOnClick($sender) {

        $item = $sender->owner->getDataItem();

        TimeItem::delete($item->time_id);

        $this->updateList();
    }

}
