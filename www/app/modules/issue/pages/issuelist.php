<?php

namespace App\Modules\Issue\Pages;

use App\Application as App;
use App\Entity\User;
use App\Modules\Issue\Entity\Issue;
use App\Modules\Issue\Entity\Project;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Helper as H;

/**
 * Главная страница
 */
class IssueList extends \App\Pages\Base
{

    public $_user;
    public $_issue;
    public $_stlist    = array();
    public $_msglist   = array();
    public $_fileslist = array();

    public function __construct($id = 0, $project_id = 0, $new = false) {
        parent::__construct();

        $this->_user = System::getUser();

        $allow = (strpos($this->_user->modules, 'issue') !== false || $this->_user->rolename == 'admins');
        if (!$allow) {
            System::setErrorMsg(H::l('noaccesstopage'));
            App::RedirectError();
            return;
        }


        $this->add(new Form('filter'))->onSubmit($this, 'onFilter');
        $where = 'status <>' . Project::STATUS_CLOSED;
        if ($project_id > 0) {
            $where .= " or project_id=" . $project_id;
        }
        $projects = Project::findArray('project_name', $where, 'project_name');
        $this->filter->add(new DropDownChoice('searchproject', $projects, $project_id));

        $this->filter->add(new TextInput('searchnumber'));
        if ($id > 0) {
            $this->filter->searchnumber->setText($id);
        }

        //пользователи ассоциированные с сотрудниками

        $users = User::findArray('username', 'user_id in (select user_id from issue_issuelist)', 'username');

        $user_id = 0;

        if ($id == 0 && $project_id == 0) {
            $uid = System::getUser()->user_id;
            if (array_key_exists($uid, $users)) {
                $user_id = $uid; // Показываем  мои задачи
            }
        }

        $this->filter->add(new DropDownChoice('searchemp', $users, $user_id));

        $stlist = Issue::getStatusList();
        $stlist[0] = H::l('mopen');
        $stlist[100] = H::l('mall');
        $this->filter->add(new DropDownChoice('searchstatus', $stlist, 0));

        $this->add(new ClickLink('addnew', $this, 'onNew'));

        $this->add(new Panel("listpan"));

        $list = $this->listpan->add(new DataView('list', new IssueDS($this), $this, 'listOnRow'));
        $list->setSelectedClass('table-success');
        $list->setPageSize(15);
        $this->listpan->add(new Paginator('pag', $list));

        $msgpan = $this->listpan->add(new Panel("msgpan"));
        $msgpan->setVisible(false);

        $msgpan->add(new Label('mcreate'));
        $msgpan->add(new Label('mtitle'));
        $msgpan->add(new Label('mdesc'));
        $msgpan->add(new ClickLink('editissue', $this, 'editOnClick'));
        $msgpan->add(new ClickLink('deleteissue', $this, 'deleteOnClick'));

        $msgpan->add(new Form('addmsgform'))->onSubmit($this, 'onAddMsg');
        $msgpan->addmsgform->add(new TextArea('msgdata'));
        $msgpan->addmsgform->add(new \Zippy\Html\Form\CheckBoxList('userlist', '<br>'));
        $msgpan->add(new DataView('msglist', new ArrayDataSource($this, '_msglist'), $this, 'msgListOnRow'));
        $msgpan->msglist->setPageSize(15);
        $msgpan->add(new Paginator('pagmsg', $msgpan->msglist));

        $msgpan->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $msgpan->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $msgpan->add(new DataView('filelist', new ArrayDataSource($this, '_fileslist'), $this, 'fileListOnRow'));

        $stform = $msgpan->add(new Form('stform'));

        $stform->add(new DropDownChoice('ststatus', $stlist, -1));
        $stform->add(new DropDownChoice('stpr', array(Issue::PRIORITY_NORMAL => 'Нормальный', Issue::PRIORITY_HIGH => 'Высокий', Issue::PRIORITY_LOW => 'Низкий'), 0));
        $stform->add(new DropDownChoice('stuser', array(), 0));
        $stform->onSubmit($this, "onStatus");
        $msgpan->add(new DataView('stlist', new ArrayDataSource($this, '_stlist'), $this, 'stlistOnRow'));

        $this->listpan->list->Reload();

        $this->add(new Panel("editpan"))->setVisible(false);
        $this->editpan->add(new Form('editform'))->onSubmit($this, 'onSaveIssue');
        $this->editpan->editform->add(new TextInput('edittitle'));
        $this->editpan->editform->add(new TextArea('editcontent'));
        $this->editpan->editform->add(new DropDownChoice('editemp', User::findArray('username', '', 'username'), 0));
        $this->editpan->editform->add(new DropDownChoice('editpr', array(Issue::PRIORITY_NORMAL => 'Нормальный', Issue::PRIORITY_HIGH => 'Высокий', Issue::PRIORITY_LOW => 'Низкий'), 0));
        $this->editpan->editform->add(new DropDownChoice('editproj', $projects, $project_id));

        $this->editpan->editform->add(new TextInput('edithours'));

        $this->editpan->editform->add(new ClickLink('editcancel', $this, 'onCancel'));

        $issue = Issue::load($id);
        if ($issue instanceof Issue) {
            $this->openIssue($issue);
        }
        if ($new == true) {
            $this->onNew(null);
        }
    }

