<?php

namespace App\Pages;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \App\Entity\Company;
use \App\System;

//владельцы
class CompanyList extends \App\Pages\Base {

    private $_company;

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('К странице имеет  доступ только администратор ');
            App::RedirectHome();
            return false;
        }

        $this->add(new Panel('firmtable'))->setVisible(true);
        $this->firmtable->add(new DataView('firmlist', new \ZCL\DB\EntityDataSource('\App\Entity\Company'), $this, 'firmlistOnRow'))->Reload();
        $this->firmtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('firmdetail'))->setVisible(false);
        $this->firmdetail->add(new TextInput('editcompany_name'));
        $this->firmdetail->add(new TextInput('editinn'));
        $this->firmdetail->add(new TextInput('editmfo'));
        $this->firmdetail->add(new TextInput('editaccount'));

        $this->firmdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->firmdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function firmlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('company_name', $item->company_name));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CompanyList'))
            return;
        $firm = $sender->owner->getDataItem();

        $del = Company::delete($firm->firm_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->firmtable->firmlist->Reload();
    }

    public function editOnClick($sender) {
        $this->_company = $sender->owner->getDataItem();
        $this->firmtable->setVisible(false);
        $this->firmdetail->setVisible(true);
        $this->firmdetail->editcompany_name->setText($this->_company->company_name);
        $this->firmdetail->editinn->setText($this->_company->inn);
        $this->firmdetail->editmfo->setText($this->_company->mfo);
        $this->firmdetail->editaccount->setText($this->_company->bankaccount);
    }

    public function addOnClick($sender) {
        $this->firmtable->setVisible(false);
        $this->firmdetail->setVisible(true);
        // Очищаем  форму
        $this->firmdetail->clean();

        $this->_company = new Company();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CompanyList'))
            return;


        $this->_company->company_name = $this->firmdetail->editcompany_name->getText();
        if ($this->_company->company_name == '') {
            $this->setError("Введите наименование");
            return;
        }
        $this->_company->inn = $this->firmdetail->editinn->getText();
        $this->_company->mfo = $this->firmdetail->editmfo->getText();
        $this->_company->bankaccount = $this->firmdetail->editaccount->getText();


        $this->_company->Save();
        $this->firmdetail->setVisible(false);
        $this->firmtable->setVisible(true);
        $this->firmtable->firmlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->firmtable->setVisible(true);
        $this->firmdetail->setVisible(false);
    }

}
