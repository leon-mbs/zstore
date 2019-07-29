<?php

namespace App\Pages\Doc;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\Customer;
use \App\Entity\Doc\Document;
use \App\Entity\Service;
use \App\Entity\Store;
use \App\Entity\Stock;
use \App\Entity\Prodarea;
use \App\Entity\Item;
use \App\Entity\Employee;
use \App\Entity\Equipment;
use \App\Application as App;
use \App\Helper as H;
use \App\Entity\MoneyFund;

/**
 * Страница  ввода  наряда  на  работу
 */
class Task extends \App\Pages\Base {

    public $_servicelist = array();
    public $_itemlist = array();
    public $_itemlist5 = array();
    public $_emplist = array();
    public $_eqlist = array();
    private $_doc;
    private $_discount;

    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new \ZCL\BT\DateTimePicker('start_date'))->setDate(time());
        $this->docform->add(new \ZCL\BT\DateTimePicker('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer', false);


        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('taskhours', "0"));

        $this->docform->add(new Label('discount'))->setVisible(false);
        $this->docform->add(new DropDownChoice('store', Store::getList(), \App\Helper::getDefStore()));
        $this->docform->add(new DropDownChoice('parea', Prodarea::findArray("pa_name", ""), 0));
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()));
        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()))->onChange($this, "onMF");
        $this->docform->add(new TextInput('paynotes'));

        $this->docform->add(new SubmitLink('addservice'))->onClick($this, 'addserviceOnClick');
        $this->docform->add(new SubmitLink('additem'))->onClick($this, 'additemOnClick');
        $this->docform->add(new SubmitLink('additem5'))->onClick($this, 'additemOnClick5');
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->add(new SubmitLink('addeq'))->onClick($this, 'addeqOnClick');
        $this->docform->add(new SubmitLink('addemp'))->onClick($this, 'addempOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Label('total'));


        //service
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('editservice'))->onText($this, 'OnAutoServive');
        $this->editdetail->editservice->onChange($this, 'OnChangeServive', true);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('edithours'));
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        //комплектующие
        $this->add(new Form('editdetail2'))->setVisible(false);
        $this->editdetail2->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->editdetail2->edititem->onChange($this, 'OnChangeItem', true);
        $this->editdetail2->add(new TextInput('editquantity2'))->setText("1");
        $this->editdetail2->add(new TextInput('editprice2'));
        $this->editdetail2->add(new Label('qty'));

        $this->editdetail2->add(new Button('cancelrow2'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail2->add(new SubmitButton('saverow2'))->onClick($this, 'saverow2OnClick');

        //Материалы
        $this->add(new Form('editdetail5'))->setVisible(false);
        $this->editdetail5->add(new AutocompleteTextInput('edititem5'))->onText($this, 'OnAutoItem5');
        $this->editdetail5->edititem5->onChange($this, 'OnChangeItem5', true);
        $this->editdetail5->add(new TextInput('editquantity5'))->setText("1");
        $this->editdetail5->add(new TextInput('editprice5'));
        $this->editdetail5->add(new Label('qty5'));

        $this->editdetail5->add(new Button('cancelrow5'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail5->add(new SubmitButton('saverow5'))->onClick($this, 'saverow5OnClick');

        //employer
        $this->add(new Form('editdetail3'))->setVisible(false);
        $this->editdetail3->add(new DropDownChoice('editemp', Employee::findArray("emp_name", "disabled<>1", "emp_name")));
        $this->editdetail3->add(new Button('cancelrow3'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail3->add(new SubmitButton('saverow3'))->onClick($this, 'saverow3OnClick');


        //equipment
        $this->add(new Form('editdetail4'))->setVisible(false);
        $this->editdetail4->add(new DropDownChoice('editeq', Equipment::findArray("eq_name", "disabled<>1", "eq_name")));
        $this->editdetail4->add(new Button('cancelrow4'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail4->add(new SubmitButton('saverow4'))->onClick($this, 'saverow4OnClick');


        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');



        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->taskhours->setText($this->_doc->headerdata['taskhours']);

            $this->docform->start_date->setDate($this->_doc->headerdata['start_date']);
            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->paynotes->setText($this->_doc->headerdata['paynotes']);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->parea->setValue($this->_doc->headerdata['parea']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);
            $this->OnChangeCustomer($this->docform->customer);

            foreach ($this->_doc->detaildata as $item) {
                if ($item["service_id"] > 0) {
                    $service = new Service($item);
                    $this->_servicelist[$service->service_id] = $service;
                }
                if ($item["item_id"] > 0 && strlen($item["item5_id"]) == 0) {
                    $stock = new Stock($item);
                    $this->_itemlist[$stock->stock_id] = $stock;
                }
                if ($item["item5_id"] > 0) {
                    $stock = new Stock($item);
                    $this->_itemlist5[$stock->stock_id] = $stock;
                }
                if ($item["employee_id"] > 0) {
                    $emp = new Employee($item);
                    $this->_emplist[$emp->employee_id] = $emp;
                }
                if ($item["eq_id"] > 0) {
                    $eq = new Equipment($item);
                    $this->_eqlist[$eq->eq_id] = $eq;
                }
            }
        } else {
            $this->_doc = Document::create('Task');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->docform->add(new DataView('detail2', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detail2OnRow'))->Reload();
        $this->docform->add(new DataView('detail3', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_emplist')), $this, 'detail3OnRow'))->Reload();
        $this->docform->add(new DataView('detail4', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_eqlist')), $this, 'detail4OnRow'))->Reload();
        $this->docform->add(new DataView('detail5', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist5')), $this, 'detail5OnRow'))->Reload();
        $this->calcTotal();

        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;

        $this->onMF($this->docform->payment);
    }

    public function onMF($sender) {
        $mf = $sender->getValue();
        $this->docform->paynotes->setVisible($mf > 0);
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->editdetail2->setVisible(false);
        $this->editdetail3->setVisible(false);
        $this->editdetail4->setVisible(false);
        $this->editdetail5->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function detailOnRow($row) {
        $service = $row->getDataItem();

        $row->add(new Label('service', $service->service_name));

        $row->add(new Label('quantity', $service->quantity));
        $row->add(new Label('price', $service->price));
        $row->add(new Label('hours', $service->hours));

        $row->add(new Label('amount', $service->quantity * $service->price));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function addserviceOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editservice->setText('');
        $this->editdetail->editservice->setKey(0);
        $this->editdetail->editquantity->setText(1);
        $this->editdetail->editprice->setText('');
    }

    public function editOnClick($sender) {
        $service = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText(($service->quantity));
        $this->editdetail->editprice->setText($service->price);
        $this->editdetail->edithours->setText($service->hours);

        $this->editdetail->editservice->setKey($service->service_id);
        $this->editdetail->editservice->setText($service->service_name);
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $service = $sender->owner->getDataItem();


        $this->_servicelist = array_diff_key($this->_servicelist, array($service->service_id => $this->_servicelist[$service->service_id]));
        $this->docform->detail->Reload();
        $this->calcTotal();
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
        $service->hours = $this->editdetail->edithours->getText();


        $this->_servicelist[$service->service_id] = $service;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->calcTotal();
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->editservice->setKey(0);
        $this->editdetail->editservice->setText('');
        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("0");
    }

    public function detail2OnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('msr', $item->msr));

        $row->add(new Label('quantity2', H::fqty($item->quantity)));
        $row->add(new Label('price2', $item->price));

        $row->add(new Label('amount2', round($item->quantity * $item->price)));
        $row->add(new ClickLink('edit2'))->onClick($this, 'edit2OnClick');
        $row->add(new ClickLink('delete2'))->onClick($this, 'delete2OnClick');
    }

    public function additemOnClick($sender) {
        $this->editdetail2->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail2->edititem->setText('');
        $this->editdetail2->edititem->setKey(0);
        $this->editdetail2->editquantity2->setText(1);
        $this->editdetail2->editprice2->setText(0);
    }

    public function edit2OnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail2->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail2->editquantity2->setText(($item->quantity));
        $this->editdetail2->editprice2->setText($item->price);


        $this->editdetail2->edititem->setKey($item->stock_id);
        $this->editdetail2->edititem->setText($item->itemname);
    }

    public function saverow2OnClick($sender) {
        $id = $this->editdetail2->edititem->getKey();
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }
        $stock = Stock::load($id);
        $stock->quantity = $this->editdetail2->editquantity2->getText();
        $stock->price = $this->editdetail2->editprice2->getText();



        $this->_itemlist[$stock->stock_id] = $stock;
        $this->editdetail2->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail2->Reload();
        $this->calcTotal();
        //очищаем  форму
        $this->editdetail2->edititem->setKey(0);
        $this->editdetail2->edititem->setText('');
        $this->editdetail2->editquantity2->setText("1");

        $this->editdetail2->editprice2->setText("0");
    }

    public function delete2OnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->_itemlist = array_diff_key($this->_itemlist, array($item->stock_id => $this->_itemlist[$item->stock_id]));
        $this->docform->detail2->Reload();
        $this->calcTotal();
    }

    public function detail5OnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item_code5', $item->item_code));
        $row->add(new Label('item5', $item->itemname));
        $row->add(new Label('msr5', $item->msr));

        $row->add(new Label('quantity5', H::fqty($item->quantity)));
        $row->add(new Label('price5', $item->price));

        $row->add(new Label('amount5', round($item->amount)));
        $row->add(new ClickLink('edit5'))->onClick($this, 'editOnClick5');
        $row->add(new ClickLink('delete5'))->onClick($this, 'deleteOnClick5');
    }

    public function additemOnClick5($sender) {
        $this->editdetail5->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail5->edititem5->setText('');
        $this->editdetail5->edititem5->setKey(0);
        $this->editdetail5->editquantity5->setText(1);
        $this->editdetail5->editprice5->setText(0);
    }

    public function editOnClick5($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail5->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail5->editquantity5->setText(($item->quantity));
        $this->editdetail5->editprice5->setText($item->price);


        $this->editdetail5->edititem5->setKey($item->stock_id);
        $this->editdetail5->edititem5->setText($item->itemname);
    }

    public function saverow5OnClick($sender) {
        $id = $this->editdetail5->edititem5->getKey();
        if ($id == 0) {
            $this->setError("Не выбран материал");
            return;
        }
        $stock = Stock::load($id);
        $stock->quantity = $this->editdetail5->editquantity5->getText();
        $stock->price = $this->editdetail5->editprice5->getText();
        $stock->amount = $stock->quantity * $stock->price;

        $stock->item5_id = $stock->item_id;
        $this->_itemlist5[$stock->stock_id] = $stock;
        $this->editdetail5->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail5->Reload();

        //очищаем  форму
        $this->editdetail5->edititem5->setKey(0);
        $this->editdetail5->edititem5->setText('');
        $this->editdetail5->editquantity5->setText("1");

        $this->editdetail5->editprice5->setText("0");
    }

    public function deleteOnClick5($sender) {
        $item = $sender->owner->getDataItem();
        $this->_itemlist5 = array_diff_key($this->_itemlist5, array($item->stock_id => $this->_itemlist5[$item->stock_id]));
        $this->docform->detail5->Reload();
    }

    //employee
    public function addempOnClick($sender) {
        $this->editdetail3->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail3->editemp->setValue(0);
    }

    public function saverow3OnClick($sender) {
        $id = $this->editdetail3->editemp->getValue();
        if ($id == 0) {
            $this->setError("Не выбран исполнитель");
            return;
        }
        $emp = Employee::load($id);

        $this->_emplist[$emp->employee_id] = $emp;
        $this->editdetail3->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail3->Reload();
    }

    public function detail3OnRow($row) {
        $emp = $row->getDataItem();

        $row->add(new Label('empname', $emp->emp_name));
        $row->add(new ClickLink('delete3'))->onClick($this, 'delete3OnClick');
    }

    public function delete3OnClick($sender) {
        $emp = $sender->owner->getDataItem();
        $this->_emplist = array_diff_key($this->_emplist, array($emp->employee_id => $this->_emplist[$emp->employee_id]));
        $this->docform->detail3->Reload();
    }

    //equipment
    public function addeqOnClick($sender) {
        $this->editdetail4->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail4->editeq->setValue(0);
    }

    public function saverow4OnClick($sender) {
        $id = $this->editdetail4->editeq->getValue();
        if ($id == 0) {
            $this->setError("Не выбрано оборудование ");
            return;
        }
        $eq = Equipment::load($id);

        $this->_eqlist[$eq->eq_id] = $eq;
        $this->editdetail4->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail4->Reload();
    }

    public function detail4OnRow($row) {
        $eq = $row->getDataItem();

        $row->add(new Label('eq_name', $eq->eq_name));
        $row->add(new ClickLink('delete4'))->onClick($this, 'delete4OnClick');
    }

    public function delete4OnClick($sender) {
        $eq = $sender->owner->getDataItem();
        $this->_emplist = array_diff_key($this->_eqlist, array($eq->eq_id => $this->_eqlist[$eq->eq_id]));
        $this->docform->detail4->Reload();
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


        $this->_doc->headerdata['parea'] = $this->docform->parea->getValue();
        $this->_doc->headerdata['pareaname'] = $this->docform->parea->getValueName();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['pricetypename'] = $this->docform->pricetype->getValueName();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['taskhours'] = $this->docform->taskhours->getText();
        $this->_doc->headerdata['start_date'] = $this->docform->start_date->getDate();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['paynotes'] = $this->docform->paynotes->getText();


        $this->_doc->detaildata = array();
        foreach ($this->_servicelist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }
        foreach ($this->_itemlist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }
        foreach ($this->_itemlist5 as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }
        foreach ($this->_eqlist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }

        $total = $this->docform->total->getText();
        $cnt = count($this->_emplist);

        foreach ($this->_emplist as $item) {
            $item->pay = round($total / $cnt); //сумма поровну
            $this->_doc->detaildata[] = $item->getData();
        }

        $isEdited = $this->_doc->document_id > 0;
        $this->_doc->amount = $this->docform->total->getText();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();

            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }

                //  $this->_doc->updateStatus(Document::STATE_EXECUTED);
                $this->_doc->updateStatus(Document::STATE_INPROCESS);


                $this->_doc->save();
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();
            if ($isEdited)
                App::RedirectBack();
            else
                App::Redirect("\\App\\Pages\\Register\\TaskList");
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
        foreach ($this->_itemlist as $item) {

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
        if (strlen($this->_doc->document_date) == 0) {
            $this->setError('Введите дату документа');
        }
        if (count($this->_servicelist) == 0) {
            $this->setError("Не введена  ни одна работа");
        }

        return !$this->isError();



        $this->docform->detail->Reload();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and customer_name like " . $text);
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
        return Service::findArray("service_name", "  disabled<>1 and  service_name like {$text}");
    }

    public function OnChangeServive($sender) {
        $id = $sender->getKey();

        $item = Service::load($id);
        $price = $item->price;

        $price = $price - $price / 100 * $this->_discount;


        $this->editdetail->editprice->setText($price);
        $this->editdetail->edithours->setText($item->hours);
        $this->updateAjax(array('editprice','edithours'));
    }

    public function OnAutoItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($this->editdetail2->edititem->getText());
        return Stock::findArrayAC($store_id, $text);
    }

    public function OnAutoItem5($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($this->editdetail5->edititem->getText());
        return Stock::findArrayAC($store_id, $text);
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $stock = Stock::load($id);
        $this->editdetail2->qty->setText(Stock::getQuantity($id));

        $item = Item::load($stock->item_id);
        $price = $item->getPrice($this->docform->pricetype->getValue(), $stock->partion > 0 ? $stock->partion : 0);
        $price = $price - $price / 100 * $this->_discount;


        $this->editdetail2->editprice2->setText($price);

        $this->updateAjax(array('qty', 'editprice2'));
    }

    public function OnChangeItem5($sender) {
        $id = $sender->getKey();
        $stock = Stock::load($id);
        $this->editdetail5->qty5->setText(Stock::getQuantity($id));

        $item = Item::load($stock->item_id);
        $price = $item->getPrice($this->docform->pricetype->getValue(), $stock->partion > 0 ? $stock->partion : 0);
        //$price = $price - $price / 100 * $this->_discount;


        $this->editdetail5->editprice5->setText($price);

        $this->updateAjax(array('qty5', 'editprice5'));
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