    public function onNew($sender) {
        $this->editpan->editform->clean();
        $this->editpan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_issue = new Issue();
        $this->editpan->editform->editproj->setValue($this->filter->searchproject->getValue());
    }

    public function onCancel($sender) {

        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->listpan->msgpan->setVisible(false);

        $this->listpan->list->Reload();
    }

    public function onFilter($sender) {
        $this->listpan->list->Reload();
    }

    //вывод строки  списка   

    public function listOnRow($row) {
        $issue = $row->getDataItem();
        $row->add(new RedirectLink('issue_number', "\\App\\Modules\\Issue\\Pages\\IssueList", array($issue->issue_id)))->setValue('#' . $issue->issue_id);

        $row->issue_number->setAttribute('class', 'badge badge-success');
        if ($issue->priority == Issue::PRIORITY_HIGH) {
            $row->issue_number->setAttribute('class', 'badge badge-danger');
        }
        if ($issue->priority == Issue::PRIORITY_LOW) {
            $row->issue_number->setAttribute('class', 'badge badge-warning');
        }

        $row->add(new ClickLink('title', $this, 'OnIssue'))->setValue($issue->issue_name);

        $row->add(new Label('emp', \App\Util::getLabelName($issue->username)));
        $row->emp->setAttribute('title', $issue->username);
        if ($this->_issue->issue_id == $issue) {
            $this->listpan->list->setSelectedRow($row);
        }
    }

    public function editOnClick($sender) {


        if ($this->_issue->status == Issue::STATUS_CLOSED) {
            $this->setError('issueclosed');

            return;
        }


        if ($this->_user->rolename != 'admins' && $this->_user->user_id != $this->_issue->createdby) {

            $this->setError('editallowedaa');
            return;
        }

        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);

