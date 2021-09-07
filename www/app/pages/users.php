<?php

namespace App\Pages;

use App\Application as App;
use App\Entity\User;
use App\Entity\UserRole;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class Users extends \App\Pages\Base
{

    public $user = null;

    public function __construct() {
        parent::__construct();

        if (System::getUser()->rolename != 'admins') {

            $this->setError('onlyadmisaccess');
            App::RedirectError();
            return false;
        }


        $this->add(new Panel("listpan"));
        $this->listpan->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->listpan->filter->add(new DropDownChoice('searchrole', \App\entity\UserRole::findArray('rolename', '', 'rolename'), 0));

        $this->listpan->add(new ClickLink('addnew', $this, "onAdd"));
        $this->listpan->add(new DataView("userrow", new UserDataSource($this), $this, 'OnUserRow'))->Reload();

        $this->add(new Panel("editpan"))->setVisible(false);
        $this->editpan->add(new Form('editform'));
        $this->editpan->editform->add(new TextInput('editlogin'));
        $this->editpan->editform->add(new TextInput('editpass'));
        $this->editpan->editform->add(new TextInput('editemail'));
        $this->editpan->editform->add(new DropDownChoice('editrole', UserRole::findArray('rolename', '', 'rolename')));

        $this->editpan->editform->add(new CheckBox('editdisabled'));
        $this->editpan->editform->add(new CheckBox('editonlymy'));
        $this->editpan->editform->add(new CheckBox('edithidemenu'));

        $this->editpan->editform->onSubmit($this, 'saveOnClick');
        $this->editpan->editform->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->editpan->editform->add(new DataView('brow', new \ZCL\DB\EntityDataSource("\\App\\Entity\\Branch", "disabled<>1", "branch_name"), $this, 'branchOnRow'));
    }

    public function onAdd($sender) {

        if (System::getUser()->rolename !== 'admins') {
            $this->setError('onlyadminsuser');

            return;
        }

        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);
        // Очищаем  форму
        $this->editpan->editform->clean();
        $this->editpan->editform->brow->Reload();

        $this->user = new User();
    }

    public function onEdit($sender) {

        if (System::getUser()->rolename !== 'admins') {
            $this->setError('onlyadminsuser');

            return;
        }

        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);

        $this->user = $sender->getOwner()->getDataItem();
        $this->editpan->editform->editemail->setText($this->user->email);
        $this->editpan->editform->editlogin->setText($this->user->userlogin);
        $this->editpan->editform->editrole->setValue($this->user->role_id);

        $this->editpan->editform->editonlymy->setChecked($this->user->onlymy);
        $this->editpan->editform->edithidemenu->setChecked($this->user->hidemenu);
        $this->editpan->editform->editdisabled->setChecked($this->user->disabled);

        $this->editpan->editform->brow->Reload();
    }

    public function saveOnClick($sender) {

        $emp = \App\Entity\Employee::getByLogin($this->user->userlogin);

        $this->user->email = $this->editpan->editform->editemail->getText();
        $this->user->userlogin = $this->editpan->editform->editlogin->getText();
        if ($emp != null && $this->user->userlogin != $emp->login) {
            $emp->login = $this->user->userlogin;
            $emp->save();
        }

        $user = User::getByLogin($this->user->userlogin);
        if ($user instanceof User) {
            if ($user->user_id != $this->user->user_id) {
                $this->setError('nouniquelogin');

                return;
            }
        }
        if ($this->user->email != "") {
            $user = User::getByEmail($this->user->email);
            if ($user instanceof User) {
                if ($user->user_id != $this->user->user_id) {

                    $this->setError('nouniqueemail');
                    return;
                }
            }
        }
        $this->user->role_id = $this->editpan->editform->editrole->getValue();
        $this->user->onlymy = $this->editpan->editform->editonlymy->isChecked() ? 1 : 0;
        $this->user->hidemenu = $this->editpan->editform->edithidemenu->isChecked() ? 1 : 0;
        $this->user->disabled = $this->editpan->editform->editdisabled->isChecked() ? 1 : 0;

        $pass = $this->editpan->editform->editpass->getText();
        if (strlen($pass) > 0) {
            $this->user->userpass = (\password_hash($pass, PASSWORD_DEFAULT));;
        }
        if ($this->user->user_id == 0 && strlen($pass) == 0) {

            $this->setError("enterpassword");
            return;
        }

        $barr = array();
        foreach ($this->editpan->editform->brow->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->editbr == true) {
                $barr[] = $item->branch_id;
            }
        }
        $this->user->aclbranch = implode(',', $barr);

        $this->user->save();
        $this->listpan->userrow->Reload();
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->editpan->editform->editpass->setText('');
    }

    public function cancelOnClick($sender) {
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
    }

    //удаление  юзера

    public function OnRemove($sender) {

        if (System::getUser()->rolename !== 'admins') {
            $this->setError('onlyadminsuser');

            return;
        }

        $user = $sender->getOwner()->getDataItem();
        $del = User::delete($user->user_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }

        $this->listpan->userrow->Reload();
    }

    public function OnUserRow($datarow) {
        $item = $datarow->getDataItem();
        $datarow->add(new \Zippy\Html\Label("userlogin", $item->userlogin));
        $datarow->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
        $datarow->add(new \Zippy\Html\Label("rolename", $item->rolename));
        $datarow->add(new \Zippy\Html\Label("empname", $item->employee_id > 0 ? $item->username : ''));

        $datarow->add(new \Zippy\Html\Label("created", \App\Helper::fd($item->createdon)));
        $datarow->add(new \Zippy\Html\Label("lastactive", \App\Helper::fdt($item->lastactive)));
        $datarow->add(new \Zippy\Html\Label("email", $item->email));
        $datarow->add(new \Zippy\Html\Link\ClickLink("edit", $this, "OnEdit"))->setVisible($item->userlogin != 'admin');
        $datarow->add(new \Zippy\Html\Link\ClickLink("remove", $this, "OnRemove"))->setVisible($item->userlogin != 'admin');
    }

    public function branchOnRow($row) {
        $item = $row->getDataItem();
        $arr = @explode(',', $this->user->aclbranch);
        if (is_array($arr)) {
            $item->editbr = in_array($item->branch_id, $arr);
        }

        $row->add(new Label('branch_name', $item->branch_name));
        $row->add(new CheckBox('editbr', new Bind($item, 'editbr')));
    }

    public function OnFilter($sender) {
        $this->listpan->userrow->Reload();
    }

}

class UserDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {

        $this->page = $page;
    }

    private function getWhere() {
        $sql = '';
        $role = $this->page->listpan->filter->searchrole->getValue();
        if ($role > 0) {
            $sql = 'role_id=' . $role;
        }

        return $sql;
    }

    public function getItemCount() {
        return User::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $orderbyfield = null, $desc = true) {
        return User::find($this->getWhere(), "userlogin", $count, $start);
    }

    public function getItem($id) {
        return User::load($id);
    }

}
