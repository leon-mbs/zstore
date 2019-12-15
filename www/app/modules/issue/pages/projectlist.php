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
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \App\Entity\Customer;
use \App\Modules\Issue\Entity\Project;
use \App\Modules\Issue\Entity\Issue;
use \App\System;
use \App\Application as App;
 
use \App\Helper as H;

class ProjectList extends \App\Pages\Base {

    public $_project = null;

    public function __construct() {
        parent::__construct();
        $this->_user = System::getUser();

        $allow = (strpos($this->_user->modules, 'issue') !== false || $this->_user->userlogin == 'admin');
        if (!$allow) {
            System::setErrorMsg('Нет права  доступа  к   модулю ');
            App::RedirectHome();
            return;
        }

        $projectpanel = $this->add(new Panel('projectpanel'));
        $projectpanel->add(new DataView('projectlist', new \ZCL\DB\EntityDataSource('\App\Modules\Issue\Entity\Project'), $this, 'listOnRow'));
        $projectpanel->add(new ClickLink('padd'))->onClick($this, 'addOnClick');
        $this->add(new Form('projectform'))->setVisible(false);
        $this->projectform->add(new TextInput('editname'));
        $this->projectform->add(new AutocompleteTextInput('editcust'))->onText($this, 'OnAutoCustomer');
        $this->projectform->add(new TextArea('editdesc'));
        $this->projectform->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->projectform->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->projectpanel->projectlist->Reload();
    }

    public function listOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('project_name', $item->projectname));
        $row->add(new Label('customer_name', $item->description));
        $row->add(new ClickLink('state_name'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('opencomment'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $this->_project = $sender->owner->getDataItem();
        $this->projectpanel->setVisible(false);
        $this->projectform->setVisible(true);
        $this->projectform->projecteditname->setText($this->_project->projectname);
        $this->projectform->projecteditdesc->setText($this->_project->description);
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('StoreList'))
            return;


        $del = Store::delete($sender->owner->getDataItem()->project_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
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
        $this->projectform->setVisible(false);
        $this->projectpanel->setVisible(true);
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and customer_name like " . $text);
    }

}
