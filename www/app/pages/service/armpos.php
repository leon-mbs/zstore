<?php

namespace App\Pages\Service;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Entity\Customer;
use \App\Helper as H;
use \App\Application as App;
use \App\System;

/**
 * АРМ кассира
 */
class ARMPos extends \App\Pages\Base {

    public $_itemlist = array();
    public $_itemlistrev = array();
    private $pos;
    private $doc = null;

    public function __construct() {
        parent::__construct();


        if (false == \App\ACL::checkShowSer('ARMPos'))
            return;

        //обшие настройки
        $this->add(new Form('form1'));
        $plist = \App\Entity\Pos::findArray('pos_name');
        $cc = $_COOKIE['posterminal'] > 0 ? $_COOKIE['posterminal'] : 0;
        if (System::getUser()->username != 'admin') {
            $plist = \App\Entity\Pos::findArray('pos_name', 'pos_id=' . $cc);
        }
        $this->form1->add(new DropDownChoice('pos', $plist, $cc));



        $this->form1->add(new SubmitButton('next1'))->onClick($this, 'next1docOnClick');

        $this->add(new Form('form2'))->setVisible(false);

        //  ввод товаров
        $this->form2->add(new Button('cancel1'))->onClick($this, 'cancel1docOnClick');
        $this->form2->add(new SubmitButton('next2'))->onClick($this, 'next2docOnClick');
        $this->form2->add(new TextInput('barcode'));
        $this->form2->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');
        $this->form2->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->form2->add(new Label('total'));

        $this->form2->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlistrev')), $this, 'detailOnRow'));

        //оплата
        $this->add(new Form('form3'))->setVisible(false);
        $this->form3->add(new DropDownChoice('payment'))->onChange($this, 'OnPayment');
        ;
        $this->form3->add(new TextInput('document_number'));

        $this->form3->add(new Date('document_date'))->setDate(time());
        $this->form3->add(new TextArea('notes'));
        $this->form3->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');


        $this->form3->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->form3->customer->onChange($this, 'OnChangeCustomer');
        $this->form3->add(new Button('cancel2'))->onClick($this, 'cancel2docOnClick');
        $this->form3->add(new SubmitButton('save'))->onClick($this, 'savedocOnClick');
        $this->form3->add(new TextInput('total2'));
        $this->form3->add(new TextInput('paydisc'));
        $this->form3->add(new TextInput('payamount'));
        $this->form3->add(new TextInput('payed'));
        $this->form3->add(new TextInput('exchange'));

        $this->form3->add(new Label('discount'));
        //печать
        $this->add(new Form('form4'))->setVisible(false);
        $this->form4->add(new Label('showcheck'));
        $this->form4->add(new Button('newdoc'))->onClick($this, 'newdoc');
        $this->form4->add(new Button('print'));


        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editserial'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new Label('qtystock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');
    }

    public function cancel1docOnClick($sender) {
        $this->form1->setVisible(true);
        $this->form2->setVisible(false);
        $this->form3->setVisible(false);
        $this->form4->setVisible(false);
    }

    public function cancel2docOnClick($sender) {

        $this->form2->setVisible(true);
        $this->form3->setVisible(false);
    }

    public function cancel3docOnClick($sender) {

        $this->form3->setVisible(true);
        $this->form4->setVisible(false);
    }

    public function next1docOnClick($sender) {
        $this->pos = \App\Entity\Pos::load($this->form1->pos->getValue());


        if ($this->pos == null) {
            $this->setError("Не выбран терминал");
            return;
        }
        setcookie("posterminal", $this->pos->pos_id, time() + 60 * 60 * 24 * 30);

        $mf = \App\Entity\MoneyFund::Load($this->pos->mf);

        $this->form3->payment->setOptionList(array($mf->mf_id => $mf->mf_name, \App\Entity\MoneyFund::BEZNAL => 'Безналичный расчет', \App\Entity\MoneyFund::PREPAID => 'Была предоплата', \App\Entity\MoneyFund::PREPAID => 'В кредит'));
        $this->form3->payment->setValue($mf->mf_id);

        $this->form1->setVisible(false);
        $this->form2->setVisible(true);

        $this->newdoc(null);
    }

    private function Reload() {

        $arr = array_keys($this->_itemlist);
        $arr = array_reverse($arr);
        $this->_itemlistrev = array();

        foreach ($arr as $id) {
            $this->_itemlistrev[$id] = $this->_itemlist[$id];
        }

        $this->form2->detail->Reload();
    }

