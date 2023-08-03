<?php

namespace App\Pages\Reference;

use App\Entity\ProdArea;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

//Производственные  участки
class ProdAreaList extends \App\Pages\Base
{
    private $_pa;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ProdAreaList')) {
            return;
        }

        $this->add(new Panel('patable'))->setVisible(true);
        $this->patable->add(new DataView('palist', new \ZCL\DB\EntityDataSource('\App\Entity\ProdArea', '', 'pa_name'), $this, 'palistOnRow'))->Reload();
        $this->patable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('padetail'))->setVisible(false);
        $this->padetail->add(new TextInput('editpa_name'));
        $this->padetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->padetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function palistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('pa_name', $item->pa_name));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('ProdAreaList')) {
            return;
        }
        $pa = $sender->owner->getDataItem();

        $del = ProdArea::delete($pa->pa_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->patable->palist->Reload();
    }

    public function editOnClick($sender) {
        $this->_pa = $sender->owner->getDataItem();
        $this->patable->setVisible(false);
        $this->padetail->setVisible(true);
        $this->padetail->editpa_name->setText($this->_pa->pa_name);
    }

    public function addOnClick($sender) {
        $this->patable->setVisible(false);
        $this->padetail->setVisible(true);
        // Очищаем  форму
        $this->padetail->clean();

        $this->_pa = new ProdArea();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('ProdAreaList')) {
            return;
        }


        $this->_pa->pa_name = $this->padetail->editpa_name->getText();
        if ($this->_pa->pa_name == '') {
            $this->setError("Не введено назву");
            return;
        }

        $this->_pa->save();
        $this->padetail->setVisible(false);
        $this->patable->setVisible(true);
        $this->patable->palist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->patable->setVisible(true);
        $this->padetail->setVisible(false);
    }

}
