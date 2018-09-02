<?php

namespace App\Pages\Reference;

use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Entity\Service;

class ServiceList extends \App\Pages\Base
{

    private $_service;

    public function __construct() {
        parent::__construct();

        $this->add(new Panel('servicetable'))->setVisible(true);
        $this->servicetable->add(new DataView('servicelist', new \ZCL\DB\EntityDataSource('\App\Entity\Service'), $this, 'servicelistOnRow'))->Reload();
        $this->servicetable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->servicetable->servicelist->setPageSize(25);
        $this->servicetable->add(new \Zippy\Html\DataList\Paginator('pag', $this->servicetable->servicelist));

        $this->add(new Form('servicedetail'))->setVisible(false);
        $this->servicedetail->add(new TextInput('editservice_name'));
        $this->servicedetail->add(new TextInput('editprice'));
        $this->servicedetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->servicedetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function servicelistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('service_name', $item->service_name));
        $row->add(new Label('price', $item->price));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        $service_id = $sender->owner->getDataItem()->service_id;
        // $cnt=  \App\Entity\Item::findCnt("cat_id=".$cat_id);
        if ($cnt > 0) {
            // $this->setError('Нельзя удалить категорию с товарами');
            //  return;
        }
        Service::delete($service_id);
        $this->servicetable->servicelist->Reload();
    }

    public function editOnClick($sender) {
        $this->_service = $sender->owner->getDataItem();
        $this->servicetable->setVisible(false);
        $this->servicedetail->setVisible(true);
        $this->servicedetail->editservice_name->setText($this->_service->service_name);
        $this->servicedetail->editprice->setText($this->_service->price);
    }

    public function addOnClick($sender) {
        $this->servicetable->setVisible(false);
        $this->servicedetail->setVisible(true);
        // Очищаем  форму
        $this->servicedetail->clean();

        $this->_service = new Service();
    }

    public function saveOnClick($sender) {
        $this->_service->service_name = $this->servicedetail->editservice_name->getText();
        $this->_service->price = $this->servicedetail->editprice->getText();
        if ($this->_service->service_name == '') {
            $this->setError("Введите наименование");
            return;
        }

        $this->_service->Save();
        $this->servicedetail->setVisible(false);
        $this->servicetable->setVisible(true);
        $this->servicetable->servicelist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->servicetable->setVisible(true);
        $this->servicedetail->setVisible(false);
    }

}
