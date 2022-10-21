<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\MoneyFund;
use App\Entity\Service;
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
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  кассовый чек
 */
class POSCheck extends \App\Pages\Base
{

    public  $_itemlist  = array();
    public  $_serlist   = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;
    private $_order_id  = 0;
    private $_prevcust  = 0;   // преыдущий контрагент

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new DropDownChoice('salesource', H::getSaleSources(), H::getDefSaleSource()));

        $this->docform->add(new Label('discount'))->setVisible(false);
        $this->docform->add(new TextInput('editpaydisc'));
        $this->docform->add(new SubmitButton('bpaydisc'))->onClick($this, 'onPayDisc');
        $this->docform->add(new Label('paydisc', 0));

        $this->docform->add(new TextInput('editpayamount'));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');
        $this->docform->add(new Label('payed', 0));
        $this->docform->add(new Label('payamount', 0));
        $this->docform->add(new Label('exchange', 0));

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');
        $this->docform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', '')));

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()));

        $this->docform->add(new TextInput('order'));

        $this->docform->add(new TextInput('notes'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('addser'))->onClick($this, 'addserOnClick');
        $this->docform->addser->setVisible(Service::findCnt('disabled<>1') > 0);  //показываем  если  есть  услуги


        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));

        //товар
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editserial'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new Label('qtystock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');
        //услуга
        $this->add(new Form('editserdetail'))->setVisible(false);
        $this->editserdetail->add(new TextInput('editserquantity'))->setText("1");
        $this->editserdetail->add(new TextInput('editserprice'));

        $this->editserdetail->add(new AutocompleteTextInput('editser'))->onText($this, 'OnAutoSer');
        $this->editserdetail->editser->onChange($this, 'OnChangeSer', true);

        $this->editserdetail->add(new Button('cancelser'))->onClick($this, 'cancelrowOnClick');
        $this->editserdetail->add(new SubmitButton('submitser'))->onClick($this, 'saveserOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->salesource->setValue($this->_doc->headerdata['salesource']);
            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->total->setText(H::fa($this->_doc->amount));

            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->payment->setValue(H::fa($this->_doc->headerdata['payment']));

            $this->docform->exchange->setText($this->_doc->exchange);
            $this->docform->payamount->setText(H::fa($this->_doc->payamount));
            $this->docform->editpayamount->setText(H::fa($this->_doc->payamount));
            $this->docform->paydisc->setText(H::fa($this->_doc->headerdata['paydisc']));
            $this->docform->editpaydisc->setText(H::fa($this->_doc->headerdata['paydisc']));

            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }
           $this->docform->editpayed->setText(H::fa($this->_doc->payed));
            $this->docform->payed->setText(H::fa($this->_doc->payed));
            $this->docform->exchange->setText(H::fa($this->_doc->headerdata['exchange']));


            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->pos->setValue($this->_doc->headerdata['pos']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->order->setText($this->_doc->headerdata['order']);
            $this->_order_id = $this->_doc->headerdata['order_id'];
            $this->_prevcust = $this->_doc->customer_id;

            $this->OnChangeCustomer($this->docform->customer);

            $this->_serlist = $this->_doc->unpackDetails('services');
            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('POSCheck');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->OnChangeCustomer($this->docform->customer);

                        $this->docform->salesource->setValue($basedoc->headerdata['salesource']);
                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                       // $this->docform->store->setValue($basedoc->headerdata['store']);
                        //  $this->docform->pos->setValue($basedoc->headerdata['pos']);
                        $this->_orderid = $basedocid;
                        $this->docform->order->setText($basedoc->document_number);
                        $this->docform->paydisc->setText($basedoc->headerdata['paydisc']);
                        $this->docform->editpaydisc->setText($basedoc->headerdata['paydisc']);

                        $notfound = array();
                        $order = $basedoc->cast();

            
                        
                        //проверяем  что уже есть продажа
                        $list = $order->getChildren('POSCheck');

                        if (count($list) > 0) {

                            $this->setWarn('order_has_sell');
                        }
                        $this->docform->total->setText($order->amount);

                        $this->OnChangeCustomer($this->docform->customer);
                        $this->calcPay();

                        if ($order->headerdata['payment'] > 0) {
                            $this->docform->payment->setValue(0); // предоплата
                            $this->docform->editpayed->setText(H::fa(0));
                            $this->docform->payed->setText(H::fa(0));
                            $this->docform->editpayamount->setText(H::fa(0));
                            $this->docform->payamount->setText(H::fa(0));
                            $this->docform->editpaydisc->setText(H::fa(0));
                            $this->docform->paydisc->setText(H::fa(0));
                        } else {
                            $this->docform->editpayed->setText($this->docform->editpayamount->getText());
                            $this->docform->payed->setText($this->docform->payamount->getText());

                        }                        
                        
                        
                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                    }

                    if ($basedoc->meta_name == 'Invoice') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->store->setValue($basedoc->headerdata['store']);

                        $notfound = array();
                        $invoice = $basedoc->cast();

                        $this->docform->total->setText($invoice->amount);

                        $this->OnChangeCustomer($this->docform->customer);
                        $this->calcPay();

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');

                        if ($invoice->payamount > 0) {
                            $this->docform->payment->setValie(0); // предоплата
                        }
                    }
                    if ($basedoc->meta_name == 'Task') {
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $this->docform->notes->setText('Наряд ' . $basedoc->document_number);
                        $this->_serlist = $basedoc->unpackDetails('detaildata');
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        $this->docform->add(new DataView('detailser', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_serlist')), $this, 'serOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? H::fd($item->sdate) : ''));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function serOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('service', $item->service_name));

        $row->add(new Label('serquantity', H::fqty($item->quantity)));
        $row->add(new Label('serprice', H::fa($item->price)));

        $row->add(new Label('seramount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('serdelete'))->onClick($this, 'serdeleteOnClick');
        $row->add(new ClickLink('seredit'))->onClick($this, 'sereditOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $tovar = $sender->owner->getDataItem();

        $this->_itemlist = array_diff_key($this->_itemlist, array($tovar->rowid => $this->_itemlist[$tovar->rowid]));
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
    }

    public function serdeleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $ser = $sender->owner->getDataItem();

        $this->_serlist = array_diff_key($this->_serlist, array($ser->rowid => $this->_serlist[$ser->rowid]));
        $this->docform->detailser->Reload();
        $this->calcTotal();
        $this->calcPay();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->qtystock->setText("");
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function addserOnClick($sender) {
        $this->editserdetail->setVisible(true);
        $this->editserdetail->editserquantity->setText("1");
        $this->editserdetail->editserprice->setText("0");

        $this->docform->setVisible(false);
        $this->_rowid = 0;
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
        $this->editdetail->editserial->setText($item->serial);
        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }

        $this->_rowid = $item->rowid;
    }

    public function sereditOnClick($sender) {
        $ser = $sender->getOwner()->getDataItem();
        $this->editserdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editserdetail->editser->setKey($ser->service_id);
        $this->editserdetail->editser->setText($ser->service_name);

        $this->editserdetail->editserprice->setText($ser->price);
        $this->editserdetail->editserquantity->setText($ser->quantity);

        if ($ser->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $ser->rowid = $ser->service_id;
        }

        $this->_rowid = $ser->rowid;
    }

    public function saverowOnClick($sender) {

        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("noselitem");
            return;
        }
        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getText();
        $item->snumber = $this->editdetail->editserial->getText();
        $qstock = $this->editdetail->qtystock->getText();

        $item->price = $this->editdetail->editprice->getText();

        if ($item->quantity > $qstock) {
            $this->setWarn('inserted_extra_count');
        }

        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("needs_serial");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($this->docform->store->getValue());

            if (in_array($item->snumber, $slist) == false) {

                $this->setWarn('invalid_serialno');
            }
        }

        if ($this->_rowid > 0) {
            $item->rowid = $this->_rowid;
        } else {
            $next = count($this->_itemlist) > 0 ? max(array_keys($this->_itemlist)) : 0;
            $item->rowid = $next + 1;
        }
        $this->_itemlist[$item->rowid] = $item;

        $this->_rowid = 0;

        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("");
        $this->editdetail->editprice->setText("");
        $this->editdetail->qtystock->setText("");

        $this->calcTotal();
        $this->calcPay();
    }

    public function saveserOnClick($sender) {

        $id = $this->editserdetail->editser->getKey();
        if ($id == 0) {

            $this->setError("noselservice");
            return;
        }
        $ser = Service::load($id);

        $ser->quantity = $this->editserdetail->editserquantity->getText();

        $ser->price = $this->editserdetail->editserprice->getText();

        if ($this->_rowid > 0) {
            $ser->rowid = $this->_rowid;
        } else {
            $next = count($this->_serlist) > 0 ? max(array_keys($this->_serlist)) : 0;
            $ser->rowid = $next + 1;
        }
        $this->_serlist[$ser->rowid] = $ser;

        $this->_rowid = 0;

        $this->editserdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detailser->Reload();

        //очищаем  форму
        $this->editserdetail->editser->setKey(0);
        $this->editserdetail->editser->setText('');
        $this->editserdetail->editserquantity->setText("1");
        $this->editserdetail->editserprice->setText("");
        $this->calcTotal();
        $this->calcPay();
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->editserdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("");
        $this->editdetail->editprice->setText("");
        $this->editdetail->qtystock->setText("");
        $this->editserdetail->editser->setKey(0);
        $this->editserdetail->editser->setText('');
        $this->editserdetail->editserquantity->setText("1");
        $this->editserdetail->editserprice->setText("");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->order = $this->docform->order->getText();

        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
     

        
        $this->_doc->payamount = $this->docform->payamount->getText();

        $this->_doc->payed = $this->docform->payed->getText();
        $this->_doc->headerdata['exchange'] = $this->docform->exchange->getText();
        $this->_doc->headerdata['paydisc'] = $this->docform->paydisc->getText();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();

        $this->_doc->headerdata['payed'] = $this->docform->payed->getText();

        if ($this->checkForm() == false) {
            return;
        }
        $order = Document::load($this->_order_id);

        $this->_doc->headerdata['order'] = $this->docform->order->getText();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['salesource'] = $this->docform->salesource->getValue();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['pricetypename'] = $this->docform->pricetype->getValueName();
        $this->_doc->headerdata['order_id'] = $this->_order_id;

        $this->_doc->packDetails('detaildata', $this->_itemlist);
        $this->_doc->packDetails('services', $this->_serlist);
        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->headerdata['pos'] = $this->docform->pos->getValue();

        
        
        
        if ($sender->id == 'execdoc') {
                // проверка на минус  в  количестве
                $allowminus = System::getOption("common", "allowminus");
                if ($allowminus != 1) {

                    foreach ($this->_itemlist as $item) {
                        $qty = $item->getQuantity($this->_doc->headerdata['store']);
                        if ($qty < $item->quantity) {
                            $this->setError("nominus", H::fqty($qty), $item->item_name);
                            return;
                        }
                    }
                }       
        
        }        
        
        
        
        
        
        
        $pos = \App\Entity\Pos::load($this->_doc->headerdata['pos']);

        if ($this->_tvars["ppo"] == true && $pos->usefisc == 1 && $sender->id == 'execdoc') {
            $this->_doc->headerdata["fiscalnumberpos"]  =  $pos->fiscalnumber;
            
                   
            $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
            if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                //повторяем для  нового номера
                $pos->fiscdocnumber = $ret['doclocnumber'];
                $pos->save();
                $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
            }
            if ($ret['success'] == false) {
                $this->setErrorTopPage($ret['data']);
                return;
            } else {
                //  $this->setSuccess("Выполнено") ;
                if ($ret['docnumber'] > 0) {
                    $pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                    $pos->save();
                    $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                } else {
                    $this->setError("ppo_noretnumber");
                    return;
                }
            }
            
        }


        $isEdited = $this->_doc->document_id > 0;
        if ($isEdited == false) {
            $this->_doc->headerdata['time'] = time();
        }

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }




                $this->_doc->updateStatus(Document::STATE_EXECUTED);

                $order = Document::load($this->_doc->headerdata['order_id']);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_DELIVERED);
                }
            } else {
                if ($sender->id == 'senddoc') {
                    if (!$isEdited) {
                        $this->_doc->updateStatus(Document::STATE_NEW);
                    }

                    $this->_doc->updateStatus(Document::STATE_EXECUTED);
                    $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
                    $this->_doc->headerdata['sent_date'] = time();
                    $this->_doc->save();

                    $order = Document::load($this->_doc->headerdata['order_id']);
                    if ($order instanceof Document) {
                        $order->updateStatus(Document::STATE_INSHIPMENT);
                    }
                } else {
                    $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
                    if ($order instanceof Document) {
                        $order->updateStatus(Document::STATE_INPROCESS);
                    }
                }
            }


            $conn->CommitTrans();
            if ($isEdited) {
                App::RedirectBack();
            } else {
                App::Redirect("\\App\\Pages\\Register\\GIList", $this->_doc->document_id);
            }
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }
    }

    public function onPayAmount($sender) {
        $this->docform->payamount->setText($this->docform->editpayamount->getText());
        $this->docform->editpayed->setText($this->docform->editpayamount->getText());
        $this->docform->payed->setText($this->docform->editpayamount->getText());
        $this->goAnkor("tankor");
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getText()));
        $payed = $this->docform->payed->getText();
        $payamount = $this->docform->payamount->getText();
        if ($payed > $payamount) {
            $this->docform->exchange->setText(H::fa($payed - $payamount));
        } else {
            $this->docform->exchange->setText(H::fa(0));
        }

        $this->goAnkor("tankor");
    }

    public function onPayDisc() {
        $this->docform->paydisc->setText($this->docform->editpaydisc->getText());
        $this->calcPay();
        $this->goAnkor("tankor");
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        foreach ($this->_serlist as $ser) {
            $ser->amount = $ser->price * $ser->quantity;

            $total = $total + $ser->amount;
        }
        $this->docform->total->setText(H::fa($total));

        $disc = 0;

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            $d= $customer->getDiscount();
            if ($d > 0) {
                $disc = round($total * ($d / 100));
            } else {
                if ($customer->bonus > 0) {
                    if ($total >= $customer->bonus) {
                        $disc = $customer->bonus;
                    } else {
                        $disc = $total;
                    }
                }
            }
        }


        $this->docform->paydisc->setText($disc);
        $this->docform->editpaydisc->setText($disc);
    }

    private function calcPay() {
        $total = $this->docform->total->getText();
        $disc = $this->docform->paydisc->getText();
        $disc = doubleval($disc) ;
        $this->docform->editpayamount->setText(H::fa($total - $disc));
        $this->docform->payamount->setText(H::fa($total - $disc));
        $this->docform->editpayed->setText(H::fa($total - $disc));
        $this->docform->payed->setText(H::fa($total - $disc));
        $this->docform->exchange->setText(H::fa(0));
    }


    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');
         $code0 = $code;
               $code = ltrim($code,'0');

        if ($code == '') {
            return;
        }
        $store_id = $this->docform->store->getValue();
        if ($store_id == 0) {
            $this->setError('noselstore');
            return;
        }

        $code0 = Item::qstr($code0);

        $code_ = Item::qstr($code);
        $item = Item::getFirst(" item_id in(select item_id from store_stock where store_id={$store_id}) and  (item_code = {$code_} or bar_code = {$code_}  or item_code = {$code0} or bar_code = {$code0}  )");

        if ($item == null) {
            $this->setError("noitemcode", $code);
            return;
        }


        $store = $this->docform->store->getValue();

        $qty = $item->getQuantity($store);
        if ($qty <= 0) {
            $this->setError("noitemonstore", $item->itemname);
        }

        foreach ($this->_itemlist as $ri => $_item) {
            if ($_item->bar_code == $code || $_item->item_code == $code) {
                $this->_itemlist[$ri]->quantity += 1;
                $this->docform->detail->Reload();
                $this->calcTotal();
                $this->calcPay();


                return;
            }
        }


        $price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);
        $item->price = $price;
        $item->quantity = 1;

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = $item->getNearestSerie($store_id);


            if (strlen($serial) == 0) {
                $this->setWarn('needs_serial');
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

        $next = count($this->_itemlist) > 0 ? max(array_keys($this->_itemlist)) : 0;
        $item->rowid = $next + 1;

        $this->_itemlist[$item->rowid] = $item;

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();

        $this->_rowid = 0;
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('enterdocnumber');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('docnumbercancreated');
            }
        }
        if (count($this->_itemlist) == 0 && count($this->_serlist) == 0) {
            $this->setError("noenterpos");
        }
        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("noselstore");
        }
        if (($this->docform->pos->getValue() > 0) == false) {
            $this->setError("noselposterm");
        }
        $p = $this->docform->payment->getValue();
        $c = $this->docform->customer->getKey();

        if ($this->_doc->amount > 0 && $this->_doc->payamount > $this->_doc->payed && $c == 0) {
            $this->setError("mustsel_cust");
        }
        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("noselmfp");
        }
        
        //изза  фискализации
        if ($this->docform->payment->getValue() == 0 && $this->_doc->payamount > 0) {
            $this->setError("noselmfp");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeStore($sender) {
        //очистка  списка  товаров
        $this->_itemlist = array();
        $this->docform->detail->Reload();
        $this->calcTotal() ;
        $this->calcPay() ;
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

    public function OnAutoSer($sender) {

        $text = trim($sender->getText());
        $text = Service::qstr('%' . $text . '%');
        return Service::findArray('service_name', "disabled <> 1 and service_name like {$text}");
    }

    public function OnChangeSer($sender) {
        $id = $sender->getKey();
        $ser = Service::load($id);
        $this->editserdetail->editserprice->setText($ser->getPrice());

      
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }

    public function OnChangeCustomer($sender) {
        $this->docform->discount->setVisible(false);

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            $d = $customer->getDiscount();
            if ($d > 0) {
                $this->docform->discount->setText("Постоянная скидка " . $d . '%');
                $this->docform->discount->setVisible(true);
            } else {
                if ($customer->bonus > 0) {
                    $this->docform->discount->setText("Бонусы " . $customer->bonus);
                    $this->docform->discount->setVisible(true);
                }
            }
        }
        if ($this->_prevcust != $customer_id) {//сменился контрагент
            $this->_prevcust = $customer_id;
            $this->calcTotal();

            $this->calcPay();
        }
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
            $this->setError("entername");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
            $this->setError("tel10", H::PhoneL());
            return;
        }

        $c = Customer::getByPhone($cust->phone);
        if ($c != null) {
            if ($c->customer_id != $cust->customer_id) {
                $this->setError("existcustphone");
                return;
            }
        }
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->discount->setVisible(false);
        $this->_discount = 0;
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

}
