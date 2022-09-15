<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * журнал  услуг
 */
class SerList extends \App\Pages\Base
{

    private $_doc = null;

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('SerList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => H::l("opened"), 1 => H::l("newed"), 2 => H::l("st_inprocess"), 3 => H::l("all")), 0));

        $doclist = $this->add(new DataView('doclist', new SerListDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        $this->statuspan->statusform->add(new SubmitButton('bttn'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bfin'))->onClick($this, 'statusOnSubmit');

        $this->statuspan->statusform->add(new SubmitButton('binproc'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bref'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('btask'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new \Zippy\Html\Link\RedirectLink('btopay'));

        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function filterOnSubmit($sender) {


        $this->statuspan->setVisible(false);

        $this->doclist->Reload();
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa($doc->amount)));

        $row->add(new Label('customer', $doc->customer_name));

        $row->add(new Label('state', Document::getStateName($doc->state)));

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state < Document::STATE_EXECUTED) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
        if ($doc->document_id == @$this->_doc->document_id) {
            $row->setAttribute('class', 'table-success');
        }
        
    }

    public function statusOnSubmit($sender) {
        if (\App\Acl::checkChangeStateDoc($this->_doc, true, true) == false) {
            return;
        }

        $state = $this->_doc->state;

        $ttn = count($this->_doc->getChildren('GoodsIssue')) > 0;
        $task = count($this->_doc->getChildren('Task')) > 0;

        if ($sender->id == "btask") {
            if ($task) {

                $this->setWarn('task_exists');
            }
            App::Redirect("\\App\\Pages\\Doc\\Task", 0, $this->_doc->document_id);
        }
        if ($sender->id == "bttn") {
            if ($ttn) {
                $this->setWarn('goodsissue_exists');
            }
            App::Redirect("\\App\\Pages\\Doc\\GoodsIssue", 0, $this->_doc->document_id);
        }
        if ($sender->id == "bref") {
            if ($ttn || $task) {

                $this->setWarn('created_task_gi');
            }
            $this->_doc->updateStatus(Document::STATE_REFUSED);
        }

        if ($sender->id == "binproc") {
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
        }
        if ($sender->id == "bfin") {
            $this->_doc->updateStatus(Document::STATE_FINISHED);

            if($this->_doc->payamount > 0 && $this->_doc->payamount > $this->_doc->payed)   {
                $this->_doc->updateStatus(Document::STATE_WP);
                
            }         
           
            
            
        }

        

        $this->doclist->Reload(false);

        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {


        $state = $this->_doc->state;

        $this->statuspan->statusform->btopay->setVisible(false);

        //новый     
        if ($state < Document::STATE_EXECUTED) {
            $this->statuspan->statusform->binproc->setVisible(true);

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bfin->setVisible(false);
        }


        // в работе
        if ($state == Document::STATE_INPROCESS) {

            $this->statuspan->statusform->binproc->setVisible(false);

            $this->statuspan->statusform->bttn->setVisible(true);
            $this->statuspan->statusform->bref->setVisible(true);
            $this->statuspan->statusform->btask->setVisible(true);
            $this->statuspan->statusform->bfin->setVisible(true);
        }

        // выполнен
        if ($state == Document::STATE_FINISHED) {

            $this->statuspan->statusform->binproc->setVisible(false);

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bfin->setVisible(false);
        }
      // ждет оплату
        if ($state == Document::STATE_WP) {

            $this->statuspan->statusform->binproc->setVisible(false);

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bfin->setVisible(false);
        }

        //к  оплате
        if ($state == Document::STATE_WP) {
            
          if( $this->_doc->payamount > 0 &&  $this->_doc->payamount >  $this->_doc->payed) { 
              $this->statuspan->statusform->btopay->setVisible(true);
              $this->statuspan->statusform->btopay->setLink("App\\PAges\\Register\\PayBayList",array($this->_doc->document_id));
          }
          
        }
        //закрыт
        if ($state == Document::STATE_CLOSED) {
            $this->statuspan->statusform->binproc->setVisible(false);

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bfin->setVisible(false);
            $this->statuspan->statusform->setVisible(false);
        }
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        $this->_doc = $this->_doc->cast();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);

        $this->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }


        App::Redirect("\\App\\Pages\\Doc\\ServiceAct", $doc->document_id);
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');

        $header = array();
        $data = array();

        $i = 0;
        foreach ($list as $d) {
            $i++;
            $data['A' . $i] = H::fd($d->document_date);
            $data['B' . $i] = $d->document_number;
            $data['C' . $i] = $d->customer_name;
            $data['D' . $i] = $d->amount;
            $data['E' . $i] = $d->notes;
        }

        H::exportExcel($data, $header, 'serlist.xlsx');
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class SerListDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = "   meta_name  in( 'ServiceAct'  ) ";

        $status = $this->page->filter->status->getValue();
        if ($status == 0) {
            $where .= " and  state <>   " . Document::STATE_CLOSED;
        }
        if ($status == 1) {
            $where .= " and  state =  " . Document::STATE_NEW;
        }
        if ($status == 2) {
            $where .= " and state = " . Document::STATE_INPROCESS;
        }


        $st = trim($this->page->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and  (  notes like {$st} or    content like {$st}  )";
        }
        $sn = trim($this->page->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " meta_name  in( 'ServiceAct'  )  and document_number like  {$sn} ";
        }

        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "priority desc,  document_id desc", $count, $start);

        return $docs;
    }

    public function getItem($id) {

    }

}
