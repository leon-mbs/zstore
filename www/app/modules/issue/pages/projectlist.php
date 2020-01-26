<?php

namespace App\Modules\Issue\Pages;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\Paginator;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\BookmarkableLink;
use \Zippy\Html\Panel;
use \App\Entity\Customer;
use \App\Modules\Issue\Entity\Project;
use \App\Modules\Issue\Entity\Issue;
use \Zippy\Html\DataList\ArrayDataSource;
use \App\System;
use \App\Application as App;
use \App\Helper as H;

class ProjectList extends \App\Pages\Base {

    public $_project = null;
    public $_msglist = array();
    public $_fileslist = array();

    public function __construct($id = 0) {
        parent::__construct();
        $this->_user = System::getUser();

        $allow = (strpos($this->_user->modules, 'issue') !== false || $this->_user->userlogin == 'admin');
        if (!$allow) {
            System::setErrorMsg('Нет права  доступа  к   модулю ');
            App::RedirectHome();
            return;
        }

        $projectpanel = $this->add(new Panel('projectpanel'));

        $projectpanel->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $clist = Customer::findArray('customer_name', 'customer_id in (select customer_id from issue_projectlist )', 'customer_name');
        $projectpanel->filter->add(new DropDownChoice('searchcust', $clist, 0));
        $projectpanel->filter->add(new TextInput('searchnumber'));
        $projectpanel->filter->add(new CheckBox('searcharch'));

        $list = $projectpanel->add(new DataView('projectlist', new ProjectDS($this), $this, 'listOnRow'));
        $list->setPageSize(25);
        $this->projectpanel->add(new Paginator('pag', $list));
        $this->projectpanel->projectlist->Reload();

        $projectpanel->add(new ClickLink('padd'))->onClick($this, 'addOnClick');
        $this->add(new Form('projectform'))->setVisible(false);
        $this->projectform->add(new TextInput('editname'));
        $this->projectform->add(new AutocompleteTextInput('editcust'))->onText($this, 'OnAutoCustomer');
        $this->projectform->add(new TextArea('editdesc'));
        $this->projectform->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->projectform->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel("msgpan"))->setVisible(false);
        $this->msgpan->add(new ClickLink('back', $this, 'cancelOnClick'));
        $this->msgpan->add(new ClickLink('toilist', $this, 'toilistOnClick'));

        $this->msgpan->add(new Label('mtitle'));
        $this->msgpan->add(new Label('mdesc'));
        $this->msgpan->add(new Form('addmsgform'))->onSubmit($this, 'onAddMsg');
        $this->msgpan->addmsgform->add(new TextArea('msgdata'));
        $this->msgpan->add(new DataView('msglist', new ArrayDataSource($this, '_msglist'), $this, 'msgListOnRow'));

