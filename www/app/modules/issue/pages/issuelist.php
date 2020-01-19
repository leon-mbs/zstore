<?php

namespace App\Modules\Issue\Pages;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Image;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\BookmarkableLink;
use \Zippy\Html\Link\SubmitLink;
use \ZCL\DB\EntityDataSource;
use \Zippy\Html\DataList\Paginator;
use \Zippy\Html\DataList\ArrayDataSource;
use \App\Application as App;
use \App\System;
use \App\Modules\Issue\Helper;
use \App\Filter;
use \App\Modules\Issue\Entity\Project;
use \App\Modules\Issue\Entity\Issue;
use \App\Entity\User;

/**
 * Главная страница
 */
class IssueList extends \App\Pages\Base {

    public $_user;
    public $_issue;
    public $_stlist = array();
    public $_msglist = array();
    public $_fileslist = array();

    public function __construct($id = 0, $project_id = 0) {
        parent::__construct();

        $this->_user = System::getUser();

        $allow = (strpos($this->_user->modules, 'issue') !== false || $this->_user->userlogin == 'admin');
        if (!$allow) {
            System::setErrorMsg('Нет права  доступа  к   модулю ');
            App::RedirectHome();
            return;
        }


        $this->add(new Form('filter'))->onSubmit($this, 'onFilter');
        $where = 'archived<>1';
        if ($project_id > 0)
            $where .= " or project_id=" . $project_id;
        $projects = Project::findArray('project_name', $where, 'project_name');
        $this->filter->add(new DropDownChoice('searchproject', $projects, $project_id));


        $this->filter->add(new TextInput('searchnumber'));
        if ($id > 0)
            $this->filter->searchnumber->setText($id);

        //пользователи ассоциированные с сотрудниками

        $users = User::findArray('username', 'user_id in (select user_id from issue_issuelist)', 'username');
        $user_id = System::getUser()->user_id;

        $where = 'status != ' . Issue::STATUS_CLOSED . ' and user_id=' . $user_id;
        if ($project_id > 0)
            $where .= " and project_id=" . $project_id;
        $cnt = Issue::findCnt($where);

        if ($id > 0 || $cnt == 0)
            $user_id = 0;

        $this->filter->add(new DropDownChoice('searchassignedto', $users, $user_id));

        $stlist = Issue::getStatusList();
        $stlist[0] = 'Открытые';
        $stlist[100] = 'Все';
        $this->filter->add(new DropDownChoice('searchstatus', $stlist, 0));

        $this->add(new ClickLink('addnew', $this, 'onNew'));

        $this->add(new Panel("listpan"));

        $list = $this->listpan->add(new DataView('list', new IssueDS($this), $this, 'listOnRow'));
        $list->setSelectedClass('table-success');
        $list->setPageSize(25);
        $this->listpan->add(new Paginator('pag', $list));

        /*
          $this->add(new Panel("editpan"))->setVisible(false);
          $this->editpan->add(new Form('editform'))->onSubmit($this, 'onSaveIssue');
          $this->editpan->editform->add(new TextInput('edittitle'));
          $this->editpan->editform->add(new TextArea('editcontent'));
          $this->editpan->editform->add(new DropDownChoice('editpr', array(0 => 'Нормальный', 1 => 'Высокий', -1 => 'Низкий'), 0));
          $this->editpan->editform->add(new TextInput('editprice'));
          $this->editpan->editform->add(new TextInput('edithours'));

          $this->editpan->editform->add(new ClickLink('editcancel', $this, 'onCancel'));

          $this->add(new Panel("msgpan"))->setVisible(false);
          $this->msgpan->add(new ClickLink('back', $this, 'onCancel'));
          $this->msgpan->add(new Label('mtitle'));
          $this->msgpan->add(new Label('mdesc'));
          $stform = $this->msgpan->add(new Form('stform'));

          $stform->add(new DropDownChoice('ststatus', $stlist, -1));
          $stform->add(new DropDownChoice('stpr', array(0 => 'Нормальный', 1 => 'Высокий', -1 => 'Низкий'), 0));
          $stform->add(new DropDownChoice('stuser', User::findArray('username', 'employee_id > 0', 'username'), 0));
          $stform->add(new TextInput('sthours'));
          $stform->add(new SubmitButton('ststatusok'))->onClick($this, "onStatus");
          $stform->add(new SubmitButton('stuserok'))->onClick($this, "onStatus");
          $stform->add(new SubmitButton('stprok'))->onClick($this, "onStatus");
          $stform->add(new SubmitButton('sthoursok'))->onClick($this, "onStatus");

          $this->msgpan->add(new DataView('stlist', new ArrayDataSource($this, '_stlist'), $this, 'stlistOnRow'));

          $this->msgpan->add(new Form('addmsgform'))->onSubmit($this, 'onAddMsg');
          $this->msgpan->addmsgform->add(new TextArea('msgdata'));
          $this->msgpan->add(new DataView('msglist', new ArrayDataSource($this, '_msglist'), $this, 'msgListOnRow'));

          $this->msgpan->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
          $this->msgpan->addfileform->add(new \Zippy\Html\Form\File('addfile'));
          $this->msgpan->add(new DataView('filelist', new ArrayDataSource($this, '_fileslist'), $this, 'fileListOnRow'));

         */
        $this->listpan->list->Reload();

        $this->add(new Panel("editpan"))->setVisible(false);

        $issue = Issue::load($id);
        if ($issue instanceof Issue) {
            $this->openIssue($issue);
        }
    }