    public function newdoc($sender) {

        $this->doc = \App\Entity\Doc\Document::create('POSCheck');

        $this->_itemlist = array();

        $this->calcTotal();


        $this->form3->document_date->setDate(time());
        $this->form3->document_number->setText($this->doc->nextNumber());
        $this->form3->customer->setKey(0);
        $this->form3->customer->setText('');
        $this->form3->paydisc->setText('0');
        $this->form3->payamount->setText('0');
        $this->form3->payed->setText('0');
        $this->form3->exchange->setText('0');
        $this->form3->discount->setText('');

        $this->form2->setVisible(true);
        $this->form4->setVisible(false);
    }

    public function next2docOnClick($sender) {
        if (count($this->_itemlist) == 0) {
            $this->setError('Не введены товары');
            return;
        }

        $this->form1->setVisible(false);
        $this->form2->setVisible(false);
        $this->form3->setVisible(true);
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->form2->barcode->getText());
        $this->form2->barcode->setText('');
        if ($code == '')
            return;


        $code_ = Item::qstr($code);
        $item = Item::getFirst("  (item_code = {$code_} or bar_code = {$code_})");



        if ($item == null) {
            $this->setError("Товар с  кодом '{$code}' не  найден");
            return;
        }

        $qty = $item->getQuantity($this->pos->store);
        if ($qty <= 0) {
            $this->setError("Товара {$item->itemname} нет на складе");
        }


        if ($this->_itemlist[$item->item_id] instanceof Item) {
            $this->_itemlist[$item->item_id]->quantity += 1;
        } else {


            $price = $item->getPrice($this->pos->pricetype, $this->pos->store);
            $item->price = $price;
            $item->quantity = 1;

            if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

                $serial = '';
                $slist = $item->getSerials($this->pos->store);
                if (count($slist) == 1) {
                    $serial = array_pop($slist);
                }

                if (strlen($serial) == 0) {
                    $this->setWarn('Нужно ввести  номер партии производителя');
                    $this->editdetail->setVisible(true);
                    $this->form2->setVisible(false);


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
            $this->_itemlist[$item->item_id] = $item;
        }
        $this->Reload();
        $this->calcTotal();
    }

    public function deleteOnClick($sender) {


        $tovar = $sender->owner->getDataItem();
        // unset($this->_itemlist[$tovar->tovar_id]);

        $this->_itemlist = array_diff_key($this->_itemlist, array($tovar->item_id => $this->_itemlist[$tovar->item_id]));
        $this->Reload();
        $this->calcTotal();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->qtystock->setText("");
        $this->form2->setVisible(false);
    }

    public function saverowOnClick($sender) {

        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }
        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getText();
        $item->snumber = $this->editdetail->editserial->getText();
        $qstock = $this->editdetail->qtystock->getText();

        $item->price = $this->editdetail->editprice->getText();

        if ($item->quantity > $qstock) {
            $this->setWarn('Введено  больше  товара  чем  в  наличии');
        }

        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("Товар требует ввода партии производителя");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($this->pos->store);

