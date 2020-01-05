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
use \App\Entity\Firm;

//Филиалы
class BranchList extends \App\Pages\Base {

    private $_firm;

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('К странице имеет  доступ только администратор ');
            App::RedirectHome();
            return false;
        }


        $this->add(new Panel('firmtable'))->setVisible(true);
        $this->firmtable->add(new DataView('firmlist', new \ZCL\DB\EntityDataSource('\App\Entity\Firm','','disabled asc,firm_name asc'), $this, 'firmlistOnRow'))->Reload();
        $this->firmtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('firmdetail'))->setVisible(false);
        $this->firmdetail->add(new TextInput('editfirm_name'));
       
        $this->firmdetail->add(new TextInput('editaddress'));
        $this->firmdetail->add(new TextArea('editcomment'));
        $this->firmdetail->add(new CheckBox('editdisabled'));
        $this->firmdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->firmdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->firmdetail->add(new DropDownChoice('editdefstore', \App\Entity\Store::getList()));
        $this->firmdetail->add(new DropDownChoice('editdefmf', \App\Entity\MoneyFund::getList()));
        $this->firmdetail->add(new DropDownChoice('editcompany', \App\Entity\Company::getList()));
    }

    public function firmlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('firm_name', $item->firm_name));
        $row->add(new Label('company', $item->getCompany()->company_name));
   
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
        
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('FirmList'))
            return;
        $firm = $sender->owner->getDataItem();

        $del = Firm::delete($firm->firm_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->firmtable->firmlist->Reload();
    }

    public function editOnClick($sender) {
        $this->_firm = $sender->owner->getDataItem();
        $this->firmtable->setVisible(false);
        $this->firmdetail->setVisible(true);
        $this->firmdetail->editfirm_name->setText($this->_firm->firm_name);
     
        $this->firmdetail->editaddress->setText($this->_firm->address);
        $this->firmdetail->editdefstore->setValue($this->_firm->defstore);
        $this->firmdetail->editcompany->setValue($this->_firm->company_id);
        
        $this->firmdetail->editdefmf->setOptionList(\App\Entity\MoneyFund::getList($this->_firm->firm_id));
        $this->firmdetail->editdefmf->setValue($this->_firm->defmf);
        $this->firmdetail->editcomment->setText($this->_firm->comment);
        $this->firmdetail->editdisabled->setChecked($this->_firm->disabled);
    }

    public function addOnClick($sender) {
        $this->firmtable->setVisible(false);
        $this->firmdetail->setVisible(true);
        // Очищаем  форму
        $this->firmdetail->clean();

        $this->_firm = new Firm();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('FirmList'))
            return;


        $this->_firm->firm_name = $this->firmdetail->editfirm_name->getText();
        if ($this->_firm->firm_name == '') {
            $this->setError("Введите наименование");
            return;
        }
       
        $this->_firm->address = $this->firmdetail->editaddress->getText();
        $this->_firm->defstore = $this->firmdetail->editdefstore->getValue();
        $this->_firm->defmf = $this->firmdetail->editdefmf->getValue();
        $this->_firm->company_id = $this->firmdetail->editcompany->getValue();
        $this->_firm->comment = $this->firmdetail->editcomment->getText();
        $this->_firm->disabled = $this->firmdetail->editdisabled->isChecked() ?1:0;

        $this->_firm->Save();
        $this->firmdetail->setVisible(false);
        $this->firmtable->setVisible(true);
        $this->firmtable->firmlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->firmtable->setVisible(true);
        $this->firmdetail->setVisible(false);
    }

}
