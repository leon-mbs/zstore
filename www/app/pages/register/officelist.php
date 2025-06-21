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
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * журнал  офисных документов
 */
class OfficeList extends \App\Pages\Base
{
    private $_doc = null;
    public  $_tag = '';

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct($docid = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('OfficeList')) {
            \App\Application::RedirectHome();
        }


        $this->add(new \Zippy\Html\Link\LinkList("taglist"))->onClick($this, 'OnTagList');


        $conn = \ZDB\DB::getConnect();
        $names = $conn->GetCol("select distinct notes from documents_view where  meta_name='OfficeDoc' order  by notes");

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->filter->add(new CheckBox('archive'));
        $this->filter->add(new TextInput('searchcontext'));
        $this->filter->add(new Date('from',strtotime('-1 month')));
        $this->filter->add(new Date('to'));
        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtype'));
        $this->filter->searchtype->setDataList($names);
        $this->filter->add(new ClickLink('erase', $this, "onErase"));

        $doclist = $this->add(new DataView('doclist', new OfficeListDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new \App\Widgets\DocView('docview'));
        $this->statuspan->add(new Form('buttons'));
        $this->statuspan->buttons->add(new SubmitButton('binprocess'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->buttons->add(new SubmitButton('bdone'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->buttons->add(new SubmitButton('bclose'))->onClick($this, 'statusOnSubmit');

        $this->statuspan->buttons->add(new SubmitButton('bapprove'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->buttons->add(new SubmitButton('brefuse'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->buttons->add(new SubmitButton('bshift'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->buttons->add(new SubmitButton('bcancel'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->buttons->add(new SubmitButton('bdelete'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->buttons->add(new TextInput('refusecomm'));


        $this->statuspan->add(new Form('maint'));
        $this->statuspan->maint->add(new DropDownChoice('musers', array()));
        $this->statuspan->maint->add(new SubmitButton('buser'))->onClick($this, 'maintOnSubmit');

        $this->Reload();

        if ($docid > 0) {
            $doc = Document::load($docid);
            $this->_doc = $doc->cast();
            $this->show();

        }


    }

    public function filterOnSubmit($sender) {


        $this->statuspan->setVisible(false);

        $this->Reload();
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();
        $doc = $doc->cast();
        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', H::fd($doc->document_date)));

        $notes = ($doc->headerdata['customer_name'] ?? '') . ' ' . ($doc->headerdata['employee_name'] ?? '');

        $row->add(new Label('notes', trim($notes)));
        $stname = Document::getStateName($doc->state);

        $row->add(new Label('state', $stname));
        $row->add(new Label('user', $doc->username));

        $row->add(new ClickLink('title'))->onClick($this, 'showOnClick');
        $row->title->setValue($doc->notes);
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('copy'))->onClick($this, 'copyOnClick');
        $row->add(new ClickLink('access', $this, 'accessOnClick'))->setVisible(false);
        $user = System::getUser();
        if ($user->rolename == 'admins' && $user->user_id == $doc->headerdata['author']) {
            $row->access->setVisible(true);
        }


        if (in_array($doc->state, [1, 2, 3, 7])) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
        if (in_array($doc->state, [1, 2, 3, 9])) {
            $row->access->setVisible(false);
        }


        if ($doc->document_id == ($this->_doc->document_id ?? 0)) {
            $row->setAttribute('class', 'table-success');
        }

        list($s, $wa) = $doc->signed();
        $ua = array_keys($wa);

        if (count($ua) > 0 && in_array($doc->state, [7, 8])) {
            if (in_array($user->user_id, $ua)) {
                $row->state->setAttribute('class', 'text-danger');
                $row->state->setText("Очікує ващ підпис");
            } else {
                $un = array_values($wa);
                $names = implode(", ", $un);
                $row->state->setText("Очікує підпис від: " . $names);
            }
        }

    }


    //просмотр
    public function showOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $doc = Document::load($doc->document_id);
        $this->_doc = $doc->cast();
        $this->show();
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem()->cast();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }
        if (false == $doc->checkEdit(System::getUser())) {
            return;
        }

        $class = "\\App\\Pages\\Doc\\OfficeDoc";

        App::Redirect($class, $doc->document_id);
    }

    public function copyOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $doc = $doc->cast();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }
        if (false == $doc->checkShow(System::getUser())) {
            return;
        }
        $class = "\\App\\Pages\\Doc\\OfficeDoc";

        App::Redirect($class, 0, $doc->document_id);
    }

    public function accessOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();

        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }
        $class = "\\App\\Pages\\Doc\\OfficeDoc";

        App::Redirect($class, $doc->document_id, 0, true);
    }

    public function onErase($sender) {
        $this->_tag = '';
        $form = $sender->getOwner();
        $form->clean();
        $this->filterOnSubmit($form);


    }


    public function Reload($flag = true) {
        $this->doclist->Reload($flag);


        $this->taglist->Clear();
        $tags = \App\Entity\Tag::getTags(\App\Entity\Tag::TYPE_OFFICEDCO);
        foreach ($tags as $tag) {
            $this->taglist->addClickLink($tag, '#' . $tag);
        }


    }

    public function OnTagList($sender) {
        $this->_tag = $sender->getSelectedValue();


        $this->Reload();

    }

    public function show() {


        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }
        if (false == $this->_doc->checkShow(System::getUser())) {
            return;
        }

        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);
        $ch = \App\ACL::checkExeDoc($this->_doc, true, false);

        $this->Reload(false);

        $this->goAnkor('dankor');
        //    $this->statuspan->buttons->setVisible($this->_doc->state > 3);
        $this->statuspan->maint->setVisible($this->_doc->state > 3);


        $u = array();

        foreach (\App\Entity\User::find("disabled <> 1", "username asc") as $_u) {
            if ($_u->rolename == 'admins') {
                $u[$_u->user_id] = $_u->username;
            } else {
                $aclexe = explode(',', $_u->aclexe);

                if (in_array($this->_doc->meta_id, $aclexe)) {
                    $u[$_u->user_id] = $_u->username;

                }
                $aclstate = explode(',', $_u->aclstate);

                if (in_array($this->_doc->meta_id, $aclstate)) {
                    $u[$_u->user_id] = $_u->username;

                }

            }
        }
        $this->statuspan->maint->musers->setOptionList($u);
//      
        if (in_array($this->_doc->user_id, array_keys($u))) {
            $this->statuspan->maint->musers->setValue($this->_doc->user_id);
        } else {
            $this->statuspan->maint->musers->setValue(0);
        }

        $state = $this->_doc->state;
        $buttons = $this->statuspan->buttons;

        $buttons->binprocess->setVisible(false);
        $buttons->bdone->setVisible(false);
        $buttons->bclose->setVisible(false);

        $buttons->bshift->setVisible(false);
        $buttons->bcancel->setVisible(false);
        $buttons->bdelete->setVisible(false);

        $buttons->bapprove->setVisible(false);
        $buttons->brefuse->setVisible(false);
        $buttons->refusecomm->setVisible(false);

        $user = System::getUser();

        if ($ch && in_array($state, [18, 19])) {
            $buttons->bclose->setVisible(true);
        }
        if ($ch && in_array($state, [7, 16, 19])) {
            $buttons->bdone->setVisible(true);
        }

        if ($ch && in_array($state, [1, 2, 3, 16])) {
            $buttons->binprocess->setVisible(true);
        }
        if ($ch && in_array($state, [7])) {
            $buttons->bshift->setVisible(true);
        }


        list($a, $wa) = $this->_doc->signed();

        if (in_array($user->user_id, array_keys($wa)) && in_array($state, [7, 8])) {
            $buttons->bapprove->setVisible(true);
            $buttons->brefuse->setVisible(true);
            $buttons->refusecomm->setVisible(true);
        }


        if ($user->rolename == 'admins' || $user->user_id == $this->_doc->headerdata['author']) {
            if ($state > 3) {
                $buttons->bcancel->setVisible(true);
            }

        }
        if ($user->rolename == 'admins' || $user->user_id == $this->_doc->headerdata['author']) {
            if (in_array($state, [1, 2, 3, 17])) {
                $buttons->bdelete->setVisible(true);
            }
        }

    }

    public function statusOnSubmit($sender) {

        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        try {

            if ($sender->id == 'binproсess') {
                $this->_doc->updateStatus(Document::STATE_INPROCESS);
            }
            if ($sender->id == 'bdone') {
                $this->_doc->updateStatus(Document::STATE_FINISHED);
            }
            if ($sender->id == 'bclose') {
                $this->_doc->updateStatus(Document::STATE_CLOSED);
            }
            if ($sender->id == 'bshift') {
                $this->_doc->updateStatus(Document::STATE_SHIFTED);
            }
            if ($sender->id == 'bcancel') {
                $this->_doc->updateStatus(Document::STATE_CANCELED);
            }
            if ($sender->id == 'bdelete') {
                $this->deldoc();
            }
            if ($sender->id == 'bapprove') {

                $this->_doc->sign($user->user_id);
                list($a, $wa) = $this->_doc->signed();
                if (count($wa) > 0) {
                    $this->_doc->updateStatus(Document::STATE_WA, true);
                } else {
                    $this->_doc->updateStatus(Document::STATE_APPROVED);
                }


            }
            if ($sender->id == 'brefuse') {
                $comment = trim($this->statuspan->buttons->refusecomm->getText());
                if (strlen($comment) == 0) {
                    $this->setError("Не введено коментар");
                    return;
                }

                $this->_doc->updateStatus(Document::STATE_REFUSED);

                $msg = new \App\Entity\Message();
                $msg->message = $comment;
                $msg->created = time();
                $msg->user_id = $user->user_id;
                $msg->item_id = $this->_doc->document_id;
                $msg->item_type = \App\Entity\Message::TYPE_DOC;
                $msg->save();

            }


            $conn->CommitTrans();
        } catch(\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();

            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;


        }
        $this->Reload(false);
        $this->statuspan->setVisible(false);
    }

    public function maintOnSubmit($sender) {
        if (\App\ACL::checkExeDoc($this->_doc, true, false) == false) {
            $this->setError('Немає права на дану операцiю');
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        try {

            if ($sender->id == "buser") {
                $user_id = intval($this->statuspan->maint->musers->getValue());
                if ($user_id == 0) {
                    return;
                }
                if ($user_id == $this->_doc->user_id) {
                    return;
                }

                $this->_doc->user_id = $user_id;
                $this->_doc->save();

                $this->_doc->insertLog($this->_doc->state, $this->_doc->user_id);


            }
            $conn->CommitTrans();
        } catch(\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();

            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;


        }
        $this->Reload(false);
        $this->statuspan->setVisible(false);

    }


    public function deldoc() {
        global $logger;

        if (false == \App\ACL::checkDelDoc($this->_doc, true)) {
            // return;
        }

        $list = $this->_doc->getChildren();
        if (count($list) > 0) {
            $this->setError("У документа є дочірні документи");

            return;
        }
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        try {

            $del = Document::delete($this->_doc->document_id);
            if (strlen($del) > 0) {
                $this->setError($del);
                $conn->RollbackTrans();

                return;
            }

            $conn->CommitTrans();


        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();

            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }


        $this->doclist->Reload(true);
        $this->resetURL();
    }


}

/**
 *  Источник  данных  для   списка  документов
 */
class OfficeListDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = "   meta_name = 'OfficeDoc'   ";

        $filterform = $this->page->filter;

        $from = $filterform->from->getDate();
        $to = $filterform->to->getDate();

        if ($from > 0) {
            $where .= " and  document_date >= " . $conn->DBDate($from);
        }
        if ($to > 0) {
            $where .= " and  document_date <= " . $conn->DBDate($to);
        }


        if (!$filterform->archive->isChecked()) {
            $where .= " and  state <> " . Document::STATE_CLOSED;
        }


        $st = trim($filterform->searchtype->getValue());
        if (strlen($st) > 9) {
            $st = $conn->qstr($st);
            $where = " notes  =  {$st} ";
        }
        $st = $filterform->searchcontext->getText();
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= "  and(   content like  {$st}    ) ";
        }
        $sn = trim($filterform->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " meta_name = 'OfficeDoc'   and document_number like  {$sn} ";
        }
        if (strlen($this->page->_tag) > 0) {

            $tag = Document::qstr($this->page->_tag);
            $where = " document_id in (select item_id from taglist where  tag_type=2 and tag_name={$tag} )";
        }
        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "document_date desc,document_id desc", $count, $start);

        return $docs;
    }

    public function getItem($id) {

    }

}
 