            if (in_array($item->snumber, $slist) == false) {
                $this->setWarn('Неверный номер серии');
            }
        }


        $this->_itemlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->form2->setVisible(true);

        $this->Reload();
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editserial->setText("");
        $this->calcTotal();
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->form2->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
    }

    private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        $this->form2->total->setText(H::fa($total));
        $this->form3->total2->setText(H::fa($total));
        $this->form3->payamount->setText(H::fa($total));
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);


        $price = $item->getPrice($this->pos->pricetype, $this->pos->store);
        $qty = $item->getQuantity($this->pos->store);

        $this->editdetail->qtystock->setText(H::fqty($qty));
        $this->editdetail->editprice->setText($price);
        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = '';
            $slist = $item->getSerials($this->pos->store);
            if (count($slist) == 1) {
                $serial = array_pop($slist);
            }
            $this->editdetail->editserial->setText($serial);
        }


        $this->updateAjax(array('qtystock', 'editprice', 'editserial'));
    }

    public function OnAutoItem($sender) {

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and (customer_name like {$text}  or phone like {$text} )");
    }

    public function OnChangeCustomer($sender) {
        $this->form3->discount->setVisible(false);
        $total = $this->form3->total2->getText();
        $disc = 0;


        $customer_id = $this->form3->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            if ($customer->discount > 0) {
                $this->form3->discount->setText("Постоянная скидка " . $customer->discount . '%');
                $this->form3->discount->setVisible(true);
                $disc = round($total * ($customer->discount / 100));
            } else if ($customer->bonus > 0) {
                $this->form3->discount->setText("Бонусы " . $customer->bonus);
                $this->form3->discount->setVisible(true);
                if ($total >= $customer->bonus) {
                    $disc = $customer->bonus;
                } else {
                    $disc = $total;
                }
            }
        }


        $this->form3->paydisc->setText(H::fa($disc));
        $this->form3->payamount->setText(H::fa($total - $disc));
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->form3->setVisible(false);

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
        $this->form3->customer->setText($cust->customer_name);
        $this->form3->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->form3->setVisible(true);
        $this->form3->discount->setVisible(false);
        $this->_discount = 0;
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender) {

        $this->doc->document_number = $this->form3->document_number->getText();

        $doc = Document::getFirst("   document_number = '{$this->doc->document_number}' ");
        if ($doc instanceof Document) {   //если уже  кто то  сохранил  с таким номером
            $this->doc->document_number = $this->doc->nextNumber();
            $this->form3->document_number->setText($this->doc->document_number);
        }
        $this->doc->document_date = $this->form3->document_date->getDate();
        $this->doc->notes = $this->form3->notes->getText();

        $this->doc->customer_id = $this->form3->customer->getKey();
        $this->doc->payamount = $this->form3->payamount->getText();

        $this->doc->headerdata['time'] = time();
        $this->doc->payed = $this->form3->payed->getText();
        $this->doc->headerdata['exchange'] = $this->form3->exchange->getText();
        $this->doc->headerdata['paydisc'] = $this->form3->paydisc->getText();
        $this->doc->headerdata['payment'] = $this->form3->payment->getValue();

        if ($this->doc->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID) {
            $this->doc->headerdata['paydisc'] = 0;
            $this->doc->payed = 0;
            $this->doc->payamount = 0;
        }
        if ($this->doc->headerdata['payment'] == \App\Entity\MoneyFund::CREDIT) {
            $this->doc->payed = 0;
        }

        if ($this->doc->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID && $this->doc->customer_id == 0) {
            $this->setError("Если предоплата  должен  быть  выбран  контрагент");
            return;
        }
        if ($this->doc->payamount > $this->doc->payed && $this->doc->customer_id == 0) {
            $this->setError("Если в долг должен  быть  выбран  контрагент");
            return;
        }

        $this->doc->headerdata['pos'] = $this->pos->pos_id;
        $this->doc->headerdata['store'] = $this->pos->store;
        $this->doc->headerdata['pricetype'] = $this->pos->pricetype;
        //   $this->doc->headerdata['pricetypename'] = $this->form1->pricetype->getValueName();
        $firm = H::getFirmData($this->doc->branch_id);

        $pos = \App\Entity\Pos::load($this->doc->headerdata['pos']);

        $this->doc->headerdata["firmname"] = $firm['firmname'];
        $this->doc->headerdata["inn"] = $firm['inn'];
        $this->doc->headerdata["address"] = $firm['address'];
        $this->doc->headerdata["phone"] = $pos->phone;
        $this->doc->headerdata["viber"] = $pos->viber;

        $this->doc->detaildata = array();
        foreach ($this->_itemlist as $tovar) {
            $this->doc->detaildata[] = $tovar->getData();
        }

        $this->doc->amount = $this->form3->total2->getText();
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            $this->doc->save();
            $this->doc->updateStatus(Document::STATE_NEW);

            $this->doc->updateStatus(Document::STATE_EXECUTED);
            $conn->CommitTrans();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->doc->meta_desc);
            return;
        }
        $this->form3->setVisible(false);
        $this->form4->setVisible(true);


        $check = $this->doc->generateReport();
        $this->form4->showcheck->setText($check, true);

        $text = implode("", $this->doc->generateCheck());

        $text = addslashes($text);
         
        $this->form4->print->setAttribute("href", "javascript:void(0);return false;");
        $this->form4->print->setAttribute('onClick', "printpos('{$pos->ip}','{$text}')");
    }

    public function OnPayment($sender) {
        $b = $sender->getValue();
        if ($b == \App\Entity\MoneyFund::PREPAID) {
            $this->form3->payed->setVisible(false);
            $this->form3->payamount->setVisible(false);
            $this->form3->paydisc->setVisible(false);
            $this->form3->exchange->setVisible(false);
        } else {
            $this->form3->payed->setVisible(true);
            $this->form3->payamount->setVisible(true);
            $this->form3->paydisc->setVisible(true);
            $this->form3->exchange->setVisible(true);
        }
    }

}