        $this->editpan->editform->edittitle->setText($this->_issue->issue_name);
        $this->editpan->editform->editcontent->setText($this->_issue->desc);
        $this->editpan->editform->editpr->setValue($this->_issue->priority);
        $this->editpan->editform->editproj->setValue($this->_issue->project_id);
        $this->editpan->editform->editemp->setValue($this->_issue->user_id);
        $this->editpan->editform->edithours->setText($this->_issue->hours);
    }

    public function onSaveIssue($sender) {


        $this->_issue->issue_name = $sender->edittitle->getText();
        $this->_issue->desc = $sender->editcontent->getText();
        $this->_issue->project_id = $sender->editproj->getValue();
        $this->_issue->priority = $sender->editpr->getValue();
        $this->_issue->hours = $sender->edithours->getText();
        $this->_issue->user_id = $sender->editemp->getValue();
        if ($this->_issue->user_id == 0) {
            $this->_issue->user_id = $this->_user->user_id;
        }
        if ($this->_issue->issue_id == 0) {

            $this->_issue->createdby = $this->_user->user_id;
            $this->_issue->createdbyname = $this->_user->username;
        }
        if ($this->_issue->project_id == 0) {

            $this->setError('noselproject');
            return;
        }
        $emp = User::load($this->_issue->user_id);

        if ($emp->rolename != 'admins') {
            $pr = Project::load($this->_issue->project_id);
            if (in_array($this->_issue->user_id, $pr->getUsers()) == false) {
                $this->setError('empnoaccess');
                return;
            }
        }


        $idnew = $this->_issue->issue_id == 0;
        $this->_issue->save();
        if ($idnew) {
            $this->_issue->addStatusLog(H::l("iscreated"));
        }
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->listpan->list->Reload();
        $this->openIssue($this->_issue);
    }

    public function OnIssue($sender) {
        $this->openIssue($sender->getOwner()->getDataItem());
    }

    public function openIssue($issue) {
        $this->_issue = $issue;
        if ($this->_issue == null) {
            return;
        }
        $this->listpan->msgpan->setVisible(true);
        $bd = "badge-success";
        if ($this->_issue->priority == Issue::PRIORITY_HIGH) {
            $bd = "badge-danger";
        };
        if ($this->_issue->priority == Issue::PRIORITY_LOW) {
            $bd = "badge-warning";
        };

        $user_id = System::getUser()->user_id;
        $project = Project::load($this->_issue->project_id);
        $this->listpan->msgpan->mcreate->setText('Автор ' . $this->_issue->createdbyname . ' ' . \App\Helper::fd($this->_issue->createdon) . '&nbsp;Проект&nbsp;<a href="/project/' . $project->project_id . '">' . $project->project_name . '</a> ', true);

        $users = \App\Entity\User::findArray('username', "user_id <> {$user_id}  and user_id in (select user_id from issue_projectacc where project_id={$project->project_id} )", 'username');
        foreach ($users as $k => $v) {
            $this->listpan->msgpan->addmsgform->userlist->AddCheckBox($k, false, $v);
        }

        $this->listpan->msgpan->mtitle->setText('<span class="badge ' . $bd . '">#' . $this->_issue->issue_id . '</span> ' . $this->_issue->issue_name, true);
        $this->listpan->msgpan->mdesc->setText($this->_issue->desc, true);
        $this->listpan->msgpan->stform->ststatus->setValue($this->_issue->status);
        $this->listpan->msgpan->stform->stpr->setValue($this->_issue->priority);

        $users = \App\Entity\User::findArray('username', "  user_id in (select user_id from issue_projectacc where project_id={$project->project_id} )", 'username');

        $this->listpan->msgpan->stform->stuser->setOptionList($users);
        $this->listpan->msgpan->stform->stuser->setValue($this->_issue->user_id);

        $this->updateStList();
        $this->updateMessages();

        $this->listpan->list->Reload(false);

    }

    public function deleteOnClick($sender) {

        if ($this->_issue->status == Issue::STATUS_CLOSED) {
            $this->setError('issueclosed');
            return;
        }


        if ($this->_user->rolename != 'admins' && $this->_user->user_id != $this->_issue->createdby) {
            $this->setError('delallowedaa');

            return;
        }


        $msg = Issue::delete($this->_issue->issue_id);
        if (strlen($msg) > 0) {
            $this->setError($msg);
            return;
        }
        $this->listpan->list->Reload();
        $this->listpan->msgpan->setVisible(false);
        $this->resetURL();
    }

    public function onAddMsg($sender) {
        $msg = new \App\Entity\Message();
        $msg->message = $this->listpan->msgpan->addmsgform->msgdata->getText();
        $msg->created = time();
        $msg->user_id = $this->_user->user_id;
        $msg->item_id = $this->_issue->issue_id;
        $msg->item_type = \App\Entity\Message::TYPE_ISSUE;
        if (strlen($msg->message) == 0) {
            return;
        }
        $msg->save();

        $this->listpan->msgpan->addmsgform->msgdata->setText('');
        $this->updateMessages();

        $recievers = $this->listpan->msgpan->addmsgform->userlist->getCheckedList();
        if (!in_array($this->_issue->user_id, $recievers)) {
            $recievers[] = $this->_issue->user_id;
        }

        foreach ($recievers as $u) {
            if ($u == System::getUser()->userid) {
                continue;
            }
            $n = new \App\Entity\Notify();
            $n->user_id = $u;
            $n->message = H::l('msgtask') . "  #{$this->_issue->issue_id} {$this->_issue->issue_name} ";
            $n->message .= "<br>  <a href=\"/issue/{$this->_issue->issue_id}/{$this->_issue->project_id}/#msgankor\">" . H::l('msgreply') . "</a> ";
            $n->sender_id = System::getUser()->user_id;
            $n->save();
        }

        $this->goAnkor('msgankor');
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type = ' . \App\Entity\Message::TYPE_ISSUE . ' and item_id=' . $this->_issue->issue_id, 'message_id');
        $this->listpan->msgpan->msglist->Reload();
        $ocnt = $this->listpan->msgpan->msglist->getPageCount();
        $this->listpan->msgpan->msglist->setCurrentPage($ocnt);
        $this->listpan->msgpan->msglist->Reload(false);
        $this->_fileslist = \App\Helper::getFileList($this->_issue->issue_id, \App\Entity\Message::TYPE_ISSUE);
        $this->listpan->msgpan->filelist->Reload();
    }

    public function msgListOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('msgdate', \App\Helper::fdt($item->created)));
        $row->add(new Label('msguser', $item->username));
        $row->add(new Label('msgdata', nl2br($item->message), true));
        $row->add(new ClickLink('delmsg'))->onClick($this, 'deleteMmsOnClick');
        if ($this->_user->rolename == 'admins' || $this->_user->user_id == $item->user_id) {
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

            $this->setError("filemore10M");
            return;
        }

        \App\Helper::addFile($file, $this->_issue->issue_id, '', 5);

        $this->updateMessages();
        $this->goAnkor('afiles');
    }

    public function filelistOnRow($row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . 'loadfile.php?id=' . $item->file_id));
        $file->setValue($item->filename);
        // $file->setAttribute('title', $item->description);

        $row->add(new ClickLink('delfile'))->onClick($this, 'deleteFileOnClick');

        if ($this->_user->rolename == 'admins' || $this->_user->user_id == $this->_issue->createdby) {
            $row->delfile->setVisible(true);
        } else {
            $row->delfile->setVisible(false);
        }
    }

    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        \App\Helper::deleteFile($file->file_id);
        $this->updateMessages();
        $this->goAnkor('afiles');
    }

    public function onStatus($sender) {

        $olduser = $this->_issue->user_id;
        $oldstatus = $this->_issue->status;
        $oldpriority = $this->_issue->priority;

        $this->_issue->status = $sender->ststatus->getValue();
        $this->_issue->user_id = $sender->stuser->getValue();
        $this->_issue->priority = $sender->stpr->getValue();
        $this->_issue->lastupdate = time();
        $this->_issue->save();

        if ($oldstatus != $this->_issue->status) {
            $this->_issue->addStatusLog(H::l("isstchanged") . " <b>" . $sender->ststatus->getValueName() . "</b>");
        }

        if ($oldpriority != $this->_issue->priority) {
            $this->_issue->addStatusLog(H::l("isprchanged") . " <b>" . $sender->stpr->getValueName() . "</b>");
        }

        $this->updateStList();
        $this->listpan->list->Reload(false);

        if ($olduser != $this->_issue->user_id) {

            $this->_issue->addStatusLog(H::l("isuserchanged") . " <b>" . $sender->stuser->getValueName() . "</b>");

            $n = new \App\Entity\Notify();
            $n->user_id = $this->_issue->user_id;
            $n->message = "  #{$this->_issue->issue_id} {$this->_issue->issue_name} ";
            $n->message .= H::l("isassigned") . "#{$this->_issue->issue_id}  <br>  <a href=\"/issue/{$this->_issue->issue_id}/{$this->_issue->project_id}\">" . H::l("msgopen") . "</a> ";
            $n->sender_id = System::getUser()->user_id;
            $n->save();
        }
    }

    public function stlistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('stlisttime', \App\Helper::fd($item->createdon)));
        $row->add(new Label('stlistuser', $item->username));
        $row->add(new Label('stlistdesc', $item->description, true));
    }

    public function updateStList() {
        $this->_stlist = $this->_issue->getLogList();
        $this->listpan->msgpan->stlist->Reload();
    }

}

class IssueDS implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $status = $this->page->filter->searchstatus->getValue();
        // $number = trim($this->page->filter->searchnumber->getText());
        $emp = $this->page->filter->searchemp->getValue();
        //  $number = $this->page->filter->searchnumber->getValue();
        $project = $this->page->filter->searchproject->getValue();

        $conn = \ZDB\DB::getConnect();

        $where = " 1=1 ";
        if ($status == 0) {
            $where .= " and status <> " . Issue::STATUS_CLOSED;
        }
        if ($status < 100 && $status > 0) {
            $where .= " and status = " . $status;
        }
        if ($project > 0) {
            $where .= " and project_id = " . $project;
        }
        if ($emp > 0) {
            $where .= " and user_id = " . $emp;
        }

        if (strlen($number) > 0) {

            $s = Issue::qstr('%' . $number . '%');
            $where = "  (details like {$s} or issue_name like {$s} or issue_id={$number})  ";
        }

        return $where;
    }

    public function getItemCount() {
        return Issue::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $sort = "priority, lastupdate desc";

        return Issue::find($this->getWhere(), $sort, $count, $start);
    }

    public function getItem($id) {

    }

}
