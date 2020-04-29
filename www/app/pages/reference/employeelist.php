<?php

namespace App\Pages\Reference;

use App\Entity\Employee;
use App\Helper;
use ZCL\DB\EntityDataSource as EDS;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class EmployeeList extends \App\Pages\Base
{

    private $_employee;
    private $_blist;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('EmployeeList')) {
            return;
        }
        $this->_blist = \App\Entity\Branch::getList(\App\System::getUser()->user_id);

        $this->add(new Panel('employeetable'))->setVisible(true);
        $this->employeetable->add(new DataView('employeelist', new EDS('\App\Entity\Employee', '', 'disabled, emp_name'), $this, 'employeelistOnRow'))->Reload();
        $this->employeetable->employeelist->setPageSize(Helper::getPG());
        $this->employeetable->add(new \Zippy\Html\DataList\Paginator('pag', $this->employeetable->employeelist));

        $this->employeetable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('employeedetail'))->setVisible(false);

        $this->employeedetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->employeedetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->employeedetail->add(new TextInput('editlogin'));
        $this->employeedetail->add(new TextInput('editemp_name'));
        $this->employeedetail->add(new DropDownChoice('editbranch', $this->_blist, 0));

        $this->employeedetail->add(new TextInput('editphone'));
        $this->employeedetail->add(new TextInput('editemail'));
        $this->employeedetail->add(new TextArea('editcomment'));
        $this->employeedetail->add(new CheckBox('editdisabled'));
    }

    /*
      public function onType($sender){
      $t = $sender->getValue();
      $this->employeedetail->editzmon->setVisible($t==1) ;
      $this->employeedetail->editzhour->setVisible($t==2) ;
      }
     */

    public function employeelistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('emp_name', $item->emp_name));
        $row->add(new Label('login', $item->login));
        $row->add(new Label('branch', $this->_blist[$item->branch_id]));
        //  $row->add(new Label('balance', $item->balance));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('EmployeeList')) {
            return;
        }

        Employee::delete($sender->owner->getDataItem()->employee_id);
        $this->employeetable->employeelist->Reload();
    }

    public function editOnClick($sender) {
        $this->_employee = $sender->owner->getDataItem();
        $this->employeetable->setVisible(false);
        $this->employeedetail->setVisible(true);
        $this->employeedetail->editlogin->setText($this->_employee->login);
        $this->employeedetail->editemp_name->setText($this->_employee->emp_name);
        $this->employeedetail->editcomment->setText($this->_employee->comment);
        $this->employeedetail->editemail->setText($this->_employee->email);
        $this->employeedetail->editphone->setText($this->_employee->phone);
        $this->employeedetail->editbranch->setValue($this->_employee->branch_id);
        $this->employeedetail->editdisabled->setChecked($this->_employee->disabled);
    }

    public function addOnClick($sender) {
        $this->employeetable->setVisible(false);
        $this->employeedetail->setVisible(true);
        // Очищаем  форму
        $this->employeedetail->clean();
        $b = \App\Session::getSession()->branch_id;
        $this->employeedetail->editbranch->setValue($b > 0 ? $b : 0);


        $this->_employee = new Employee();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('EmployeeList')) {
            return;
        }

        $login = trim($this->employeedetail->editlogin->getText());

        if (strlen($login) > 0) {
            if ($login == "admin") {
                $this->setError('invalidlogin');
                return;
            }
            $_emp = Employee::getFirst("login = '{$login}'");
            if ($_emp != null && $_emp->employee_id != $this->_employee->employee_id) {
                $this->setError('assignedlogin', $_emp->emp_name());
                return;
            }
            $user = \App\Entity\User::getByLogin($login);
            if ($user == null) {
                $this->setError('invalidlogin');
                return;
            }
        }
        $this->_employee->login = $login;
        $this->_employee->emp_name = trim($this->employeedetail->editemp_name->getText());

        $this->_employee->email = $this->employeedetail->editemail->getText();
        $this->_employee->phone = $this->employeedetail->editphone->getText();
        $this->_employee->comment = $this->employeedetail->editcomment->getText();

        $this->_employee->branch_id = $this->employeedetail->editbranch->getValue();

        $this->_employee->disabled = $this->employeedetail->editdisabled->isChecked() ? 1 : 0;

        $this->_employee->Save();

        $this->employeedetail->setVisible(false);
        $this->employeetable->setVisible(true);
        $this->employeetable->employeelist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->employeetable->setVisible(true);
        $this->employeedetail->setVisible(false);
    }

}