    public function onNew($sender) {
        $this->editpan->editform->clean();
        $this->editpan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_issue = new Issue();
    }

    public function onCancel($sender) {

        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->msgpan->setVisible(false);

        $this->listpan->list->Reload();
    }

    public function onFilter($sender) {


        $this->listpan->list->Reload();
    }

    //вывод строки  списка   

    public function listOnRow($row) {
        $issue = $row->getDataItem();
        $row->add(new Label('issue_id', '#' . $issue->issue_id));
        $row->add(new Label('title', $issue->issue_name));
        $row->add(new Label('prup'))->setVisible($issue->priority == 1);
        $row->add(new Label('prdown'))->setVisible($issue->priority == -1);
    }

    public function editOnClick($sender) {

        $this->_issue = $sender->getOwner()->getDataItem();
        if ($this->_issue->status == Issue::STATUS_CLOSED) {
            $this->setError('Задача  закрыта');
            return;
        }


        if ($this->_user->username != 'admin' && $this->_user->user_id != $this->_issue->createdby) {
            $this->setError('Редактировать  может  только  автор или  администатор');
            return;
        }

        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);

        $this->editpan->editform->edittitle->setText($this->_issue->issue_name);
        $this->editpan->editform->editcontent->setText($this->_issue->desc);
        $this->editpan->editform->editpr->setValue($this->_issue->priority);
        $this->editpan->editform->edithours->setText($this->_issue->hours);
        $this->editpan->editform->editprice->setText($this->_issue->price);
    }

    public function onSaveIssue($sender) {


        $this->_issue->issue_name = $sender->edittitle->getText();
        $this->_issue->desc = $sender->editcontent->getText();
        $this->_issue->priority = $sender->editpr->getValue();
        $this->_issue->hours = $sender->edithours->getText();
        $this->_issue->price = $sender->editprice->getText();
        if ($this->_issue->issue_id == 0) {

            $this->_issue->createdby = $this->_user->user_id;
            $this->_issue->createdbyname = $this->_user->username;
        }
        $this->_issue->save();

        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->listpan->list->Reload();
    }

    public function openIssue($issue) {
        $this->_issue = $issue;

        $this->msgpan->setVisible(true);

        $this->msgpan->mtitle->setText('#' . $this->_issue->issue_id . ' ' . $this->_issue->issue_name);
        $this->msgpan->mdesc->setText($this->_issue->desc, true);
        $this->msgpan->stform->ststatus->setValue($this->_issue->status);
        $this->msgpan->stform->stpr->setValue($this->_issue->priority);
        $this->msgpan->stform->stuser->setValue($this->_issue->user_id);
        $this->msgpan->stform->sthours->setText('0');
        $this->updateStList();
        $this->updateMessages();

        $this->listpan->list->setSelectedRow($sender->getOwner());
        $this->listpan->list->Reload(false);
    }

    public function commentOnClick($sender) {

        $this->openIssue($sender->getOwner()->getDataItem());
    }

    public function deleteOnClick($sender) {

        $issue = $sender->getOwner()->getDataItem();

        if ($issue->status == Issue::STATUS_CLOSED) {
            $this->setError('Задача  закрыта');
            return;
        }


        if ($this->_user->username != 'admin' && $this->_user->user_id != $issue->createdby) {
            $this->setError('Удалить  может  только  автор или  администатор');
            return;
        }


        $msg = Issue::delete($issue->issue_id);
        if (strlen(msg) > 0) {
            $this->setError($msg);
            return;
        }
        $this->listpan->list->Reload();
    }

    public function onAddMsg($sender) {
        $msg = new \App\Entity\Message();
        $msg->message = $this->msgpan->addmsgform->msgdata->getText();
        $msg->created = time();
        $msg->user_id = $this->_user->user_id;
        $msg->item_id = $this->_issue->issue_id;
        $msg->item_type = \App\Entity\Message::TYPE_ISSUE;
        if (strlen($msg->message) == 0)
            return;
        $msg->save();

        $this->msgpan->addmsgform->msgdata->setText('');
        $this->updateMessages();


        $this->goAnkor('msgankor');
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type =5 and item_id=' . $this->_issue->issue_id);
        $this->msgpan->msglist->Reload();
        $this->_fileslist = \App\Helper::getFileList($this->_issue->issue_id, 5);
        $this->msgpan->filelist->Reload();
    }

    public function msgListOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('msgdate', date("Y-m-d H:i", $item->created)));
        $row->add(new Label('msguser', $item->username));
        $row->add(new Label('msgdata', $item->message));
        $row->add(new ClickLink('delmsg'))->onClick($this, 'deleteMmsOnClick');
        if ($this->_user->username == 'admin' || $this->_user->user_id == $item->user_id) {
            $row->delmsg->setVisible(true);
        } else {
            $row->delmsg->setVisible(false);
        }
    }

    public function deleteMmsOnClick($sender) {
        $msg = $sender->getOwner()->getDataItem();

        \App\Entity\Message::delete($msg->message_id);
        $this->updateMessages();
    }

    public function OnFileSubmit($sender) {

        $file = $sender->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->getOwnerPage()->setError("Файл больше 10М!");
            return;
        }

        \App\Helper::addFile($file, $this->_issue->issue_id, '', 5);

        $this->updateMessages();
    }

    public function filelistOnRow($row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . 'loadfile.php?id=' . $item->file_id));
        $file->setValue($item->filename);
        // $file->setAttribute('title', $item->description);

        $row->add(new ClickLink('delfile'))->onClick($this, 'deleteFileOnClick');

        if ($this->_user->username == 'admin' || $this->_user->user_id == $this->_issue->createdby) {
            $row->delfile->setVisible(true);
        } else {
            $row->delfile->setVisible(false);
        }
    }

    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        \App\Helper::deleteFile($file->file_id);
        $this->updateMessages();
    }

    public function onStatus($sender) {

        if ($sender->id == 'ststatusok') {
            $status = $this->msgpan->stform->ststatus->getValue();
            if ($status == $this->_issue->status)
                return;
            $this->_issue->status = $status;
            $this->_issue->lastupdate = time();
            $this->_issue->save();
            Helper::addHistory($this->_issue->issue_id, $status, null, 'Статус ' . $this->msgpan->stform->ststatus->getValueName());
            $this->updateStList();
            return;
        }
        if ($this->_issue->status == Issue::STATUS_CLOSED) {
            $this->setError('Задача  закрыта');
            return;
        }
        if ($sender->id == 'stprok') {
            $priority = $this->msgpan->stform->stpr->getValue();
            if ($priority == $this->_issue->priority)
                return;
            $this->_issue->priority = $priority;
            $this->_issue->lastupdate = time();
            $this->_issue->save();
            Helper::addHistory($this->_issue->issue_id, null, null, 'Приоритет ' . $this->msgpan->stform->stpr->getValueName());
        }



        if ($sender->id == 'stuserok') {
            $user_id = $this->msgpan->stform->stuser->getValue();
            if ($user_id == 0) {
                return;
            }
            if ($user_id == $this->_issue->user_id)
                return;

            $this->_issue->user_id = $user_id;
            $this->_issue->lastupdate = time();
            $this->_issue->save();
            Helper::addHistory($this->_issue->issue_id, null, null, 'Переназначена на  ' . $this->msgpan->stform->stuser->getValueName());

            $n = new \App\Entity\Notify();
            $n->user_id = $user_id;
            $n->message = " На  вас переведена задача <a href=\"/index.php?p=App/Modules/Issue/Pages/IssueList&arg={$this->_issue->issue_id}\">{$this->_issue->issue_name}</a> ";
            $n->save();
        }
        if ($sender->id == 'sthoursok') {
            $hours = $this->msgpan->stform->sthours->getText();
            if ($hours > 0) {
                Helper::addHistory($this->_issue->issue_id, null, $hours, "Добавлено время {$hours} ");
            }
            $this->msgpan->stform->sthours->setText('');
        }

        $this->updateStList();
    }

    public function stlistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('sttime', date('Y-m-d', $item->createdon)));
        $row->add(new Label('stuser', $item->username));
        $row->add(new Label('stnotes', $item->notes));
    }

    public function updateStList() {
        $this->_stlist = Helper::getHistoryList($this->_issue->issue_id);
        $this->msgpan->stlist->Reload();
    }

}

