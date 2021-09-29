<?php

namespace App\Pages\Reference;

use App\Entity\Store;
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
use App\Helper as H;

class StoreList extends \App\Pages\Base
{

    public  $_store = null;
    private $_blist;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('StoreList')) {
            return;
        }
        $this->_blist = \App\Entity\Branch::getList(\App\System::getUser()->user_id);

        $storepanel = $this->add(new Panel('storetable'));
        $storepanel->add(new DataView('storelist', new \ZCL\DB\EntityDataSource('\App\Entity\Store'), $this, 'storelistOnRow'));
        $storepanel->add(new ClickLink('storeadd'))->onClick($this, 'storeaddOnClick');
        $this->add(new Form('storeform'))->setVisible(false);
        $this->storeform->add(new TextInput('storeeditname'));
        $this->storeform->add(new TextArea('storeeditdesc'));
        $this->storeform->add(new DropDownChoice('editbranch', $this->_blist, 0));

        $this->storeform->add(new SubmitButton('storesave'))->onClick($this, 'storesaveOnClick');
        $this->storeform->add(new Button('storecancel'))->onClick($this, 'storecancelOnClick');
        $this->storetable->storelist->Reload();
    }

    public function storelistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();

        $row->add(new Label('storename', $item->storename));
        $row->add(new Label('branch', $this->_blist[$item->branch_id]));
        $row->add(new Label('storedesc', $item->description));


        $conn = $conn = \ZDB\DB::getConnect();

        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "  store_id in ({$cstr})  and  ";
        }
        $sql = " select coalesce(sum(partion*qty),0) as cnt  from  store_stock_view  where {$cstr} store_id={$item->store_id} and  qty >0                   ";


        $row->add(new Label('qty', H::fqty($conn->GetOne($sql))));
        $row->add(new ClickLink('storeedit'))->onClick($this, 'storeeditOnClick');
        $row->add(new ClickLink('storedelete'))->onClick($this, 'storedeleteOnClick');
    }

    public function storeeditOnClick($sender) {
        $this->_store = $sender->owner->getDataItem();
        $this->storetable->setVisible(false);
        $this->storeform->setVisible(true);
        $this->storeform->storeeditname->setText($this->_store->storename);
        $this->storeform->storeeditdesc->setText($this->_store->description);
        $this->storeform->editbranch->setValue($this->_store->branch_id);
    }

    public function storedeleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('StoreList')) {
            return;
        }


        $del = Store::delete($sender->owner->getDataItem()->store_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->storetable->storelist->Reload();
    }

    public function storeaddOnClick($sender) {
        $this->storetable->setVisible(false);
        $this->storeform->setVisible(true);
        $this->storeform->storeeditname->setText('');
        $this->storeform->storeeditdesc->setText('');
        $b = \App\System::getBranch();
        $this->storeform->editbranch->setValue($b > 0 ? $b : 0);

        $this->_store = new Store();
    }

    public function storesaveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('StoreList')) {
            return;
        }

        $this->_store->storename = $this->storeform->storeeditname->getText();
        $this->_store->description = $this->storeform->storeeditdesc->getText();
        if ($this->_store->storename == '') {
            $this->setError("entername");
            return;
        }
        $this->_store->branch_id = $this->storeform->editbranch->getValue();
        if ($this->_tvars['usebranch'] == true && $this->_store->branch_id == 0) {
            $this->setError('selbranch');

            return;
        }

        $this->_store->Save();
        $this->storeform->setVisible(false);
        $this->storetable->setVisible(true);
        $this->storetable->storelist->Reload();
    }

    public function storecancelOnClick($sender) {
        $this->storeform->setVisible(false);
        $this->storetable->setVisible(true);
    }

}
