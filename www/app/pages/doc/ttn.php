<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\MoneyFund;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  торгово-транспортной  накладной
 */
class TTN extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;
    private $_orderid   = 0;
    private $_changedpos  = false;

     /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");

        $this->_tvars["colspan"] = $common['usesnumber'] == 1 ? 8 : 6;

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new Date('sent_date',time()));
        $this->docform->add(new Date('delivery_date',time()+24*3600));
        $this->docform->add(new CheckBox('nostore'));
        $this->docform->add(new CheckBox('payseller'));

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new DropDownChoice('salesource', H::getSaleSources(), H::getDefSaleSource()));

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->addcust->setVisible(       \App\ACL::checkEditRef('CustomerList',false));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');

        $this->docform->add(new DropDownChoice('firm', \App\Entity\Firm::getList(), H::getDefFirm()));

        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList(), H::getDefPriceType()));
        $this->docform->add(new DropDownChoice('emp', \App\Entity\Employee::findArray('emp_name', '', 'emp_name')));

        $this->docform->add(new DropDownChoice('delivery', Document::getDeliveryTypes($this->_tvars['np'] == 1), Document::DEL_SELF))->onChange($this, 'OnDelivery');

        $this->docform->add(new TextInput('order'));

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('ship_number'));
        $this->docform->add(new TextInput('ship_amount'));
        $this->docform->add(new TextArea('ship_address'));
        $this->docform->add(new TextInput('email'));
        $this->docform->add(new TextInput('phone'));

        $this->docform->add(new Label('notesfromorder'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('senddoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('sendnp'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));
        $this->docform->add(new Label('weight'))->setVisible(false);

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editserial'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));
        $this->editdetail->add(new Label('qtystock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editaddress'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->total->setText($this->_doc->amount);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->sent_date->setDate($this->_doc->headerdata['sent_date']);
            $this->docform->delivery_date->setDate($this->_doc->headerdata['delivery_date']);
            $this->docform->ship_number->setText($this->_doc->headerdata['ship_number']);
            $this->docform->ship_amount->setText($this->_doc->headerdata['ship_amount']);
            $this->docform->ship_address->setText($this->_doc->headerdata['ship_address']);
            $this->docform->emp->setValue($this->_doc->headerdata['emp_id']);
            $this->docform->delivery->setValue($this->_doc->headerdata['delivery']);
            $this->docform->email->setText($this->_doc->headerdata['email']);
            $this->docform->phone->setText($this->_doc->headerdata['phone']);
            $this->docform->nostore->setChecked($this->_doc->headerdata['nostore']);
            $this->docform->payseller->setChecked($this->_doc->headerdata['payseller']);

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->salesource->setValue($this->_doc->headerdata['salesource']);
            $this->docform->customer->setKey($this->_doc->customer_id);

            if ($this->_doc->customer_id) {
                $this->docform->customer->setText($this->_doc->customer_name);
            } else {
                $this->docform->customer->setText($this->_doc->headerdata['customer_name']);
            }

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->order->setText($this->_doc->headerdata['order']);
            $this->_orderid = $this->_doc->headerdata['order_id'];

            $this->docform->firm->setValue($this->_doc->firm_id);
            $this->OnChangeCustomer($this->docform->customer);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('TTN');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {


                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $this->docform->salesource->setValue($basedoc->headerdata['salesource']);
                        $this->docform->delivery->setValue($basedoc->headerdata['delivery']);
                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);

                        $this->_orderid = $basedocid;
                        $this->docform->order->setText($basedoc->document_number);
                        $this->docform->notesfromorder->setText($basedoc->notes);
                        $this->docform->ship_address->setText($basedoc->headerdata['ship_address']);
                        $this->docform->delivery->setValue($basedoc->headerdata['delivery']);

                        $this->_doc->headerdata['bayarea'] = $basedoc->headerdata['bayarea'];
                        $this->_doc->headerdata['baycity'] = $basedoc->headerdata['baycity'];
                        $this->_doc->headerdata['baypoint'] = $basedoc->headerdata['baypoint'];
 
                        $notfound = array();
                        $order = $basedoc->cast();

                        //проверяем  что уже есть отправка
                        $list = $order->getChildren('TTN');

                        if (count($list) > 0 && $common['numberttn'] <> 1) {

                            $this->setError('У замовлення вже є відправки');
                            App::Redirect("\\App\\Pages\\Register\\GIList");
                            return;
                        }
                        $list = $order->getChildren('GoodsIssue');

                        if (count($list) > 0 && $common['numberttn'] <> 1) {

                            $this->setError('У замовлення вже є відправки');
                            App::Redirect("\\App\\Pages\\Register\\GIList");
                            return;
                        }
                        $this->docform->total->setText($order->amount);


                        if($order->headerdata['store']>0) {
                            $this->docform->store->setValue($order->headerdata['store']);
                            
                        }


                        $this->OnChangeCustomer($this->docform->customer);

                        $itemlist = $basedoc->unpackDetails('detaildata');
                        $k = 1;      //учитываем  скидку
                        if ($basedoc->headerdata["paydisc"] > 0 && $basedoc->amount > 0) {
                            $k = ($basedoc->amount - $basedoc->headerdata["paydisc"]) / $basedoc->amount;
                        }

                        $this->_itemlist = array();
                        foreach ($itemlist as $i => $it) {
                            $it->price = $it->price * $k;
                            $this->_itemlist[$i] = $it;
                        }
                        $this->calcTotal();

                        if($order->state == Document::STATE_INPROCESS || $order->state == Document::STATE_READYTOSHIP) {
                            $order->updateStatus(Document::STATE_INSHIPMENT);
                        }


                    }
                    if ($basedoc->meta_name == 'Invoice') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->store->setValue($basedoc->headerdata['store']);

                        $notfound = array();
                        $invoice = $basedoc->cast();

                        $this->docform->total->setText($invoice->amount);
                        $this->docform->firm->setValue($basedoc->firm_id);


                        $this->OnChangeCustomer($this->docform->customer);

                        $itemlist = $basedoc->unpackDetails('detaildata');
                        $k = 1;      //учитываем  скидку
                        if ($basedoc->headerdata["paydisc"] > 0 && $basedoc->amount > 0) {
                            $k = ($basedoc->amount - $basedoc->headerdata["paydisc"]) / $basedoc->amount;
                        }

                        $this->_itemlist = array();
                        foreach ($itemlist as $it) {
                            $it->price = $it->price * $k;
                            $this->_itemlist[$it->item_id] = $it;
                        }

                        $this->calcTotal();
                    }


                    if ($basedoc->meta_name == 'GoodsIssue') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        if ($basedoc->customer_id > 0) {
                            $this->docform->customer->setText($basedoc->customer_name);
                        } else {
                            $this->docform->customer->setText($basedoc->headerdata['customer_name']);
                        }


                        $this->docform->salesource->setValue($basedoc->headerdata['salesource']);
                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->store->setValue($basedoc->headerdata['store']);

                        $this->docform->firm->setValue($basedoc->firm_id);

                        $this->OnChangeCustomer($this->docform->customer);
                        $k = 1;      //учитываем  скидку
                        if ($basedoc->headerdata["paydisc"] > 0 && $basedoc->amount > 0) {
                            $k = ($basedoc->amount - $basedoc->headerdata["paydisc"]) / $basedoc->amount;
                        }

                        $this->docform->nostore->setChecked(true);
                    

                        foreach ($basedoc->unpackDetails('detaildata') as $item) {
                            // $item->price = $item->getPrice($basedoc->headerdata['pricetype']);
                            $item->price = $item->price * $k;
                            $this->_itemlist[ ] = $item;
                        }
                        $this->calcTotal();
                    }

                    if ($basedoc->meta_name == 'POSCheck') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        if ($basedoc->customer_id > 0) {
                            $this->docform->customer->setText($basedoc->customer_name);
                        } else {
                            $this->docform->customer->setText($basedoc->headerdata['customer_name']);
                        }


                        $this->docform->salesource->setValue($basedoc->headerdata['salesource']);
                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->nostore->setChecked(true);

                        $this->docform->firm->setValue($basedoc->firm_id);

                        $this->OnChangeCustomer($this->docform->customer);
                        $k = 1;      //учитываем  скидку
                        if ($basedoc->headerdata["paydisc"] > 0 && $basedoc->amount > 0) {
                            $k = ($basedoc->amount - $basedoc->headerdata["paydisc"]) / $basedoc->amount;
                        }


                        foreach ($basedoc->unpackDetails('detaildata') as $item) {
                            // $item->price = $item->getPrice($basedoc->headerdata['pricetype']);
                            $item->price = $item->price * $k;
                            $this->_itemlist[] = $item;
                        }
                        $this->calcTotal();
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }


        $this->OnDelivery($this->docform->delivery);
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('num', $row->getNumber()));
        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }


        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_itemlist, true);


        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->_changedpos = true;

    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $code0 = $code;
        $code = ltrim($code, '0');

        $this->docform->barcode->setText('');
        if ($code == '') {
            return;
        }


        foreach ($this->_itemlist as $ri => $_item) {
            if ($_item->bar_code == $code || $_item->item_code == $code || $_item->bar_code == $code0 || $_item->item_code == $code0) {
                $this->_itemlist[$ri]->quantity += 1;
                $this->docform->detail->Reload();
                $this->calcTotal();

                return;
            }
        }


        $store_id = $this->docform->store->getValue();
        if ($store_id == 0) {
            $this->setError('Не обрано склад');
            return;
        }

        $code_ = Item::qstr($code);
        $item = Item::getFirst(" item_id in(select item_id from store_stock where store_id={$store_id}) and   (item_code = {$code_} or bar_code = {$code_})");

        if ($item == null) {

            $this->setWarn("Товар з кодом `{$code}` не знайдено");
            return;
        }


        $store_id = $this->docform->store->getValue();

        $qty = $item->getQuantity($store_id);
        if ($qty <= 0) {

            $this->setWarn("Товару {$item->itemname} немає на складі");
        }


        $price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);
        $item->price = $price;
        $item->quantity = 1;

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = '';
            $serial = $item->getNearestSerie($store_id);


            if (strlen($serial) == 0) {
                $this->setWarn('Потрібна партія виробника');
                $this->editdetail->setVisible(true);
                $this->docform->setVisible(false);

                $this->editdetail->edittovar->setKey($item->item_id);
                $this->editdetail->edittovar->setText($item->itemname);
                $this->editdetail->editserial->setText('');
                $this->editdetail->editquantity->setText('1');
                $this->editdetail->editprice->setText($item->price);

                return;
            } else {
                $item->snumber = $serial;
            }
        }

        $this->_itemlist[ ] = $item;

        $this->docform->detail->Reload();
        $this->calcTotal();

        $this->_rowid = -1;
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->qtystock->setText("");
        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $this->OnChangeItem($this->editdetail->edittovar);

        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editserial->setText($item->snumber);

        $this->_rowid = array_search($item, $this->_itemlist, true) ;

    }

    public function saverowOnClick($sender) {

        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);


        $store_id = $this->docform->store->getValue();

        $item->quantity = $this->editdetail->editquantity->getText();
        $item->snumber = $this->editdetail->editserial->getText();
        $qstock = $this->editdetail->qtystock->getText();

        $item->price = $this->editdetail->editprice->getText();

        if ($item->quantity > $qstock) {

            $this->setWarn('Введено більше товару, чим є в наявності');
        }

        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {

            $this->setError("Потрібна партія виробника");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($store_id);

            if (in_array($item->snumber, $slist) == false) {

                $this->setWarn('Невірний номер серії');
            } else {
                $st = Stock::getFirst("store_id={$store_id} and item_id={$item->item_id} and snumber=" . Stock::qstr($item->snumber));
                if ($st instanceof Stock) {
                    $item->sdate = $st->sdate;
                }
            }
        }

        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }

        $this->editdetail->setVisible(false);
        $this->wselitem->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editserial->setText("");
        $this->calcTotal();
        $this->_changedpos = true;

    }

    public function cancelrowOnClick($sender) {
        $this->wselitem->setVisible(false);
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }


        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        //   $this->_doc->order = $this->docform->order->getText();
        $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }

        $this->_doc->firm_id = $this->docform->firm->getValue();
        if ($this->_doc->firm_id > 0) {
            $this->_doc->headerdata['firm_name'] = $this->docform->firm->getValueName();
        }

        $this->_doc->headerdata['order_id'] = $this->_orderid;
        $this->_doc->headerdata['order'] = $this->docform->order->getText();
        $this->_doc->headerdata['ship_address'] = $this->docform->ship_address->getText();
        $this->_doc->headerdata['ship_number'] = $this->docform->ship_number->getText();
        $this->_doc->headerdata['ship_amount'] = $this->docform->ship_amount->getText();
        $this->_doc->headerdata['delivery'] = $this->docform->delivery->getValue();
        $this->_doc->headerdata['delivery_name'] = $this->docform->delivery->getValueName();
        $this->_doc->headerdata['salesource'] = $this->docform->salesource->getValue();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['store_name'] = $this->docform->store->getValueName();
        $this->_doc->headerdata['emp_id'] = $this->docform->emp->getValue();
        $this->_doc->headerdata['emp_name'] = $this->docform->emp->getValueName();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['pricetypename'] = $this->docform->pricetype->getValueName();
        $this->_doc->headerdata['delivery_date'] = $this->docform->delivery_date->getDate();
        $this->_doc->headerdata['sent_date'] = $this->docform->sent_date->getDate();
        $this->_doc->headerdata['order_id'] = $this->_orderid;
        $this->_doc->headerdata['phone'] = $this->docform->phone->getText();
        $this->_doc->headerdata['email'] = $this->docform->email->getText();
        $this->_doc->headerdata['nostore'] = $this->docform->nostore->isChecked() ? 1 : 0;
        $this->_doc->headerdata['payseller'] = $this->docform->payseller->isChecked() ? 1 : 0;

        if ($this->checkForm() == false) {
            return;
        }

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->payamount = $this->docform->total->getText();

        if($this->_doc->headerdata['nostore']==1)  {
           // $this->_doc->payamount = 0;
        }
        
        $isEdited = $this->_doc->document_id > 0;

        if ($sender->id == 'senddoc' && $this->_doc->headerdata['delivery'] > 2 && strlen($this->_doc->headerdata['delivery']) == 0) {
            $this->setError('Не вказана декларація служби доставки');
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
            $this->_doc->save();
            
            if ($sender->id == 'execdoc' ||$sender->id == 'senddoc' || $sender->id == 'sendnp') {
             
          
             
  
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
  
             
             
                if ($this->_doc->parent_id > 0) {
                    $basedoc = Document::load($this->_doc->parent_id)->cast();

                    if($this->_changedpos) {
                        if($this->_changedpos) {
                            $msg=  "У документа {$this->_doc->document_number}, створеного на підставі {$basedoc->document_number}, користувачем ".\App\System::getUser()->username." змінено перелік ТМЦ "  ;
                            \App\Entity\Notify::toSystemLog($msg) ;
                        }

                    }
                    
                    if( $basedoc->meta_name =='Order') {
                        

                        if($basedoc->state == Document::STATE_INPROCESS || $basedoc->state == Document::STATE_READYTOSHIP) {
                            $basedoc->updateStatus(Document::STATE_INSHIPMENT);
                        }                            
                    
                        
                        $basedoc->unreserve();
                    }                    
                    
                }  
 
                // проверка на минус  в  количестве
                $allowminus = System::getOption("common", "allowminus");
                if ($allowminus != 1) {

                    foreach ($this->_itemlist as $item) {
                        $qty = $item->getQuantity($this->_doc->headerdata['store']);
                        if ($qty < $item->quantity) {
                            $this->setError("На складі всього ".H::fqty($qty)." ТМЦ {$item->itemname}. Списання у мінус заборонено");
                            return;
                        }
                    }
                }                  
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                
                           
             }            
            
            
            if ($sender->id == 'execdoc') {
                $this->_doc->updateStatus(Document::STATE_READYTOSHIP);
            } else  if ($sender->id == 'senddoc') {
                 $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
            }
            else  if ($sender->id == 'sendnp') {
                 $this->_doc->updateStatus(Document::STATE_READYTOSHIP);
            }
            else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }


            $conn->CommitTrans();
            if ($sender->id == 'sendnp') {

                App::Redirect('\App\Pages\Register\GIList', $this->_doc->document_id);
                return;
            }
            App::Redirect("\\App\\Pages\\Register\\GIList");
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );
            return;
        }
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;
        $weight = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
            if ($item->weight > 0) {
                $weight = $weight + $item->weight;
            }
        }
        $this->docform->total->setText(H::fa($total));
        $this->docform->weight->setText("Загальна вага {$weight} кг");
        $this->docform->weight->setVisible($weight > 0);
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {

            $this->setError('Введіть номер документа');
        }

        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }

        if (count($this->_itemlist) == 0) {
            $this->setError("Не введено товар");
        }

        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("Не обрано склад");
        }
        $c = $this->docform->customer->getKey();

        if ($c == 0) {
            $this->setError("Не задано контрагента");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $store_id = $this->docform->store->getValue();

        $price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);
        $qty = $item->getQuantity($store_id);

        $this->editdetail->qtystock->setText(H::fqty($qty));
        $this->editdetail->editprice->setText($price);
        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = $item->getNearestSerie($store_id);

            $this->editdetail->editserial->setText($serial);
        }



    }

    public function OnAutoItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }

    public function OnChangeCustomer($sender) {


        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);

            if ($this->docform->ship_address->getText() == '') {
                $this->docform->ship_address->setText($customer->address);
            }
            $this->docform->phone->setText($customer->phone);
            $this->docform->email->setText($customer->email);
        }
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editaddress->setText('');
        $this->editcust->editphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->address = $this->editcust->editaddress->getText();
        $this->docform->ship_address->setText($cust->address);
        $cust->phone = $this->editcust->editphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
            $this->setError("");
            $this->setError("Довжина номера телефона повинна бути ".\App\Helper::PhoneL()." цифр");
            return;
        }

        $c = Customer::getByPhone($cust->phone);
        if ($c != null) {
            if ($c->customer_id != $cust->customer_id) {

                $this->setError("Вже існує контрагент з таким телефоном");
                return;
            }
        }
        $cust->type = Customer::TYPE_BAYER;
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);
        $this->docform->phone->setText($cust->phone);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function OnDelivery($sender) {


        if ($sender->getValue() != Document::DEL_SELF) {
            $this->docform->senddoc->setVisible(true);
            $this->docform->sendnp->setVisible(true);

            $this->docform->payseller->setVisible(true);
            $this->docform->ship_address->setVisible(true);
            $this->docform->ship_number->setVisible($sender->getValue() == Document::DEL_NP);
            $this->docform->ship_amount->setVisible(true);
            $this->docform->sent_date->setVisible(true);
            $this->docform->delivery_date->setVisible(true);
            $this->docform->emp->setVisible(true);
        } else {
            $this->docform->senddoc->setVisible(false);

            $this->docform->payseller->setVisible(false);
            $this->docform->ship_address->setVisible(false);
            $this->docform->ship_number->setVisible(false);
            $this->docform->ship_amount->setVisible(false);
            $this->docform->sent_date->setVisible(false);
            $this->docform->delivery_date->setVisible(false);
            $this->docform->emp->setVisible(false);
            $this->docform->ship_number->setText('');
        }
        $this->docform->sendnp->setVisible($sender->getValue() == Document::DEL_NP);
    }

    public function onOpenItemSel($sender) {
        $this->wselitem->setVisible(true);
        $this->wselitem->setPriceType($this->docform->pricetype->getValue());
        $this->wselitem->Reload();
    }

    public function onSelectItem($item_id, $itemname) {
        $this->editdetail->edittovar->setKey($item_id);
        $this->editdetail->edittovar->setText($itemname);
        $this->OnChangeItem($this->editdetail->edittovar);
    }

}