class IssueDS implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $status = $this->page->listpan->filter->searchstatus->getValue();
        $number = trim($this->page->listpan->filter->searchnumber->getText());
        $assignedto = $this->page->listpan->filter->searchassignedto->getValue();
        $project = $this->page->listpan->filter->searchproject->getKey();

        $conn = \ZDB\DB::getConnect();

        $where = " 1=1 ";
        if ($status == -1)
            $where .= " and status <> " . Issue::STATUS_CLOSED;
        if ($status < 100 && $status >= 0)
            $where .= " and status = " . $status;
        if ($project > 0)
            $where .= " and project_id = " . $project;
        if ($assignedto > 0)
            $where .= " and user_id = " . $assignedto;

        if (strlen($number) > 0) {
            $s = Issue::qstr('%' . $number . '%');

            $where .= " and (content like {$s} or issue_name like {$s} or issue_id=" . Issue::qstr($number) . ")  ";
        }

        return $where;
    }

    public function getItemCount() {
        return Issue::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $sort = "lastupdate desc";
        $s = $this->page->listpan->sort->sorttype->getValue();
        if ($s == 1)
            $sort = "issue_id desc";
        if ($s == 2)
            $sort = "priority desc";

        return Issue::find($this->getWhere(), $sort, $count, $start);
    }

    public function getItem($id) {
        
    }

}
