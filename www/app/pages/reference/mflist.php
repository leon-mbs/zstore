<?php

namespace App\Pages\Reference;

use App\Entity\MoneyFund;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * Справочник  денежных счетов
 */
class MFList extends \App\Pages\Base
{

    private $_mf;
    private $_balance;
    private $_blist;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('MFList')) {
            return;
        }
        $this->_balance = MoneyFund::Balance();
        $this->_blist = \App\Entity\Branch::getList(\App\System::getUser()->user_id);

        $this->add(new Panel('mftable'))->setVisible(true);
        $this->mftable->add(new DataView('mflist', new \ZCL\DB\EntityDataSource('\App\Entity\MoneyFund', '', 'mf_name'), $this, 'mflistOnRow'))->Reload();
        $this->mftable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('mfdetail'))->setVisible(false);
        $this->mfdetail->add(new TextInput('editmf_name'));
        $this->mfdetail->add(new DropDownChoice('editbranch', $this->_blist, 0));

        $this->mfdetail->add(new CheckBox('editbeznal'))->onChange($this, 'onBeznal');
        $this->mfdetail->add(new TextInput('editbtran'));
        $this->mfdetail->add(new TextInput('editbtranin'));
        $this->mfdetail->add(new TextArea('editmf_description'));
        $this->mfdetail->add(new TextInput('editbank'));
        $this->mfdetail->add(new TextInput('editbankacc'));

        $this->mfdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->mfdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function mflistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();

        $row->add(new Label('mf_name', $item->mf_name));
        $row->add(new Label('branch', $this->_blist[$item->branch_id]));

        $row->add(new Label('description', $item->description));
        $row->add(new Label('amount', \App\Helper::fa($this->_balance[$item->mf_id])));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('MFList')) {
            return;
        }


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
        $this->mfdetail->editbtran->setText($this->_mf->btran);
        $this->mfdetail->editbtranin->setText($this->_mf->btranin);
        $this->mfdetail->editmf_name->setText($this->_mf->mf_name);
        $this->mfdetail->editbranch->setValue($this->_mf->branch_id);
        $this->mfdetail->editbeznal->setChecked($this->_mf->beznal);
        $this->onBeznal($this->mfdetail->editbeznal);
        $this->mfdetail->editmf_description->setText($this->_mf->description);
        $this->mfdetail->editbank->setText($this->_mf->bank);
        $this->mfdetail->editbankacc->setText($this->_mf->bankacc);
    }

    public function addOnClick($sender) {
        $this->mftable->setVisible(false);
        $this->mfdetail->setVisible(true);
        // Очищаем  форму
        $this->mfdetail->clean();
        $b = \App\System::getBranch();
        $this->mfdetail->editbranch->setValue($b > 0 ? $b : 0);

        $this->_mf = new MoneyFund();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('MFList')) {
            return;
        }

        $this->_mf->mf_name = $this->mfdetail->editmf_name->getText();
        if (strlen($this->_mf->mf_name) == 0) {
            $this->setError('entername');
            return;
        }
        $this->_mf->btran = $this->mfdetail->editbtran->getText();
        $this->_mf->btranin = $this->mfdetail->editbtranin->getText();
        $this->_mf->bank = $this->mfdetail->editbank->getText();
        $this->_mf->bankacc = $this->mfdetail->editbankacc->getText();

        $this->_mf->description = $this->mfdetail->editmf_description->getText();
        if ($this->_mf->mf_name == '') {
            $this->setError("entername");
            return;
        }
        $this->_mf->beznal = $this->mfdetail->editbeznal->isChecked() ? 1 : 0;
        $this->_mf->branch_id = $this->mfdetail->editbranch->getValue();
 

        $this->_mf->save();
        $this->mfdetail->setVisible(false);
        $this->mftable->setVisible(true);
        $this->mftable->mflist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->mftable->setVisible(true);
        $this->mfdetail->setVisible(false);
    }

    public function onBeznal($sender) {
        $b = $sender->isChecked();
        $this->mfdetail->editbank->setVisible($b);
        $this->mfdetail->editbankacc->setVisible($b);
        $this->mfdetail->editbtran->setVisible($b);
    }

}
