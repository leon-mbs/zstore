<?php

namespace App\Pages\Doc;

use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Service;
use App\Application as App;
use App\System;

/**
 * Страница  ввода  заказа на  услуги
 */
class ServiceOrder extends \App\Pages\Base {

    public $_servicelist = array();
    private $_doc;
    private $_rowid = 0;
    private $_basedocid = 0;
    private $_discount;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer', false);



        $this->docform->add(new TextInput('notes'));

        $this->docform->add(new Label('discount'))->setVisible(false);


        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('inprocdoc'))->onClick($this, 'savedocOnClick');


        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('editservice'))->onText($this, 'OnAutoServive');
        $this->editdetail->editservice->onChange($this, 'OnChangeServive', true);

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextArea('editdesc'));


        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');


        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');



        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid);
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->headerdata['notes']);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            foreach ($this->_doc->detaildata as $item) {
                $item = new Service($item);
                $this->_servicelist[$item->service_id] = $item;
            }
        } else {
            $this->_doc = Document::create('ServiceOrder');
        }


        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->calcTotal();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function detailOnRow($row) {
        $service = $row->getDataItem();

        $row->add(new Label('item', $service->service_name));
        $row->add(new Label('desc', $service->desc));

        $row->add(new Label('quantity', $service->quantity));
        $row->add(new Label('price', $service->price));

        $row->add(new Label('amount', $service->quantity * $service->price));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $service = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editdesc->setText(($service->desc));
        $this->editdetail->editquantity->setText(($service->quantity));
        $this->editdetail->editprice->setText($service->price);

        $this->editdetail->editservice->setKey($service->service_id);
        $this->editdetail->editservice->setText($service->service_name);
        $this->_rowid = $service->service_id;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $service = $sender->owner->getDataItem();


        $this->_servicelist = array_diff_key($this->_servicelist, array($service->service_id => $this->_servicelist[$service->service_id]));
        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
        $this->editdetail->editdesc->setText('');
        $this->editdetail->editquantity->setText(1);
        $this->editdetail->editprice->setText(0);
    }

    public function saverowOnClick($sender) {
        $id = $this->editdetail->editservice->getKey();
        if ($id == 0) {
            $this->setError("Не выбрана  услуга");
            return;
        }
        $service = Service::load($id);
        $service->quantity = $this->editdetail->editquantity->getText();
        $service->price = $this->editdetail->editprice->getText();
        $service->desc = $this->editdetail->editdesc->getText();


        $this->_servicelist[$service->service_id] = $service;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        //очищаем  форму
        $this->editdetail->editservice->setKey(0);
        $this->editdetail->editdesc->setText('');
        $this->editdetail->editservice->setText('');
        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("0");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();


        if ($this->checkForm() == false) {
            return;
        }

        $this->calcTotal();

        $old = $this->_doc->cast();

        $this->_doc->headerdata = array(
            'total' => $this->docform->total->getText()
        );
        $this->_doc->detaildata = array();
        foreach ($this->_servicelist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }

        $isEdited = $this->_doc->document_id > 0;
        $this->_doc->amount = $this->docform->total->getText();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();

            if ($sender->id != 'savedoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                if ($sender->id == 'execdoc') {
                    $this->_doc->updateStatus(Document::STATE_EXECUTED);
                    $this->_doc->updateStatus(Document::STATE_CLOSED);
                }

                if ($sender->id == 'inprocdoc') {
                    $this->_doc->updateStatus(Document::STATE_INPROCESS);
                }
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();
            App::RedirectBack();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_servicelist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        $this->docform->total->setText(round($total));
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введите номер документа');
        }
        if (count($this->_servicelist) == 0) {
            $this->setError("Не введена  ни одна позиция");
        }

        return !$this->isError();



        $this->docform->detail->Reload();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "customer_name like " . $text);
    }

    public function OnChangeCustomer($sender) {
        $this->_discount = 0;
        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            $this->_discount = $customer->discount;
        }
        $this->calcTotal();
        if ($this->_discount > 0) {
            $this->docform->discount->setVisible(true);
            $this->docform->discount->setText('Скидка ' . $this->_discount . '%');
        } else {
            $this->docform->discount->setVisible(false);
        }
    }

    public function OnAutoServive($sender) {

        $text = Service::qstr('%' . $sender->getText() . '%');
        return Service::findArray("service_name", "    service_name like {$text}");
    }

    public function OnChangeServive($sender) {
        $id = $sender->getKey();

        $item = Service::load($id);
        $price = $item->price;

        $price = $price - $price / 100 * $this->_discount;


        $this->editdetail->editprice->setText($price);


        $this->updateAjax(array('editprice'));
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено имя");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editcustname->getText();
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

}
