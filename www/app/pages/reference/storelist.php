<?php

namespace App\Pages\Reference;

use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Entity\Store;
use App\Helper as H;

class StoreList extends \App\Pages\Base
{

    public $_store = null;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('StoreList'))
            return;

        $storepanel = $this->add(new Panel('storetable'));
        $storepanel->add(new DataView('storelist', new \ZCL\DB\EntityDataSource('\App\Entity\Store'), $this, 'storelistOnRow'));
        $storepanel->add(new ClickLink('storeadd'))->onClick($this, 'storeaddOnClick');
        $this->add(new Form('storeform'))->setVisible(false);
        $this->storeform->add(new TextInput('storeeditname'));
        $this->storeform->add(new TextArea('storeeditdesc'));
        $this->storeform->add(new SubmitButton('storesave'))->onClick($this, 'storesaveOnClick');
        $this->storeform->add(new Button('storecancel'))->onClick($this, 'storecancelOnClick');
        $this->storetable->storelist->Reload();
    }

    public function storelistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('storename', $item->storename));
        $row->add(new Label('storedesc', $item->description));
        $row->add(new ClickLink('storeedit'))->onClick($this, 'storeeditOnClick');
        $row->add(new ClickLink('storedelete'))->onClick($this, 'storedeleteOnClick');
    }

    public function storeeditOnClick($sender) {
        $this->_store = $sender->owner->getDataItem();
        $this->storetable->setVisible(false);
        $this->storeform->setVisible(true);
        $this->storeform->storeeditname->setText($this->_store->storename);
        $this->storeform->storeeditdesc->setText($this->_store->description);
    }

    public function storedeleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('StoreList'))
            return;

        if (false == Store::delete($sender->owner->getDataItem()->store_id)) {
            $this->setError("Нельзя удалить цей  склад");
            return;
        }

        $this->storetable->storelist->Reload();
    }

    public function storeaddOnClick($sender) {
        $this->storetable->setVisible(false);
        $this->storeform->setVisible(true);
        $this->storeform->storeeditname->setText('');
        $this->storeform->storeeditdesc->setText('');
        $this->_store = new Store();
    }

    public function storesaveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('StoreList'))
            return;

        $this->_store->storename = $this->storeform->storeeditname->getText();
        $this->_store->description = $this->storeform->storeeditdesc->getText();
        if ($this->_store->storename == '') {
            $this->setError("Введите наименование");
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