        $this->msgpan->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $this->msgpan->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $this->msgpan->add(new DataView('filelist', new ArrayDataSource($this, '_fileslist'), $this, 'fileListOnRow'));
    }

    public function listOnRow($row) {
        $pr = $row->getDataItem();

        $row->add(new Label('project_name', $pr->project_name));
        $row->add(new Label('customer_name', $pr->customer_name));
        $row->add(new Label('inew', $pr->inew))->setVisible($pr->inew > 0);
        $row->add(new Label('iproc', $pr->iproc))->setVisible($pr->iproc > 0);
        $row->add(new Label('iclose', $pr->iclose))->setVisible($pr->iclose > 0);


        $row->add(new ClickLink('preview'))->onClick($this, 'previewOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('archive'))->onClick($this, 'archiveOnClick');
        $row->archive->setAttribute('title', 'Архивировать');
        if ($pr->iproc > 0 || $pr->inew > 0) {
            $row->archive->setVisible(false);
        }
        if ($pr->archived == 1) {
            $row->archive->setVisible(true);
            $row->preview->setVisible(false);
            $row->edit->setVisible(false);
            $row->delete->setVisible(false);
            $row->archive->setAttribute('title', 'Разархивировать');
        }
    }

    public function editOnClick($sender) {
        $this->_project = $sender->owner->getDataItem();
        $this->projectpanel->setVisible(false);
        $this->projectform->setVisible(true);
        $this->projectform->editname->setText($this->_project->project_name);
        $this->projectform->editdesc->setText($this->_project->desc);
    }

    public function archiveOnClick($sender) {
        $project = $sender->owner->getDataItem();
        $project->archived = $project->archived == 1 ? 0 : 1;
        $project->save();
        $this->projectpanel->projectlist->Reload();
    }

    public function deleteOnClick($sender) {

        $del = Project::delete($sender->owner->getDataItem()->project_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->projectpanel->projectlist->Reload();
        $this->resetURL();
    }

    public function listOnClick($sender) {

        $id->owner->getDataItem()->project_id;
    }

    public function filterOnSubmit($sender) {
        $this->projectpanel->projectlist->Reload();
    }

    public function addOnClick($sender) {
        $this->projectpanel->setVisible(false);
        $this->projectform->setVisible(true);
        $this->projectform->editname->setText('');
        $this->projectform->editdesc->setText('');
        $this->_project = new Project();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('StoreList'))
            return;

        $this->_project->project_name = $this->projectform->editname->getText();
        $this->_project->customer_id = $this->projectform->editcust->getKey();
        $this->_project->desc = $this->projectform->editdesc->getText();
        if ($this->_project->project_name == '') {
            $this->setError("Введите наименование");
            return;
        }

        $this->_project->Save();
        $this->projectform->setVisible(false);
        $this->projectpanel->setVisible(true);
        $this->projectpanel->projectlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->msgpan->setVisible(false);
        $this->projectform->setVisible(false);
        $this->projectpanel->setVisible(true);
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and customer_name like " . $text);
    }

    public function previewOnClick($sender) {

        $this->_project = $sender->getOwner()->getDataItem();
        $this->msgpan->mtitle->setText($this->_project->project_name);
        $this->msgpan->mdesc->setText($this->_project->desc, true);

        $this->projectpanel->setVisible(false);

        $this->msgpan->setVisible(true);
        $this->updateMessages();
    }

    public function toilistOnClick($sender) {
        
    }

    public function onAddMsg($sender) {
        $msg = new \App\Entity\Message();
        $msg->message = $this->msgpan->addmsgform->msgdata->getText();
        $msg->created = time();
        $msg->user_id = $this->_user->user_id;
        $msg->item_id = $this->_project->project_id;
        $msg->item_type = \App\Entity\Message::TYPE_PROJECT;
        if (strlen($msg->message) == 0)
            return;
        $msg->save();

        $this->msgpan->addmsgform->msgdata->setText('');
        $this->updateMessages();


        $this->goAnkor('msgankor');
    }

    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type =6 and item_id=' . $this->_project->project_id);
        $this->msgpan->msglist->Reload();
        $this->_fileslist = \App\Helper::getFileList($this->_project->project_id, 6);
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

        \App\Helper::addFile($file, $this->_project->project_id, '', 6);

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

}

class ProjectDS implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $number = trim($this->page->projectpanel->filter->searchnumber->getText());
        $cust = $this->page->projectpanel->filter->searchcust->getValue();
        $arch = $this->page->projectpanel->filter->searcharch->isChecked();

        $conn = \ZDB\DB::getConnect();


        if ($arch == false)
            $where = " archived <> 1 ";
        else
            $where = " archived = 1 ";

        if ($cust > 0)
            $where .= " and customer_id = " . $cust;


        if (strlen($number) > 0) {
            $s = Project::qstr('%' . $number . '%');

            $where .= " and (details like {$s} or project_name like {$s} or issue_id=" . Project::qstr(project_name) . ")  ";
        }

        return $where;
    }

    public function getItemCount() {
        return Project::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $sort = "project_id desc";

        return Project::find($this->getWhere(), $sort, $count, $start);
    }

    public function getItem($id) {
        
    }

}
