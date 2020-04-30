<?php

namespace App\Pages;

use App\Entity\Pos;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

//POS терминалы
class PosList extends \App\Pages\Base
{

    private $_pos;
    private $_blist;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('PosList')) {
            return;
        }
        $this->_blist = \App\Entity\Branch::getList(\App\System::getUser()->user_id);

        $this->add(new Panel('postable'))->setVisible(true);
        $this->postable->add(new DataView('poslist', new \ZCL\DB\EntityDataSource('\App\Entity\Pos', '', ' pos_name asc'), $this, 'poslistOnRow'))->Reload();
        $this->postable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->add(new Form('posdetail'))->setVisible(false);
        $this->posdetail->add(new DropDownChoice('editbranch', $this->_blist, 0));

        $this->posdetail->add(new TextInput('editpos_name'));
        $this->posdetail->add(new DropDownChoice('editstore', \App\Entity\Store::getList(), H::getDefStore()));
        $this->posdetail->add(new DropDownChoice('editmf', \App\Entity\MoneyFund::getList()));
        $this->posdetail->add(new DropDownChoice('editpricetype', \App\Entity\Item::getPriceTypeList()));


        $this->posdetail->add(new TextArea('editcomment'));

        $this->posdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->posdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function poslistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('pos_name', $item->pos_name));
        $row->add(new Label('branch_name', $this->_blist[$item->branch_id]));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('PosList')) {
            return;
        }
        $pos = $sender->owner->getDataItem();

        $del = Pos::delete($pos->pos_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->postable->poslist->Reload();
    }

    public function editOnClick($sender) {
        $this->_pos = $sender->owner->getDataItem();
        $this->postable->setVisible(false);
        $this->posdetail->setVisible(true);
        $this->posdetail->editpos_name->setText($this->_pos->pos_name);
        $this->posdetail->editbranch->setValue($this->_pos->branch_id);
        $this->posdetail->editstore->setValue($this->_pos->store);
        $this->posdetail->editmf->setValue($this->_pos->mf);

        $this->posdetail->editpricetype->setValue($this->_pos->pricetype);

        $this->posdetail->editcomment->setText($this->_pos->comment);
    }

    public function addOnClick($sender) {
        $this->postable->setVisible(false);
        $this->posdetail->setVisible(true);
        // Очищаем  форму
        $this->posdetail->clean();
        $b = \App\Session::getSession()->branch_id;
        $this->posdetail->editbranch->setValue($b > 0 ? $b : 0);
        $this->_pos = new Pos();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('PosList')) {
            return;
        }


        $this->_pos->pos_name = $this->posdetail->editpos_name->getText();

        $this->_pos->branch_id = $this->posdetail->editbranch->getValue();

        $this->_pos->store = $this->posdetail->editstore->getValue();
        $this->_pos->mf = $this->posdetail->editmf->getValue();

        $this->_pos->pricetype = $this->posdetail->editpricetype->getValue();

        if ($this->_pos->pos_name == '') {
            $this->setError("entername");
            return;
        }
        if ($this->_tvars['usebranch'] == true && $this->_pos->branch_id == 0) {

            $this->setError("selbranch");
            return;
        }
        if ($this->_pos->mf == 0) {

            $this->setError("selmf");
            return;
        }

        if ($this->_pos->store == 0) {

            $this->setError("noselstore");
            return;
        }

        if ($this->_pos->pricetype == "0") {
            $this->setError("noselpricetype");
            return;
        }

        if ($this->_tvars['usebranch'] == true && $this->_pos->branch_id > 0) {
            $mf = \App\Entity\MoneyFund::load($this->_pos->mf);
            $store = \App\Entity\Store::load($this->_pos->store);
            if ($this->_pos->branch_id != $mf->branch_id) {

                $this->setError("thesamebranch");
                return;
            }
            if ($this->_pos->branch_id != $store->branch_id) {
                $this->setError("thesamebranch");
                return;
            }
        }

        $this->_pos->comment = $this->posdetail->editcomment->getText();

        $this->_pos->Save();
        $this->posdetail->setVisible(false);
        $this->postable->setVisible(true);
        $this->postable->poslist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->postable->setVisible(true);
        $this->posdetail->setVisible(false);
    }

}
