<?php

namespace App\Shop\Pages;

use \Zippy\Html\Label;
use \Zippy\Html\Image;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\DropDownChoice;
use \App\Shop\Helper;
use \App\Shop\Basket;
use \App\Application as App;
use \App\Shop\Entity\Product;
use \App\Entity\Doc\Document;
use \App\System;

//страница формирования заказа  пользователя
class Order extends Base {

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
        $form->add(new DropDownChoice('delivery', array(1 => 'Самовывоз', 2 => 'Курьер', 3 => 'Почта')))->onChange($this, 'OnDelivery');
        $form->add(new TextInput('email'));
        $form->add(new TextInput('phone'));
        $form->add(new TextInput('address'))->setVisible(false);
        $form->add(new TextArea('contact'));
        $form->onSubmit($this, 'OnSave');
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
                $this->setError('Неверное количество');
                break;
            }

            $this->sum = $this->sum + $product->price * $product->quantity;
            $this->sum = $this->sum;
        }
        Basket::getBasket()->list = $this->basketlist;
        $this->listform->pitem->Reload();
    }

    public function OnDelete($sender) {
        $product_id = $sender->owner->getDataItem()->product_id;
        Basket::getBasket()->deleteProduct($product_id);
        $this->basketlist = Basket::getBasket()->list;

        if (Basket::getBasket()->isEmpty()) {
            App::Redirect("\\App\\Shop\\Pages\\Catalog",0);
        } else {
            $this->OnUpdate($this);
        }
    }

    //формирование  заказа
    public function OnSave($sender) {
        if (count($this->basketlist) == 0)
            return;

        $email = trim($this->orderform->email->getText());
        $phone = trim($this->orderform->phone->getText());
        $delivery = $this->orderform->delivery->getValue();
        $address = $this->orderform->address->getValue();

        if ($delivery == 0) {
            $this->setError("Выберите  тип доставки");
            return;
        }
        if (($delivery == 2 || $delivery == 3) && strlen($address) == 0) {
            $this->setError(" Введите адрес");
            return;
        }
        if (strlen($phone) == 0 && strlen($email) == 0) {
            $this->setError(" Введите телефон или  email");
            return;
        }


        try {

            $order = Document::create('Order');
            $order->document_number = $order->nextNumber();
            if (strlen($order->document_number) == 0)
                $order->document_number = 'З0001';
            $amount = 0;

            foreach ($this->basketlist as $product) {
                $item = \App\Entity\Item::load($product->item_id);
                $item->price = $product->price;
                $item->quantity = $product->quantity;
                $item->product_id = $product->product_id;
                $amount += ($product->price * $product->quantity);
                $order->detaildata[] = $item->getData();
            }

            $order->headerdata = array(
                'delivery' => $delivery,
                'delivery_name' => $this->orderform->delivery->getValueName(),
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'total' => $amount
            );

            $op = System::getOptions("shop");
            $cust = \App\Entity\Customer::load($op["defcust"]);
            if ($cust instanceof \App\Entity\Customer) {
                $order->customer_id = $cust->customer_id;
            }
            $order->headerdata['store'] = $op["defstore"];
            $order->headerdata['pricetype'] = $op["defpricetype"];

            $order->notes = trim($this->orderform->contact->getText());
            $order->amount = $amount;
            $order->save();
            $order->updateStatus(Document::STATE_NEW);

            //todo  покупатель по умолчанию
            //todo  отослаnь нотификацию
            //todo  отослаnь писмо
        } catch (Exception $ee) {
            $this->setError($ee->getMessage());
        }


        $this->orderform->contact->setText('');
        $this->basketlist = array();
        Basket::getBasket()->list = array();

        $this->orderform->setVisible(false);
        $this->listform->setVisible(false);
        $this->setSuccess("Заказ  отправлен");
        App::RedirectURI("/shop");
    }

    public function OnAddRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->setDataItem($item);
        $datarow->add(new \Zippy\Html\Link\RedirectLink('pname', '\App\Shop\Pages\ProductView', $item->product_id))->setValue($item->productname);
        $datarow->add(new Label('price', $item->price));
        $datarow->add(new TextInput('quantity', new \Zippy\Binding\PropertyBinding($item, 'quantity')));
        $datarow->add(new \Zippy\Html\Link\ClickLink('delete', $this, 'OnDelete'));
        $datarow->add(new Image('photo', "/loadimage.php?id={$item->image_id}&t=t"));
    }

}
