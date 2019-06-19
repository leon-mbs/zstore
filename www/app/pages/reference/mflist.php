<?php

namespace App\Pages\Reference;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \App\Entity\MoneyFund;

/**
 * Справочник  денежных счетов
 */
class MFList extends \App\Pages\Base {

    private $_mf;
    private $_balance;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('MFList'))
            return;

        $this->add(new Panel('mftable'))->setVisible(true);
        $this->mftable->add(new DataView('mflist', new \ZCL\DB\EntityDataSource('\App\Entity\MoneyFund'), $this, 'mflistOnRow'))->Reload();
        $this->mftable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('mfdetail'))->setVisible(false);
        $this->mfdetail->add(new TextInput('editmf_name'));
        $this->mfdetail->add(new TextArea('editmf_description'));
        $this->mfdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->mfdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->_balance = MoneyFund::Balance();
    }

    public function mflistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('mf_name', $item->mf_name));
        $row->add(new Label('description', $item->description));
        $row->add(new Label('amount', $this->_balance[$item->mf_id]));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('MFList'))
            return;


        $mf_id = $sender->owner->getDataItem()->mf_id;

        $del = MoneyFund::delete($mf_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->mftable->mflist->Reload();
    }

    public function editOnClick($sender) {
        $this->_mf = $sender->owner->getDataItem();
        $this->mftable->setVisible(false);
        $this->mfdetail->setVisible(true);
        $this->mfdetail->editmf_name->setText($this->_mf->mf_name);
        $this->mfdetail->editmf_description->setText($this->_mf->mf_description);
    }

    public function addOnClick($sender) {
        $this->mftable->setVisible(false);
        $this->mfdetail->setVisible(true);
        // Очищаем  форму
        $this->mfdetail->clean();

        $this->_mf = new MoneyFund();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('MFList'))
            return;

        $this->_mf->mf_name = $this->mfdetail->editmf_name->getText();
        $this->_mf->description = $this->mfdetail->editmf_description->getText();
        if ($this->_mf->mf_name == '') {
            $this->setError("Введите наименование");
            return;
        }

        $this->_mf->Save();
        $this->mfdetail->setVisible(false);
        $this->mftable->setVisible(true);
        $this->mftable->mflist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->mftable->setVisible(true);
        $this->mfdetail->setVisible(false);
    }

}
