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
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Service;
use App\Entity\Store;
use App\Entity\Stock;
use App\Entity\Prodarea;
use App\Entity\Item;
use App\Entity\Employee;
use App\Entity\Equipment;
use App\Application as App;

/**
 * Страница  ввода  наряда  на  работу
 */
class Task extends \App\Pages\Base
{

    public $_servicelist = array();
    public $_itemlist = array();
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
        $this->docform->add(new TextInput('hours', "0"));
        
        $this->docform->add(new Label('discount'))->setVisible(false);
        $this->docform->add(new DropDownChoice('store', Store::getList(), \App\Helper::getDefStore()));
        $this->docform->add(new DropDownChoice('parea', Prodarea::findArray("pa_name", "" ), 0));
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()));

        $this->docform->add(new SubmitLink('addservice'))->onClick($this, 'addserviceOnClick');
        $this->docform->add(new SubmitLink('additem'))->onClick($this, 'additemOnClick');
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->add(new SubmitLink('addeq'))->onClick($this, 'addeqOnClick');
        $this->docform->add(new SubmitLink('addemp'))->onClick($this, 'addempOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Label('total'));
        $this->docform->add(new Label('totaldisc'));

        //service
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('editservice'))->onText($this, 'OnAutoServive');
        $this->editdetail->editservice->onChange($this, 'OnChangeServive', true);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        //item
        $this->add(new Form('editdetail2'))->setVisible(false);
        $this->editdetail2->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->editdetail2->edititem->onChange($this, 'OnChangeItem', true);
        $this->editdetail2->add(new TextInput('editquantity2'))->setText("1");
        $this->editdetail2->add(new TextInput('editprice2'));
        $this->editdetail2->add(new Label('qty'));
        $this->editdetail2->add(new CheckBox('custpay'));
        $this->editdetail2->add(new Button('cancelrow2'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail2->add(new SubmitButton('saverow2'))->onClick($this, 'saverow2OnClick');

        //employer
        $this->add(new Form('editdetail3'))->setVisible(false);
        $this->editdetail3->add(new DropDownChoice('editemp', Employee::findArray("emp_name", "", "emp_name")));
        $this->editdetail3->add(new Button('cancelrow3'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail3->add(new SubmitButton('saverow3'))->onClick($this, 'saverow3OnClick');
    
    
        //equipment
        $this->add(new Form('editdetail4'))->setVisible(false);
        $this->editdetail4->add(new DropDownChoice('editeq', Equipment::findArray("eq_name", "", "eq_name")));
        $this->editdetail4->add(new Button('cancelrow4'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail4->add(new SubmitButton('saverow4'))->onClick($this, 'saverow4OnClick');


        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');



        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid);
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->hours->setText($this->_doc->headerdata['hours']);

            $this->docform->start_date->setDate($this->_doc->headerdata['start_date']);
              $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->store->setValue($this->_doc->headerdata['store']);

            $this->docform->document_date->setDate($this->_doc->headerdata['end_date']);
            $this->docform->parea->setValue($this->_doc->headerdata['parea']);
                        $this->docform->customer->setKey($this->_doc->customer_id);
                        $this->docform->customer->setText($this->_doc->customer_name);
            $this->OnChangeCustomer($this->docform->customer);

            foreach ($this->_doc->detaildata as $item) {
                if ($item["service_id"] > 0) {
                    $service = new Service($item);
                    $this->_servicelist[$service->service_id] = $service;
                }
                if ($item["item_id"] > 0) {
                    $stock = new Stock($item);
                    $this->_itemlist[$stock->stock_id] = $stock;
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
            $this->_doc->document_number = $this->_doc->nextNumber();
            $this->docform->document_number->setText($this->_doc->document_number);
            ;
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->docform->add(new DataView('detail2', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detail2OnRow'))->Reload();
        $this->docform->add(new DataView('detail3', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_emplist')), $this, 'detail3OnRow'))->Reload();
        $this->docform->add(new DataView('detail4', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_eqlist')), $this, 'detail4OnRow'))->Reload();
        $this->calcTotal();

        if(false ==\App\ACL::checkShowDoc($this->_doc))return;       
    
    }

    public function detailOnRow($row) {
        $service = $row->getDataItem();

        $row->add(new Label('service', $service->service_name));

        $row->add(new Label('quantity', $service->quantity));
        $row->add(new Label('price', $service->price));

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
        $this->editdetail->editprice->setText(0);
    }

    public function editOnClick($sender) {
        $service = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText(($service->quantity));
        $this->editdetail->editprice->setText($service->price);

        $this->editdetail->editservice->setKey($service->service_id);
        $this->editdetail->editservice->setText($service->service_name);
    }

    public function deleteOnClick($sender) {
        if(false ==\App\ACL::checkEditDoc($this->_doc))return;       
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


        $this->_servicelist[$service->service_id] = $service;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
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

        $row->add(new Label('quantity2', $item->quantity));
        $row->add(new Label('price2', $item->price));

        $row->add(new Label('amount2', $item->quantity * $item->price));
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
        $this->editdetail2->custpay->setChecked($item->custpay);

        $this->editdetail2->edititem->setKey($item->item_id);
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
        $stock->custpay = $this->editdetail2->custpay->isChecked();


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

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->editdetail2->setVisible(false);
        $this->editdetail3->setVisible(false);
        $this->editdetail4->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function delete2OnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->_itemlist = array_diff_key($this->_itemlist, array($item->stock_id => $this->_itemlist[$item->stock_id]));
        $this->docform->detail2->Reload();
        $this->calcTotal();
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
         if(false ==\App\ACL::checkEditDoc($this->_doc))return;       
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
            'parea' => $this->docform->parea->getValue(),
            'pricetype' => $this->docform->pricetype->getValue(),
             'pricetypename' => $this->docform->pricetype->getValueName(),
           'store' => $this->docform->store->getValue(),
             'hours' => $this->docform->hours->getText(),
            
            'start_date' => $this->docform->start_date->getDate(),
            'end_date' => $this->docform->document_date->getDate(),
             'totaldisc' => $this->docform->totaldisc->getText(),
            'total' => $this->docform->total->getText()
        );
        $this->_doc->detaildata = array();
        foreach ($this->_servicelist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }
        foreach ($this->_itemlist as $item) {
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
     
            $logger->error($ee->getMessage() . " Документ ". $this->_doc->meta_desc);
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
            if ($item->custpay == 1)
                $total = $total + $item->amount;
        }

        $totaldisc = round($total / 100 * $this->_discount);
        $total = $total - $totaldisc;
        $this->docform->total->setText($total);
        $this->docform->totaldisc->setText($totaldisc);
        $this->docform->totaldisc->setVisible($totaldisc > 0);
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

    public function OnAutoItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = Item::qstr('%' . $this->editdetail2->edititem->getText() . '%');
        return Stock::findArrayEx("store_id={$store_id}   and (itemname like {$text} or item_code like {$text}) ");
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $stock = Stock::load($id);
        $this->editdetail2->qty->setText(Stock::getQuantity($id, $this->docform->document_date->getDate()));

        $item = Item::load($stock->item_id);
        $price = $item->getPrice($this->docform->pricetype->getValue(),$stock->partion > 0 ? $stock->partion : 0);
        //$price = $price - $price / 100 * $this->_discount;


        $this->editdetail2->editprice2->setText($price);

        $this->updateAjax(array('qty', 'editprice2'));
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
