<?php

namespace App\Modules\Shop\Pages;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Modules\Shop\Basket;
use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Image;
use Zippy\Html\Label;

//страница формирования заказа  пользователя
class Order extends Base
{

    public $sum = 0;
    public $basketlist;

    public function __construct() {
        parent::__construct();
        $this->basketlist = Basket::getBasket()->list;
        $form = $this->add(new Form('listform'));
        $form->onSubmit($this, 'OnUpdate');

        $form->add(new \Zippy\Html\DataList\DataView('pitem', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, 'basketlist')), $this, 'OnAddRow'))->Reload();
        $form->add(new Label('summa', new \Zippy\Binding\PropertyBinding($this, 'sum')));
        $this->OnUpdate($this);
        $form = $this->add(new Form('orderform'));
        $form->add(new DropDownChoice('delivery', Document::getDeliveryTypes($this->_tvars['np'] == 1)))->onChange($this, 'OnDelivery');

        if ($this->_tvars["isfood"]) {
            $form->delivery->setValue(Document::DEL_BOY);
        }

        $form->add(new Date('deldate', time()))->setVisible($this->_tvars["isfood"]);
        $form->add(new \Zippy\Html\Form\Time('deltime', time() + 3600))->setVisible($this->_tvars["isfood"]);


        $form->add(new TextInput('email'));
        $form->add(new TextInput('phone'));
        $form->add(new TextInput('name'));
        $form->add(new TextArea('address'))->setVisible(false);
        $form->add(new TextArea('notes'));
        $form->onSubmit($this, 'OnSave');

        $this->OnDelivery($form->delivery);

    }

    public function OnDelivery($sender) {

        if ($sender->getValue() == 2 || $sender->getValue() == 3) {
            $this->orderform->address->setVisible(true);
        } else {
            $this->orderform->address->setVisible(false);
        }
    }

    public function OnUpdate($sender) {


        $this->sum = 0;

        $rows = $this->listform->pitem->getDataRows();
        foreach ($rows as $row) {
            $product = $row->GetDataItem();
            if (!is_numeric($product->quantity)) {

                $this->setError('invalidquantity');
                break;
            }

            $this->sum = $this->sum + $product->getPriceFinal() * $product->quantity;
        }
        Basket::getBasket()->list = $this->basketlist;
        $this->listform->pitem->Reload();
    }

    public function OnDelete($sender) {
        $item_id = $sender->owner->getDataItem()->item_id;
        Basket::getBasket()->deleteProduct($item_id);
        $this->basketlist = Basket::getBasket()->list;

        if (Basket::getBasket()->isEmpty()) {
            App::Redirect("\\App\\Modules\\Shop\\Pages\\Main", 0);
        } else {
            $this->OnUpdate($this);
        }
    }

    //формирование  заказа
    public function OnSave($sender) {
        if (count($this->basketlist) == 0) {
            return;
        }
        $shop = System::getOptions("shop");

        $time = trim($this->orderform->deldate->getDate());
        $time = trim($this->orderform->deltime->getDateTime($time));
        $email = trim($this->orderform->email->getText());
        $phone = trim($this->orderform->phone->getText());
        $name = trim($this->orderform->name->getText());
        $delivery = $this->orderform->delivery->getValue();
        $address = $this->orderform->address->getValue();

        if ($delivery == 0) {

            $this->setError("enterdelivery");
            return;
        }
        if (($delivery == 2 || $delivery == 3) && strlen($address) == 0) {

            $this->setError("enteraddress");
            return;
        }
        if (($delivery == 2 || $delivery == 3) && strlen($phone) == 0) {

            $this->setError("enterteldeliv");
            return;
        }


        if ($this->_tvars["isfood"] == true) {

            if (strlen($phone) == 0) {

                $this->setError("entertelemail");
                return;
            }
        } else {
            if (strlen($phone) == 0 && strlen($email) == 0) {

                $this->setError("entertelemail");
                return;
            }
        }
        if (strlen($phone) > 0 && strlen($phone) != \App\Helper::PhoneL()) {
            $this->setError("tel10", \App\Helper::PhoneL());
            return;
        }

        if ($this->_tvars["isfood"] && $time < (time() + 1800)) {
            $this->setError("timedelivery");
            return;
        }


        try {


            $store_id = (int)$shop["defstore"];
            $f = 0;

            $store = \App\Entity\Store::load($store_id);
            if ($store != null) {
                $f = $store->branch_id;
            }


            if ($shop['ordertype'] == 1) {
                $order = Document::create('POSCheck', $f);
            } else {
                if ($shop['ordertype'] == 2) {
                    $order = Document::create('OrderFood', $f);
                } else {
                    $order = Document::create('Order', $f);
                }

            }

            $order->document_number = $order->nextNumber();

            $amount = 0;
            $itlist = array();
            foreach ($this->basketlist as $product) {
                $item = \App\Entity\Item::load($product->item_id);
                $item->price = $product->getPriceFinal();
                $item->quantity = $product->quantity;
                $item->item_id = $product->item_id;
                $amount += ($product->getPriceFinal() * $product->quantity);
                $itlist[$item->item_id] = $item;
            }

            $order->headerdata = array(
                'delivery'      => $delivery,
                'delivery_name' => $this->orderform->delivery->getValueName(),
                'email'         => $email,
                'deltime'       => $time,
                'phone'         => $phone,
                'ship_address'  => $address,
                'ship_name'     => $name,
                'total'         => $amount
            );
            $order->packDetails('detaildata', $itlist);

            $cust = \App\Entity\Customer::getByEmail($email);
            if ($cust instanceof \App\Entity\Customer) {
                $order->customer_id = $cust->customer_id;
            }
            $cust = \App\Entity\Customer::getByPhone($phone);
            if ($cust instanceof \App\Entity\Customer) {
                $order->customer_id = $cust->customer_id;
            }

            if ($order->customer_id == 0) {
                $cust = \App\Entity\Customer::load($op["defcust"]);
                if ($cust instanceof \App\Entity\Customer) {
                    $order->customer_id = $cust->customer_id;
                }

                if ($shop['createnewcust'] == 1) {

                    $c = new \App\Entity\Customer();
                    $c->customer_name = $name;
                    $c->email = $email;
                    $c->phone = $phone;
                    $c->save();
                    $order->customer_id = $c->customer_id;
                }
            }
            $order->headerdata['pricetype'] = $shop["defpricetype"];
            $order->headerdata['contact'] = $name . ', ' . $phone;

            $order->notes = trim($this->orderform->notes->getText());
            $order->amount = $amount;
            $order->payamount = $amount;
            $order->branch_id = $op["defbranch"];
            $order->save();
            $order->updateStatus(Document::STATE_NEW);
            if ($shop['ordertype'] == 1) {  //Кассовый чек
                $order->updateStatus(Document::STATE_EXECUTED);
            }


            if ($shop['ordertype'] == 2) {  //уведомление  в арм  кухни
                $n = new \App\Entity\Notify();
                $n->user_id = \App\Entity\Notify::ARMFOOD;
                $n->dateshow = time();
               
                $n->message = serialize(array('document_id'=>$order->document_id)) ;

                $n->save();
            }


            $this->setSuccess("shopneworder", $order->document_number);

            if (strlen($phone) > 0) {
                \App\Entity\Subscribe::sendSMS($phone, \App\Helper::l("shopyoursorder", $order->document_number));
            }
        } catch(\Exception $ee) {
            $this->setError($ee->getMessage());
        }


        $this->orderform->notes->setText('');
        $this->basketlist = array();
        Basket::getBasket()->list = array();

        $this->orderform->setVisible(false);
        $this->listform->setVisible(false);

        App::RedirectURI("/shop");
    }

    public function OnAddRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->setDataItem($item);
        $datarow->add(new \Zippy\Html\Link\RedirectLink('pname', '\App\Modules\Shop\Pages\ProductView', $item->item_id))->setValue($item->itemname);
        $datarow->add(new Label('price', $item->getPriceFinal()));
        $datarow->add(new TextInput('quantity', new \Zippy\Binding\PropertyBinding($item, 'quantity')));
        $datarow->add(new \Zippy\Html\Link\ClickLink('delete', $this, 'OnDelete'));
        $datarow->add(new Image('photo', "/loadshopimage.php?id={$item->image_id}&t=t"));
    }

}
