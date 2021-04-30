<?php

namespace App\Pages;

use App\Application as App;
use App\Entity\Branch;
use App\System;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

//Филиалы
class BranchList extends \App\Pages\Base
{

    private $_branch;

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg(H::l('onlyadminpage'));
            \App\Application::RedirectError();
            return false;
        }


        $this->add(new Panel('branchtable'))->setVisible(true);
        $this->branchtable->add(new DataView('branchlist', new \ZCL\DB\EntityDataSource('\App\Entity\Branch', '', 'disabled asc,branch_name asc'), $this, 'branchlistOnRow'))->Reload();
        $this->branchtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('branchdetail'))->setVisible(false);
        $this->branchdetail->add(new TextInput('editbranchname'));

        $this->branchdetail->add(new TextInput('editaddress'));
        $this->branchdetail->add(new TextInput('editphone'));
        $this->branchdetail->add(new TextArea('editcomment'));
        $this->branchdetail->add(new CheckBox('editdisabled'));
        $this->branchdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->branchdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function branchlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('branch_name', $item->branch_name));

        $row->add(new Label('address', $item->address));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
    }

    public function deleteOnClick($sender) {

        $branch = $sender->owner->getDataItem();

        $del = Branch::delete($branch->branch_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->branchtable->branchlist->Reload();
    }

    public function editOnClick($sender) {
        $this->_branch = $sender->owner->getDataItem();
        $this->branchtable->setVisible(false);
        $this->branchdetail->setVisible(true);
        $this->branchdetail->editbranchname->setText($this->_branch->branch_name);

        $this->branchdetail->editphone->setText($this->_branch->phone);

        $this->branchdetail->editaddress->setText($this->_branch->address);

        $this->branchdetail->editcomment->setText($this->_branch->comment);
        $this->branchdetail->editdisabled->setChecked($this->_branch->disabled);
    }

    public function addOnClick($sender) {
        $this->branchtable->setVisible(false);
        $this->branchdetail->setVisible(true);
        // Очищаем  форму
        $this->branchdetail->clean();

        $this->_branch = new Branch();
    }

    public function saveOnClick($sender) {


        $this->_branch->branch_name = $this->branchdetail->editbranchname->getText();
        if ($this->_branch->branch_name == '') {
            $this->setError("entername");
            return;
        }

        $this->_branch->address = $this->branchdetail->editaddress->getText();
        $this->_branch->phone = $this->branchdetail->editphone->getText();

        $this->_branch->comment = $this->branchdetail->editcomment->getText();
        $this->_branch->disabled = $this->branchdetail->editdisabled->isChecked() ? 1 : 0;

        $this->_branch->Save();
        $this->branchdetail->setVisible(false);
        $this->branchtable->setVisible(true);
        $this->branchtable->branchlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->branchtable->setVisible(true);
        $this->branchdetail->setVisible(false);
    }

}
