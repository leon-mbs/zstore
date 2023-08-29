<?php

namespace App\Pages\Reference;

use App\Entity\Department;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

//отделы
class DepartmentList extends \App\Pages\Base
{
    private $_dep;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('DepartmentList')) {
            return;
        }

        $this->add(new Panel('patable'))->setVisible(true);
        $this->patable->add(new DataView('palist', new \ZCL\DB\EntityDataSource('\App\Entity\Department', '', 'department_name'), $this, 'palistOnRow'))->Reload();
        $this->patable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('padetail'))->setVisible(false);
        $this->padetail->add(new TextInput('editdepartment_name'));
        $this->padetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->padetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function palistOnRow($row) {
        $item = $row->getDataItem();
        $conn = \ZDB\DB::getConnect() ;
        $item->cnt=  intval( $conn->Execute('select count(*) from employees where  department_id = '. $item->department_id) ); ;
        $row->add(new Label('department_name', $item->department_name));
        $row->add(new Label("cnt", $item->cnt));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        if ($item->cnt > 0) {
            $row->delete->setVisible(false);
        }
        if ($item->cnt == 0) {
            $row->cnt->setVisible(false);
        }
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('DepartmentList')) {
            return;
        }
        $dep = $sender->owner->getDataItem();

        $del = Department::delete($dep->department_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->patable->palist->Reload();
    }

    public function editOnClick($sender) {
        $this->_dep = $sender->owner->getDataItem();
        $this->patable->setVisible(false);
        $this->padetail->setVisible(true);
        $this->padetail->editdepartment_name->setText($this->_dep->department_name);
    }

    public function addOnClick($sender) {
        $this->patable->setVisible(false);
        $this->padetail->setVisible(true);
        // Очищаем  форму
        $this->padetail->clean();

        $this->_dep = new Department();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('DepartmentList')) {
            return;
        }


        $this->_dep->department_name = $this->padetail->editdepartment_name->getText();
        if ($this->_dep->department_name == '') {
            $this->setError("Не введено назву");
            return;
        }

        $this->_dep->save();
        $this->padetail->setVisible(false);
        $this->patable->setVisible(true);
        $this->patable->palist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->patable->setVisible(true);
        $this->padetail->setVisible(false);
    }

}
