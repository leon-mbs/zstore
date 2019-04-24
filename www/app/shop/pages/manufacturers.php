<?php

namespace App\Shop\Pages;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\DataRow;
use \Zippy\Binding\PropertyBinding as Bind;
use \App\Shop\Entity\Manufacturer;
use \App\Shop\Helper;

//список   производителей
class Manufacturers extends \App\Pages\Base {

    public $msg;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowCat('Manufacturers'))
            return;

        $panellist = $this->add(new \Zippy\Html\Panel('list'));

        $panellist->add(new DataView("manufacturerslist", new \ZCL\DB\EntityDataSource('\App\Shop\Entity\Manufacturer'), $this, 'OnAddRow'));
        $panellist->add(new \Zippy\Html\Link\ClickLink('addnew', $this, 'OnAddClick'));


        $editform = $this->add(new \Zippy\Html\Form\Form('editform'));
        $editform->add(new \Zippy\Html\Form\TextInput('manufacturername'));
        $editform->add(new \Zippy\Html\Form\TextInput('url'));
        $editform->add(new \Zippy\Html\Form\TextInput('manufacturer_id'));
        $editform->add(new \Zippy\Html\Form\SubmitButton('save'))->onClick($this, 'OnSubmit');
        $editform->add(new \Zippy\Html\Form\Button('cancel'))->onClick($this, 'cancelOnClick');

        $editform->setVisible(false);
        $this->list->manufacturerslist->Reload();
    }

    public function OnDeleteClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        if (false == $item->checkDelete()) {
            $this->setError("Этот бренд уже  используется");
            return;
        }
        Manufacturer::delete($item->manufacturer_id);
        $this->list->manufacturerslist->Reload();
    }

    public function OnEditClick($sender) {

        $this->editform->SetVisible(true);
        $this->list->SetVisible(false);
        $item = $sender->getOwner()->getDataItem();
        $this->editform->manufacturername->setText($item->manufacturername);
        $this->editform->url->setText($item->url);
        $this->editform->manufacturer_id->setText($item->manufacturer_id);
    }

    public function OnSubmit($sender) {
        $name = $this->editform->manufacturername->getText();
        $url = $this->editform->url->getText();
        $id = $this->editform->manufacturer_id->getText();

        if ($id > 0) {
            $manufacturer = Manufacturer::load($id);
        } else {
            $manufacturer = new Manufacturer();
        }
        $manufacturer->manufacturername = $this->editform->manufacturername->getText();
        ;
        $manufacturer->url = $this->editform->url->getText();
        $manufacturer->save();
        $this->list->manufacturerslist->Reload();
        $this->editform->SetVisible(false);
        $this->list->SetVisible(true);
    }

    public function OnAddClick($sender) {
        $this->editform->SetVisible(true);
        $this->list->SetVisible(false);
        $this->editform->manufacturername->setText('');
        $this->editform->url->setText('');
        $this->editform->manufacturer_id->setText(0);
    }

    public function OnAddRow(DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->add(new \Zippy\Html\Label("manufacturername", $item->manufacturername));
        $datarow->add(new \Zippy\Html\Link\BookmarkableLink("url", $item->url))->setValue($item->url);
        $datarow->add(new \Zippy\Html\Link\ClickLink("edit", $this, "OnEditClick"));
        $datarow->add(new \Zippy\Html\Link\ClickLink("delete", $this, "OnDeleteClick"));
    }

    public function cancelOnClick($sender) {
        $this->list->setVisible(true);
        $this->editform->setVisible(false);
    }

}
