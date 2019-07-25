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
use \App\Application as App;
use \App\System;
use \App\Modules\Issue\Helper;
use \App\Filter;
use \ZCL\BT\Tags;
use \App\Modules\Issue\Entity\Issue;
use \App\Entity\Customer;
use \App\Entity\User;

/**
 * Главная страница
 */
class IssueList extends \App\Pages\Base {

    public $_issue;

    public function __construct($id = 0) {
        parent::__construct();

        $user = System::getUser();

        $allow = (strpos($user->modules, 'issue') !== false || $user->userlogin == 'admin');
        if (!$allow) {
            System::setErrorMsg('Нет права  доступа  к   модулю ');
            App::RedirectHome();
            return;
        }

        $this->add(new Panel("listpan"));

        $this->listpan->add(new Form('filter'))->onSubmit($this, 'onFilter');
        $this->listpan->filter->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
        $this->listpan->filter->add(new TextInput('searchnumber', $filter->searchnumber));

        //пользователи ассоциированные с сотрудниками
        $this->listpan->filter->add(new DropDownChoice('searchassignedto', User::findArray('username', 'employee_id > 0', 'username'), $user->employee_id > 0 ? $user->user_id : 0 ));

        $stlist = Issue::getStatusList();
        $stlist[-1] = 'Открытые';
        $stlist[100] = 'Все';
        $this->listpan->filter->add(new DropDownChoice('searchstatus', $stlist, -1));


        $this->listpan->add(new Form('sort'))->onSubmit($this, 'reload');
        $this->listpan->sort->add(new DropDownChoice('sorttype', array(0 => 'Последние измененные', 1 => 'Дата создания', 2 => 'Приоритет'), 0));


        $list = $this->listpan->add(new DataView('list', new IssueDS($this), $this, 'listOnRow'));
        $list->setPageSize(25);
        $this->listpan->add(new Paginator('pag', $list));
        $this->listpan->add(new ClickLink('addnew', $this, 'onNew'));

        $this->add(new Panel("editpan"))->setVisible(false);
        $this->editpan->add(new Form('editform'))->onSubmit($this, 'onSaveIssue');
        $this->editpan->editform->add(new TextInput('edittitle'));
        $this->editpan->editform->add(new TextArea('editcontent'));
        $this->editpan->editform->add(new DropDownChoice('editpr', array(0 => 'Нормальный', 1 => 'Высокий', -1 => 'Низкий'), 0));
        $this->editpan->editform->add(new TextInput('editprice'));
        $this->editpan->editform->add(new TextInput('edithours'));

        $this->editpan->editform->add(new AutocompleteTextInput('editcust'))->onText($this, 'OnAutoCustomer');
        $this->editpan->editform->add(new ClickLink('editcancel', $this, 'onCancel'));



        $this->add(new Panel("msgpan"))->setVisible(false);
        $this->msgpan->add(new ClickLink('back', $this, 'onCancel'));
        $this->msgpan->add(new Label('mtitle'));
        $this->msgpan->add(new Label('mdesc'));


        $this->listpan->list->Reload();
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
        $row->add(new Label('issue_id', $issue->issue_id));
        $row->add(new Label('title', $issue->issue_name));
        $row->add(new Label('issignedto', $issue->username));
        $row->add(new Label('prup'))->setVisible($issue->priority == 1);
        $row->add(new Label('prdown'))->setVisible($issue->priority == -1);
        $st = Issue::getStatusList();
        $row->add(new Label('status', $st[$issue->status]));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('opencomment'))->onClick($this, 'commentlOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {

        $this->_issue = $sender->getOwner()->getDataItem();

        $user = System::getUser();
        if ($user->username != 'admin' && $user->user_id != $this->_issue->createdby) {
            $this->setError('Редактировать  может  только  автор или  администатор');
            return;
        }

        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);

        $this->editpan->editform->edittitle->setText($this->_issue->issue_name);
        $this->editpan->editform->editcontent->setText($this->_issue->desc);
        $this->editpan->editform->editcust->setKey($this->_issue->customer_id);
        $this->editpan->editform->editcust->setText($this->_issue->customer_name);
        $this->editpan->editform->editpr->setValue($this->_issue->priority);
        $this->editpan->editform->edithours->setText($this->_issue->hours);
        $this->editpan->editform->editprice->setText($this->_issue->price);
    }

    public function onSaveIssue($sender) {


        $this->_issue->issue_name = $sender->edittitle->getText();
        $this->_issue->desc = $sender->editcontent->getText();
        $this->_issue->customer_id = $sender->editcust->getKey();
        $this->_issue->priority = $sender->editpr->getValue();
        $this->_issue->hours = $sender->edithours->getText();
        $this->_issue->price = $sender->editprice->getText();
        if ($this->_issue->issue_id == 0) {
            $user = System::getUser();
            $this->_issue->createdby = $user->user_id;
            $this->_issue->createdbyname = $user->username;
        }
        $this->_issue->save();

        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->listpan->list->Reload();
    }

    public function commentlOnClick($sender) {

        $this->_issue = $sender->getOwner()->getDataItem();
        $this->listpan->setVisible(false);
        $this->msgpan->setVisible(true);

        $this->msgpan->mtitle->setText('#' . $this->_issue->issue_id . ' ' . $this->_issue->issue_name);
        $this->msgpan->mdesc->setText($this->_issue->desc, true);
    }

    public function deleteOnClick($sender) {

        $issue = $sender->getOwner()->getDataItem();
        $msg = Issue::delete($issue->issue_id);
        if (strlen(msg) > 0) {
            $this->setError($msg);
            return;
        }
        $this->listpan->list->Reload();
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and customer_name like " . $text);
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
        $cust = $this->page->listpan->filter->searchcust->getKey();

        $conn = \ZDB\DB::getConnect();

        $where = "1=1 ";
        if ($status == 100)
            $where .= " and status <> " . Issue::STATUS_CLOSED;
        if ($status < 100 && $status >= 0)
            $where .= " and status = " . $status;
        if ($cust > 0)
            $where .= " and customer_id = " . $cust;
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
