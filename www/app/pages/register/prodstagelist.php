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
    public $_emps  = array();
    public $_dates = array();
    public $_docs  = array();


    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('ProdStageList')) {
            \App\Application::RedirectHome() ;
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
        $this->statuspan->add(new ClickLink("btntask", $this, "taskOnClick"));
        $this->statuspan->add(new ClickLink("btnfromprod", $this, "fromprodOnClick"));
        $this->statuspan->add(new ClickLink("btnservice", $this, "btnserviceOnClick"));
        $this->statuspan->add(new ClickLink("btnclose", $this, "closeOnClick"));
        $this->statuspan->add(new ClickLink("btnstop", $this, "stopOnClick"));
        $this->statuspan->add(new ClickLink("btnstart", $this, "startOnClick"));

        $this->statuspan->add(new DataView('doclist', new  ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_docs')), $this, 'onDocRow'));
        $this->statuspan->add(new \App\Widgets\DocView('docview'))->setVisible(false);
 
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

      
        $row->add(new Label('hasnotes'))->setVisible(strlen($st->notes) > 0);
        $row->hasnotes->setAttribute('title', $st->notes);

        $row->add(new ClickLink('card', $this, 'cardOnClick'))->setVisible(strlen($st->card) > 0);

        $row->add(new ClickLink('show', $this, 'showOnClick'));
        $row->add(new ClickLink('workers', $this, 'wOnClick'));
   


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
                $this->setError("Сумарний КТУ повинен бути 1");
                return;
            }

        }

        $this->_stage->emplist = $this->_emps;
        $this->_stage->save();
        $this->userspan->setVisible(false);
        $this->listpan->setVisible(true);
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
  public function btnserviceOnClick($sender) {
        if ($this->_stage->state == ProdStage::STATE_NEW) {
            $this->_stage->state = ProdStage::STATE_INPROCESS;
            $this->_stage->save();
        }
        Application::Redirect("\\App\\Pages\\Doc\\IncomeService", 0, 0, $this->_stage->st_id);

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
        $this->statuspan->btntask->setVisible(true);

        if ($this->_stage->state == ProdStage::STATE_NEW) {
            $this->statuspan->btnclose->setVisible(false);
            $this->statuspan->btnstop->setVisible(false);

        }
        if ($this->_stage->state == ProdStage::STATE_INPROCESS) {
            $this->statuspan->btnstart->setVisible(false);

        }
        if ($this->_stage->state == ProdStage::STATE_STOPPED || $this->_stage->state == ProdStage::STATE_NEW ) {
            $this->statuspan->btnstop->setVisible(false);
            $this->statuspan->btntoprod->setVisible(false);
            $this->statuspan->btnfromprod->setVisible(false);
            $this->statuspan->btntask->setVisible(false);
            $this->statuspan->btnservice->setVisible(false);

        }

        if ($this->_stage->state == ProdStage::STATE_FINISHED) {
            $this->statuspan->btnservice->setVisible(false);
            $this->statuspan->btntask->setVisible(false);
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
   
        $this->listpan->setVisible(true);

        $this->listpan->stlist->Reload();

        $this->_tvars['gtp'] = false;
        $this->_tvars['gtf'] = false;

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
