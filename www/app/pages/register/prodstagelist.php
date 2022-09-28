<?php

namespace App\Pages\Register;

use App\Application;
use App\Entity\ProdProc;
use App\Entity\ProdStage;
use App\Entity\ProdStageAgenda;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Time;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;

/**
 * журнал производственные этапы
 */
class ProdStageList extends \App\Pages\Base
{

    private $_stage = null;
    public  $_emps  = array();
    public  $_dates = array();
    public  $_docs  = array();


    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('ProdStageList')) {
            return;
        }

        $this->add(new Panel("listpan"));
        $this->listpan->add(new ClickLink("opencal", $this, "opencalOnClick"));

        $this->listpan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->listpan->filter->add(new DropDownChoice('fproc', ProdProc::findArray('procname', 'state=' . ProdProc::STATE_INPROCESS, 'procname'), 0));
        $this->listpan->filter->add(new DropDownChoice('fparea', \App\Entity\ProdArea::findArray('pa_name', '', 'pa_name'), 0));

        $stlist = $this->listpan->add(new DataView('stlist', new ProcStageDataSource($this), $this, 'stlistOnRow'));

        $this->listpan->add(new Paginator('pag', $stlist));
        $stlist->setPageSize(H::getPG());

        $this->add(new Panel("cardpan"))->setVisible(false);
        $this->cardpan->add(new Label("stagenamec"));
        $this->cardpan->add(new Label("carddata"));
        $this->cardpan->add(new ClickLink("backc", $this, "backOnClick"));

        $this->add(new Panel("userspan"))->setVisible(false);
        $this->userspan->add(new Label("stageh5"));
        $this->userspan->add(new Form("useraddform"))->onSubmit($this, "onAddEmp");

        $this->userspan->useraddform->add(new DropDownChoice('adduser', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name")));
        $this->userspan->useraddform->add(new  TextInput("addktu"));
        $this->userspan->add(new DataView('userslist', new  ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_emps')), $this, 'empOnRow'));

        $this->userspan->add(new Button("saveusers"))->onClick($this, "onSaveEmp");
        $this->userspan->add(new Button("cancelusers"))->onClick($this, "backOnClick");

        $this->add(new Panel("statuspan"))->setVisible(false);
        $this->statuspan->add(new Label("stagenames"));
        $this->statuspan->add(new ClickLink("backs", $this, "backOnClick"));
        $this->statuspan->add(new ClickLink("btntoprod", $this, "toprodOnClick"));
        $this->statuspan->add(new ClickLink("btnfromprod", $this, "fromprodOnClick"));
        $this->statuspan->add(new ClickLink("btnclose", $this, "closeOnClick"));
        $this->statuspan->add(new ClickLink("btnstop", $this, "stopOnClick"));
        $this->statuspan->add(new ClickLink("btnstart", $this, "startOnClick"));

        $this->statuspan->add(new DataView('doclist', new  ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_docs')), $this, 'onDocRow'));
        $this->statuspan->add(new \App\Widgets\DocView('docview'))->setVisible(false);


        $this->add(new Panel("calpan"))->setVisible(false);
        $this->calpan->add(new Label("stagenamed"));
        $this->calpan->add(new Label("planhours"));
        $this->calpan->add(new Label("facthours"));
        $this->calpan->add(new ClickLink("backd", $this, "backOnClick"));

        $this->calpan->add(new Form("calform"))->onSubmit($this, "onAddCal");
        $this->calpan->calform->add(new Date("addcaldate"));
        $this->calpan->calform->add(new Time("addcalfrom"));
        $this->calpan->calform->add(new Time("addcalto"));
        $this->calpan->add(new DataView('callist', new  ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_dates')), $this, 'onCalRow'));

        $this->add(new Panel("calendarpan"))->setVisible(false);
        $this->calendarpan->add(new ClickLink("backcal", $this, "backOnClick"));
        $this->calendarpan->add(new \ZCL\Calendar\Calendar('calendar', 'ua'))->setEvent($this, 'OnCal');
        $this->calendarpan->add(new Form('calfilter'));
        $this->calendarpan->calfilter->add(new SubmitButton('filterok'))->onClick($this, "onCalFilter");
        $this->calendarpan->calfilter->add(new DropDownChoice('calfilterpa', \App\Entity\ProdArea::findArray('pa_name', '', 'pa_name'), 0));
        $this->calendarpan->calfilter->add(new DropDownChoice('calfilterpp', ProdProc::findArray('procname', 'state=' . ProdProc::STATE_INPROCESS, 'procname'), 0))->onChange($this, "onProd");
        $this->calendarpan->calfilter->add(new DropDownChoice('calfilterps', array(), 0))->setVisible(false);
        $this->calendarpan->calfilter->add(new DropDownChoice('calfilteremp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name"), 0));

        $stlist->Reload();


    }

    public function filterOnSubmit($sender) {

        $this->listpan->stlist->Reload();
    }

    public function stlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $st = $row->getDataItem();

        $row->add(new Label('sname', $st->stagename));
        $row->add(new Label('pname', $st->procname));
        $row->add(new Label('snumber', $st->snumber));
        $row->add(new Label('sstate', ProdStage::getStateName($st->state)));

        $row->add(new Label('startdate', H::fd($st->startdate)));
        $row->add(new Label('enddate', H::fd($st->enddate)));
        $row->add(new Label('hasnotes'))->setVisible(strlen($st->notes) > 0);
        $row->hasnotes->setAttribute('title', $st->notes);

        $row->add(new ClickLink('card', $this, 'cardOnClick'))->setVisible(strlen($st->card) > 0);

        $row->add(new ClickLink('show', $this, 'showOnClick'));
        $row->add(new ClickLink('workers', $this, 'wOnClick'));
        $row->add(new ClickLink('agenda', $this, 'cOnClick'));


    }

    public function cardOnClick($sender) {
        $this->cardpan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_stage = $sender->getOwner()->getDataItem();

        $this->cardpan->stagenamec->setText($this->_stage->stagename);
        $this->cardpan->carddata->setText($this->_stage->card, true);

    }

    public function wOnClick($sender) {
        $this->userspan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_stage = $sender->getOwner()->getDataItem();
        $this->_emps = $this->_stage->emplist;

        $this->userspan->userslist->Reload();
        $this->userspan->stageh5->setText($this->_stage->stagename);


    }

    public function onAddEmp($sender) {
        $id = $sender->adduser->getValue();
        $ktu = $sender->addktu->getText();

        if ($id > 0 && $ktu > 0) {
            $emp = \App\Entity\Employee::load($id);
            $emp->ktu = $ktu;
            $this->_emps[$id] = $emp;
            $sender->clean();
            $this->userspan->userslist->Reload();
        }


    }

    public function empOnRow($row) {
        $e = $row->getDataItem();

        $row->add(new Label('username', $e->emp_name));
        $row->add(new Label('ktu', $e->ktu));
        $row->add(new ClickLink('deluser', $this, 'deluserOnClick'));

    }

    public function deluserOnClick($sender) {
        $e = $sender->getOwner()->getDataItem();
        $this->_emps = array_diff_key($this->_emps, array($e->employee_id => $this->_emps[$e->employee_id]));

        $this->userspan->userslist->Reload();
    }

    public function onSaveEmp($sender) {

        if (count($this->_emps) > 0) {
            $ktu = 0;
            foreach ($this->_emps as $emp) {
                $ktu += doubleval($emp->ktu);
            }
            if ($ktu != 1) {
                $this->setError("ktu1");
                return;
            }

        }

        $this->_stage->emplist = $this->_emps;
        $this->_stage->save();
        $this->userspan->setVisible(false);
        $this->listpan->setVisible(true);
    }


    public function cOnClick($sender) {
        $this->calpan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_stage = $sender->getOwner()->getDataItem();

        $this->calpan->stagenamed->setText($this->_stage->stagename);
        $this->calpan->planhours->setText($this->_stage->hours);

        $this->onCalUpdate();

    }

    public function onAddCal($sender) {
        $d = $sender->addcaldate->getDate();
        if ($d == false) {
            return;
        }
        $from = $sender->addcalfrom->getDateTime($d);
        $to = $sender->addcalto->getDateTime($d);
        if ($from >= $to) {
            $this->setError('ts_invalidinterval');
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $t_start = $conn->DBTimeStamp($from);
        $t_end = $conn->DBTimeStamp($to);

        $sql = " select  count(*)  from prodstageagenda where    st_id={$this->_stage->st_id}  and   (( {$t_start} between  enddate  and   startdate) or (  {$t_end} between startdate and enddate))";
        $cnt = $conn->GetOne($sql);

        if ($cnt > 0) {
            $this->setError('stpp_intersect_this');
            return;
        }

        $sql = " select  count(*)  from prodstageagenda_view where  pa_id={$this->_stage->pa_id} and  st_id<>{$this->_stage->st_id}  and   (( {$t_start} between  enddate  and   startdate) or (  {$t_end} between startdate and enddate))";
        $cnt = $conn->GetOne($sql);

        if ($cnt > 0) {
            $this->setWarn('stpp_intersect_other');
        }

        $ag = new ProdStageAgenda();
        $ag->startdate = $from;
        $ag->enddate = $to;
        $ag->st_id = $this->_stage->st_id;

        $ag->save();

        $this->onCalUpdate();
        $sender->clean();
    }

    public function onCalRow($row) {
        $st = $row->getDataItem();

        $row->add(new Label('date', H::fd($st->startdate)));
        $row->add(new Label('from', H::ft($st->startdate)));
        $row->add(new Label('to', H::ft($st->enddate)));
        $row->add(new Label('hours', number_format($st->hours, 1, '.', '')));
        $row->add(new ClickLink('delcal', $this, 'onCalDel'));

    }

    public function onCalDel($sender) {
        $sta = $sender->getOwner()->getDataItem();
        ProdStageAgenda::delete($sta->sta_id);
        $this->onCalUpdate();
    }

    public function onCalUpdate() {
        $conn = \ZDB\DB::getConnect();

        $this->_dates = ProdStageAgenda::find("st_id=" . $this->_stage->st_id, "startdate");

        $this->calpan->callist->Reload();
        $h = 0;
        foreach ($this->_dates as $d) {
            $h += $d->hours;
        }

        $this->calpan->facthours->setText(number_format($h, 1, '.', ''));

    }

    public function opencalOnClick($sender) {
        $this->calendarpan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->updateCal();

    }

    public function onCalFilter($sender) {

        $this->updateCal();

    }

    public function onProd($sender) {
        $pp_id = $sender->getValue();
        $this->calendarpan->calfilter->calfilterps->setVisible(false);
        $this->calendarpan->calfilter->calfilterps->setOptionList(array());
        $this->calendarpan->calfilter->calfilterps->setValue(0);
        if ($pp_id > 0) {
            $this->calendarpan->calfilter->calfilterps->setVisible(true);
            $this->calendarpan->calfilter->calfilterps->setOptionList(ProdStage::findArray('stagename', 'state <>2 and pp_id=' . $pp_id, 'stagename'));
        }

    }

    public function updateCal() {

        $tasks = array();
        $where = "pp_id in (select pp_id from prodproc where  state=1) and st_id in (select st_id from prodstage where  state <>2 )";
        $emp_id = $this->calendarpan->calfilter->calfilteremp->getValue();
        $stemps = array(0);
        if ($emp_id > 0) {

            foreach (ProdStage::find($where) as $ps) {
                $ei = array_keys($ps->emplist);
                if (in_array($emp_id, $ei)) {
                    $stemps[] = $ps->st_id;
                }
            }

            $where .= " and st_id in (" . implode(",", $stemps) . ") ";

        }

        $pp_id = $this->calendarpan->calfilter->calfilterpp->getValue();
        $st_id = $this->calendarpan->calfilter->calfilterps->getValue();
        $pa_id = $this->calendarpan->calfilter->calfilterpa->getValue();
        if ($pp_id > 0) {
            $where .= " and  pp_id=" . $pp_id;
        }
        if ($st_id > 0) {
            $where .= " and  st_id=" . $st_id;
        }
        if ($pa_id > 0) {
            $where .= " and  pa_id=" . $pa_id;
        }


        $items = ProdStageAgenda::find($where);

        foreach ($items as $item) {

            $col = "#00ff00";
            if($item->state==ProdStage::STATE_FINISHED) {
              $col = "#ACACAC";  
            }
            if($item->state==ProdStage::STATE_STOPPED) {
              $col = "#FFC0C0";   
            }
            
            $tasks[] = new \ZCL\Calendar\CEvent($item->sta_id, $item->stagename, $item->startdate, $item->enddate, $col);
        }

        $this->calendarpan->calendar->setData($tasks);

    }

    public function OnCal($sender, $action) {

        if ($action['action'] == 'move') {
            $task = ProdStageAgenda::load($action['id']);

            if ($action['years'] <> 0) {
                $task->startdate = strtotime($action['years'] . ' years', $task->startdate);
                $task->enddate = strtotime($action['years'] . ' years', $task->enddate);
            }
            if ($action['months'] <> 0) {
                $task->startdate = strtotime($action['months'] . ' months', $task->startdate);
                $task->enddate = strtotime($action['months'] . ' months', $task->enddate);
            }
            if ($action['days'] <> 0) {
                $task->startdate = strtotime($action['days'] . ' days', $task->startdate);
                $task->enddate = strtotime($action['days'] . ' days', $task->enddate);
            }
            if ($action['ms'] <> 0) {
                $task->startdate = $task->startdate + $action['ms'];
                $task->enddate = $task->enddate + $action['ms'];
            }

            $task->save();

            $this->updateCal();

        }

    }


    public function toprodOnClick($sender) {
        if ($this->_stage->state == ProdStage::STATE_NEW) {
            $this->_stage->state = ProdStage::STATE_INPROCESS;
            $this->_stage->save();
        }
        Application::Redirect("\\App\\Pages\\Doc\\ProdIssue", 0, 0, $this->_stage->st_id);

    }

    public function fromprodOnClick($sender) {
        if ($this->_stage->state == ProdStage::STATE_NEW) {
            $this->_stage->state = ProdStage::STATE_INPROCESS;
            $this->_stage->save();
        }
        Application::Redirect("\\App\\Pages\\Doc\\ProdReceipt", 0, 0, $this->_stage->st_id);

    }

    public function closeOnClick($sender) {
        $this->_stage->state = ProdStage::STATE_FINISHED;
        $this->_stage->save();
        $this->statuspan->setVisible(false);
        $this->listpan->setVisible(true);

        $this->listpan->stlist->Reload();
    }
    public function startOnClick($sender) {
        $this->_stage->state = ProdStage::STATE_INPROCESS;
        $this->_stage->save();
        $this->statuspan->setVisible(false);
        $this->listpan->setVisible(true);

        $this->listpan->stlist->Reload();
    }
    public function stopOnClick($sender) {
        $this->_stage->state = ProdStage::STATE_STOPPED;
        $this->_stage->save();
        $this->statuspan->setVisible(false);
        $this->listpan->setVisible(true);

        $this->listpan->stlist->Reload();
    }

    public function showOnClick($sender) {
        $this->statuspan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_stage = $sender->getOwner()->getDataItem();

        $this->statuspan->stagenames->setText($this->_stage->stagename);


        $this->statuspan->btnclose->setVisible(true);
        $this->statuspan->btnstart->setVisible(true);
        $this->statuspan->btnstop->setVisible(true);
        $this->statuspan->btntoprod->setVisible(true);
        $this->statuspan->btnfromprod->setVisible(true);

        if ($this->_stage->state == ProdStage::STATE_NEW) {
            $this->statuspan->btnclose->setVisible(false);
            $this->statuspan->btnstop->setVisible(false);

        }
        if ($this->_stage->state == ProdStage::STATE_INPROCESS) {
            $this->statuspan->btnstart->setVisible(false);

        }
        if ($this->_stage->state == ProdStage::STATE_STOPPED) {
            $this->statuspan->btnstop->setVisible(false);
            $this->statuspan->btntoprod->setVisible(false);
            $this->statuspan->btnfromprod->setVisible(false);

        }

        if ($this->_stage->state == ProdStage::STATE_FINISHED) {
            $this->statuspan->btntoprod->setVisible(false);
            $this->statuspan->btnfromprod->setVisible(false);
            $this->statuspan->btnclose->setVisible(false);
            $this->statuspan->btnstart->setVisible(false);
            $this->statuspan->btnstop->setVisible(false);

        }

        $this->_docs = \App\Entity\Doc\Document::find("state>4 and meta_name in('ProdReceipt','ProdIssue') and content like '%<st_id>{$this->_stage->st_id}</st_id>%'   ", "document_id");
        $this->statuspan->doclist->Reload();

        $this->statuspan->docview->setVisible(false);

    }

    public function onDocRow($row) {
        $doc = $row->getDataItem();
        $row->add(new Label("docnumber", $doc->document_number));
        $row->add(new Label("docname", $doc->meta_desc));
        $row->add(new Label("docdate", H::fd($doc->document_date)));
        $row->add(new ClickLink("viewdoc", $this, "onViewDoc"));

    }

    public function onViewDoc($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $this->statuspan->docview->setVisible(true);
        $this->statuspan->docview->setDoc($doc);


    }

    public function backOnClick($sender) {
        $this->cardpan->setVisible(false);
        $this->userspan->setVisible(false);
        $this->statuspan->setVisible(false);
        $this->calpan->setVisible(false);
        $this->calendarpan->setVisible(false);
        $this->listpan->setVisible(true);

        $this->listpan->stlist->Reload();

    }


}

/**
 *  Источник  данных  для   списка  документов
 */
class ProcStageDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $where = "procstate =" . ProdProc::STATE_INPROCESS;

        $proc = $this->page->listpan->filter->fproc->getValue();
        $parea = $this->page->listpan->filter->fparea->getValue();

        if ($proc > 0) {
            $where .= " and pp_id=" . $proc;
        }
        if ($parea > 0) {
            $where .= " and pa_id=" . $parea;
        }

        return $where;
    }

    public function getItemCount() {
        return ProdStage::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return ProdStage::find($this->getWhere(), "");
    }

    public function getItem($id) {

    }

}
