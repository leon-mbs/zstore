<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\MoneyFund;

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
 * Страница  ввода  заказ (общепит)
 */
class OrderFood extends \App\Pages\Base
{
    public $_itemlist = array();

    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = -1;

    private $_prevcust = 0;   // преыдущий контрагент

     /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
   public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));

        $this->docform->add(new Label('custinfo')) ;
        $this->docform->add(new TextInput('editpaybonus'));
        $this->docform->add(new SubmitButton('bpaybonus'))->onClick($this, 'onPayBonus');
        $this->docform->add(new Label('paybonus', 0));

        $this->docform->add(new Label('totaldisc'));
        $this->docform->add(new TextInput('edittotaldisc'));
        $this->docform->add(new SubmitButton('btotaldisc'))->onClick($this, 'onTotalDisc');

        $this->docform->add(new TextInput('editpayamount'));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');
        $this->docform->add(new Label('payed', 0));
        $this->docform->add(new Label('payamount', 0));
        $this->docform->add(new Label('exchange', 0));


        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', '')));

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()));


        $this->docform->add(new TextInput('notes'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');


        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));

        //товар
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editpureprice'));
        $this->editdetail->add(new TextInput('editdisc'));

        $tlist = Item::findArray('itemname', 'disabled<>1 and item_type in(1,4)');
        $this->editdetail->add(new DropDownChoice('edittovar', $tlist, 0));
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);


        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();


        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            if($this->_doc->headerdata['arm']==1) {
                $this->setWarn('Замовлення створено в АРМ кафе')  ;
                App::Redirect("\\App\\Pages\\Service\\ARMFood");
                return;
            }
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->total->setText(H::fa($this->_doc->amount));

            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->payment->setValue($this->_doc->headerdata['payment']);


            $this->docform->payamount->setText(H::fa($this->_doc->payamount));
            $this->docform->editpayamount->setText(H::fa($this->_doc->payamount));
            $this->docform->paybonus->setText(H::fa($this->_doc->headerdata['bonus']));
            $this->docform->editpaybonus->setText($this->_doc->headerdata['bonus']);
            $this->docform->totaldisc->setText(H::fa($this->_doc->headerdata['totaldisc']));
            $this->docform->edittotaldisc->setText($this->_doc->headerdata['totaldisc']);

            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }
            $this->docform->editpayed->setText(H::fa($this->_doc->payed));
            $this->docform->payed->setText(H::fa($this->_doc->payed));


            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->pos->setValue($this->_doc->headerdata['pos']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->notes->setText($this->_doc->notes);


            $this->_prevcust = $this->_doc->customer_id;



            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
            $this->OnChangeCustomer($this->docform->customer);


        } else {
            $this->_doc = Document::create('OrderFood');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;


                }
            } else {
                $this->setWarn('Замовлення слід створювати через  АРМ касира для кафе')  ;
            }
        }

        $this->docform->detail->Reload();


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));


        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('disc', H::fa1($item->disc))) ;

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

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
        $this->editdetail->editpureprice->setText("0");
        $this->editdetail->editdisc->setText("0");

        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }




    public function saverowOnClick($sender) {

        $id = $this->editdetail->edittovar->getValue();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getText();



        $item->price = $this->editdetail->editprice->getText();
        $item->pureprice = $this->editdetail->editpureprice->getText();
        $item->disc = $this->editdetail->editdisc->getText();


        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }

        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setValue(0);

        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("");
        $this->editdetail->editprice->setText("");


        $this->calcTotal();
        $this->calcPay();
    }


    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);

        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setValue(0);

        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("");
        $this->editdetail->editprice->setText("");

    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();


        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
        $this->_doc->payamount = $this->docform->payamount->getText();


        $this->_doc->payed = $this->docform->payed->getText();
        $this->_doc->headerdata['exchange'] = $this->docform->exchange->getText();
        $this->_doc->headerdata['bonus'] = $this->docform->paybonus->getText();
        $this->_doc->headerdata['totaldisc'] = $this->docform->totaldisc->getText();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        if ($this->_doc->headerdata['payment'] == 0) {

            $this->_doc->payed = 0;
            $this->_doc->payamount = 0;
        }
        $this->_doc->headerdata['payed'] = doubleval($this->docform->payed->getText());


        if ($this->checkForm() == false) {
            return;
        }


        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['pricetypename'] = $this->docform->pricetype->getValueName();


        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->headerdata['pos'] = $this->docform->pos->getValue();



        if ($sender->id == 'execdoc') {
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

        }

        $pos = \App\Entity\Pos::load($this->_doc->headerdata['pos']);

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


                $this->_doc->DoPayment();
                $this->_doc->DoStore();

                $this->_doc->updateStatus(Document::STATE_EXECUTED);


            } else {
                if ($sender->id == 'senddoc') {
                    if (!$isEdited) {
                        $this->_doc->updateStatus(Document::STATE_NEW);
                    }
                    $this->_doc->DoPayment();
                    $this->_doc->DoStore();

                    $this->_doc->updateStatus(Document::STATE_EXECUTED);
                    $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
                    $this->_doc->headerdata['sent_date'] = time();
                    $this->_doc->save();


                } else {
                    $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);

                }
            }


            $conn->CommitTrans();
            if ($isEdited) {
                App::RedirectBack();
            } else {
                App::Redirect("\\App\\Pages\\Register\\DocList");
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

    public function onPayAmount($sender) {
        $this->docform->payamount->setText($this->docform->editpayamount->getText());
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

    public function onPayBonus() {
        $this->docform->paybonus->setText($this->docform->editpaybonus->getText());
        $this->calcPay();
        $this->goAnkor("tankor");
    }
    public function onTotalDisc() {
        $this->docform->totaldisc->setText($this->docform->edittotaldisc->getText());
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

        $this->docform->total->setText(H::fa($total));


    }

    private function calcPay() {
        $total = $this->docform->total->getText();
        $bonus = intval($this->docform->paybonus->getText());
        $totaldisc = intval($this->docform->totaldisc->getText());

        $this->docform->editpayamount->setText(H::fa($total - $bonus -$totaldisc));
        $this->docform->payamount->setText(H::fa($total - $bonus-$totaldisc));
        $this->docform->editpayed->setText(H::fa($total - $bonus-$totaldisc));
        $this->docform->payed->setText(H::fa($total - $bonus-$totaldisc));
        $this->docform->exchange->setText(H::fa(0));
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
            $this->setError("Не введено позиції");
        }
        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("Не обрано склад");
        }
        if (($this->docform->pos->getValue() > 0) == false) {
            $this->setError("Не обрано POS термінал");
        }
        $p = $this->docform->payment->getValue();
        $c = $this->docform->customer->getKey();

        if ($this->_doc->payamount > $this->_doc->payed && $c == 0) {
            $this->setError("Якщо у борг або передоплата або нарахування бонусів має бути обраний контрагент");
        }
        if ($p == 0 && $this->_doc->payed > 0) {
            $this->setError("Якщо внесена сума більше нуля, повинна бути обрана каса або рахунок");
        }
        if ($c == 0 && $this->_doc->headerdata['bonus'] > 0) {
            $this->setError("Для списання бонусів виберіть контрагента");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }


    public function OnChangeItem($sender) {
        $id = $sender->getValue();
        $item = Item::load($id);
        $store_id = $this->docform->store->getValue();

        $price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);
        $pureprice = $item->getPurePrice($this->docform->pricetype->getValue(), $store_id);
        $qty = $item->getQuantity($store_id);
        $disc=0;
        if($price >0 && $pureprice >0) {
            $disc = number_format((1 - ($price/$pureprice))*100, 1, '.', '') ;
        }
        if($disc < 0) {
            $disc=0;
        }
        $customer_id = $this->docform->customer->getKey()  ;

        if($disc ==0 && $customer_id >0) {
            $c = Customer::load($customer_id) ;
            $d = $c->getDiscount();
            if($d >0) {
                $disc = $d;
                $price = H::fa($pureprice - ($pureprice*$d/100)) ;
            }
        }

        $this->editdetail->editprice->setText($price);
        $this->editdetail->editpureprice->setText($pureprice);
        $this->editdetail->editdisc->setText($disc);



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
        $this->docform->custinfo->setText("");

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            $d = $customer->getDiscount();
            if ($d > 0) {
                $this->docform->custinfo->setText("Знижка " . $d . '%');

            } else {
                $b = $customer->getBonus();
                if ($b > 0) {
                    $this->docform->custinfo->setText("Бонусiв " . $b);

                }
            }

            if($d > 0) {

                foreach($this->_itemlist as $it) {
                    if($it->disc == 0) {

                        $it->disc = $d;
                        $it->price = H::fa($it->pureprice - ($it->pureprice*$d/100)) ;
                    }

                }

                $this->docform->detail->Reload();
                $this->calcTotal();
                $this->calcPay();
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
            $this->setError("Не введено назву");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editphone->getText();
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
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
     //   $this->docform->discount->setVisible(false);
       // $this->_discount = 0;
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

}
