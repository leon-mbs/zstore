<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Service;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница    счет фактура
 */
class Invoice extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;


    /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');
        $this->docform->add(new DropDownChoice('firm', \App\Entity\Firm::getList(), H::getDefFirm()))->onChange($this, 'OnCustomerFirm');
        $this->docform->add(new DropDownChoice('contract', array(), 0))->setVisible(false);

        $this->docform->add(new TextArea('notes'));

        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()))->onChange($this, 'OnChangePriceType');

        $this->docform->add(new TextInput('email'));
        $this->docform->add(new TextInput('phone'));
        $this->docform->add(new TextInput('customer_print'));

        $this->docform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(2), H::getDefMF()));

        $this->docform->add(new Label('custdisc'));

        $this->docform->add(new Label('payamount', 0));

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('addserrow'))->onClick($this, 'addserrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total', 0));
        $this->docform->add(new Label('totaldisc', 0));
        $this->docform->add(new TextInput('edittotaldisc'));
        $this->docform->add(new SubmitButton('btotaldisc'))->onClick($this, 'onTotaldisc');

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');


        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new Label('qtystock'));
        $this->editdetail->add(new Label('pricestock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        $this->add(new Form('editserdetail'))->setVisible(false);
        $this->editserdetail->add(new DropDownChoice('editservice', Service::findArray("service_name", "disabled<>1", "service_name")))->onChange($this, 'OnChangeServive', true);
        $this->editserdetail->add(new TextInput('editserquantity'))->setText("1");
        $this->editserdetail->add(new TextInput('editserprice'));
        $this->editserdetail->add(new Button('cancelserrow'))->onClick($this, 'cancelrowOnClick');
        $this->editserdetail->add(new SubmitButton('submitserrow'))->onClick($this, 'saveserrowOnClick');

        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editcustphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->totaldisc->setText($this->_doc->headerdata['totaldisc']);

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }


            $this->docform->payamount->setText($this->_doc->payamount);


            $this->docform->total->setText($this->_doc->amount);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->email->setText($this->_doc->headerdata['email']);
            $this->docform->phone->setText($this->_doc->headerdata['phone']);
            $this->docform->customer_print->setText($this->_doc->headerdata['customer_print']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);


            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
            $this->docform->firm->setValue($this->_doc->firm_id);

            $this->OnChangeCustomer($this->docform->customer);
            $this->docform->contract->setValue($this->_doc->headerdata['contract_id']);
        } else {
            $this->_doc = Document::create('Invoice');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->OnChangeCustomer($this->docform->customer);

                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->totaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->edittotaldisc->setText($basedoc->headerdata['totaldisc']);

                        $this->docform->notes->setText("Рахунок для ". $basedoc->document_number);
                        $order = $basedoc->cast();

                        $this->docform->total->setText($order->amount);

                        $this->calcPay();

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                    }
                    if ($basedoc->meta_name == 'ServiceAct') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->OnChangeCustomer($this->docform->customer);
                        $this->docform->totaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->edittotaldisc->setText($basedoc->headerdata['totaldisc']);


                        $this->docform->pricetype->setValue('price1');


                        $this->docform->notes->setText("Рахунок для ". $basedoc->document_number);
                        $order = $basedoc->cast();

                        $this->docform->total->setText($order->amount);

                        $this->calcPay();

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                        $this->_itemlist = array_merge($this->_itemlist, $basedoc->unpackDetails('detail2data'));


                    }
                    if ($basedoc->meta_name == 'GoodsIssue') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->OnChangeCustomer($this->docform->customer);

                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->totaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->edittotaldisc->setText($basedoc->headerdata['totaldisc']);

                        $this->docform->notes->setText("Рахунок для ". $basedoc->document_number);
                        $order = $basedoc->cast();

                        $this->docform->total->setText($order->amount);

                        $this->calcPay();

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }

    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', strlen($item->itemname) > 0 ? $item->itemname : $item->service_name));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('disc', H::fa($item->disc)));

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
        $this->calcPay();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    public function addserrowOnClick($sender) {
        $this->editserdetail->setVisible(true);
        $this->editserdetail->editserquantity->setText("1");
        $this->editserdetail->editserprice->setText("");
        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->docform->setVisible(false);
        if ($item instanceof Item) {
            $this->editdetail->setVisible(true);

            $this->editdetail->editquantity->setText($item->quantity);
            $this->editdetail->editprice->setText($item->price);

            $this->editdetail->edittovar->setKey($item->item_id);
            $this->editdetail->edittovar->setText($item->itemname);
        }
        if ($item instanceof Service) {
            $this->editserdetail->setVisible(true);

            $this->editserdetail->editserquantity->setText($item->quantity);
            $this->editserdetail->editserprice->setText($item->price);

            $this->editserdetail->editservice->setValue($item->service_id);

        }


        $this->_rowid =  array_search($item, $this->_itemlist, true);

    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getText();

        $item->price = $this->editdetail->editprice->getText();

        $item->disc = '';
        $item->pureprice = $item->getPurePrice();
        if($item->pureprice > $item->price) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }

        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }


        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
    }

    public function saveserrowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editserdetail->editservice->getValue();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }



        $item = Service::load($id);



        $item->quantity = $this->editserdetail->editserquantity->getText();

        $price = $this->editserdetail->editserprice->getText();

        $item->disc = '';
        $item->pureprice = $item->getPurePrice();
        if($item->pureprice > $price) {
            $item->disc = number_format((1 - ($price/($item->pureprice)))*100, 1, '.', '') ;
        }

        $item->price = $price;

        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }

        $this->editserdetail->setVisible(false);

        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
        //очищаем  форму
        $this->editserdetail->clean();


        $this->editserdetail->editserquantity->setText("1");


    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->editserdetail->setVisible(false);
        $this->editserdetail->clean();
        $this->editserdetail->editserquantity->setText("1");

        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->clean();

        $this->editdetail->editquantity->setText("1");

    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();

        if ($this->checkForm() == false) {
            return;
        }

 
        $this->_doc->headerdata['totaldisc'] = $this->docform->totaldisc->getText();
        $this->_doc->headerdata['email'] = $this->docform->email->getText();
        $this->_doc->headerdata['phone'] = $this->docform->phone->getText();
        $this->_doc->headerdata['customer_print'] = $this->docform->customer_print->getText();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['contract_id'] = $this->docform->contract->getValue();
        $this->_doc->firm_id = $this->docform->firm->getValue();

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->payed = 0;
        $this->_doc->payamount = $this->docform->payamount->getText();

        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
            $this->_doc->save();
            if ($sender->id == 'savedoc') {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                $this->_doc->updateStatus(Document::STATE_WP);

            }


            $conn->CommitTrans();
            if ($sender->id == 'execdoc') {
                // App::Redirect("\\App\\Pages\\Register\\GList");
                //  return;
            }

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

            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );
            return;
        }
    }
 
    public function onTotaldisc($sender) {
        $this->docform->totaldisc->setText(H::fa($this->docform->edittotaldisc->getText()));
        $this->calcPay() ;
    }
  
    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = H::fa($item->price * $item->quantity);

            $total = $total + $item->amount;
        }

        $this->docform->total->setText(H::fa($total));
 
    }

    private function calcPay() {
        $total = $this->docform->total->getText();
        $totaldisc = $this->docform->totaldisc->getText();

        if($totaldisc) {
            $total = $total - $totaldisc;
        }


        $this->docform->payamount->setText(H::fa($total));


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

        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity($this->docform->store->getValue())));
        $price = $item->getLastPartion(0, "", false);
        $this->editdetail->pricestock->setText(H::fa($price));

        $store_id = $this->docform->store->getValue();

        $customer_id = $this->docform->customer->getKey()  ;
        $pt=     $this->docform->pricetype->getValue() ;
        $price = $item->getPriceEx(array(
         'pricetype'=>$pt,
         'store'=>$store_id,
         'customer'=>$customer_id
         ));


        $this->editdetail->editprice->setText(H::fa($price));

    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 0, true);
    }

    public function OnChangeServive($sender) {
        $id = $sender->getValue();
        $customer_id = $this->docform->customer->getKey()  ;

        $item = Service::load($id);
        $price = $item->getPrice($customer_id);

        $this->editserdetail->editserprice->setText($price);


    }

    public function OnChangeCustomer($sender) {
        $this->docform->custdisc->setVisible(false);

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $cust = Customer::load($customer_id);
            if (strlen($cust->pricetype) > 4) {
                $this->docform->pricetype->setValue($cust->pricetype);
            }

            $disctext = "";
            $d =  $cust->getDiscount() ;
            if (doubleval($d) > 0) {
                $disctext = "Постійна знижка " . " {$d}%";
            } else {
                $bonus = $cust->getBonus();
                if ($bonus > 0) {
                    $disctext = "Нараховано бонусів {$bonus} ";
                }
            }
            $this->docform->custdisc->setText($disctext);
            $this->docform->custdisc->setVisible(strlen($disctext) >0);

        }



        //    $this->calcTotal();
        //     $this->calcPay();
        $this->OnCustomerFirm(null);
    }

    public function OnAutoItem($sender) {
        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editcustphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editcustphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
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
        $cust->type = 1;
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->custdisc->setVisible(false);

        $this->docform->phone->setText($cust->phone);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function OnChangePriceType($sender) {
        foreach ($this->_itemlist as $item) {
            //$item = Item::load($item->item_id);
            $price = $item->getPrice($this->docform->pricetype->getValue());
        }

        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    public function OnCustomerFirm($sender) {
        $c = $this->docform->customer->getKey();
        $f = $this->docform->firm->getValue();

        $ar = \App\Entity\Contract::getList($c, $f);

        $this->docform->contract->setOptionList($ar);
        if (count($ar) > 0) {
            $this->docform->contract->setVisible(true);
        } else {
            $this->docform->contract->setVisible(false);
            $this->docform->contract->setValue(0);
        }
    }

    public function getPriceByQty($args, $post=null) {
        $item = Item::load($args[0]) ;
        $args[1] = str_replace(',', '.', $args[1]) ;
        $price = $item->getActionPriceByQuantity($args[1]);


        return $price;

    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');
        $code0 = $code;
        $code = ltrim($code, '0');

        if ($code == '') {
            return;
        }

        foreach ($this->_itemlist as $ri => $_item) {
            if ($_item->bar_code == $code || $_item->item_code == $code  || $_item->bar_code == $code0 || $_item->item_code == $code0) {
                $this->_itemlist[$ri]->quantity += 1;
                $this->docform->detail->Reload();
                $this->calcTotal();
                $this->CalcPay();
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


        $qty = $item->getQuantity($store_id);
        if ($qty <= 0) {

            $this->setWarn("Товару {$item->itemname} немає на складі");
        }


        $customer_id = $this->docform->customer->getKey()  ;
        $pt=     $this->docform->pricetype->getValue() ;
        $item->price = $item->getPriceEx(array('pricetype'=>$pt));


        $item->quantity = 1;

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = '';
            $slist = $item->getSerials($store_id);
            if (count($slist) == 1) {
                $serial = array_pop($slist);
            }


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


        $next = count($this->_itemlist) > 0 ? max(array_keys($this->_itemlist)) : 0;
        $item->rowid = $next + 1;

        $this->_itemlist[$item->rowid] = $item;

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();

        $this->_rowid = 0;
    }

}
