<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Customer;
use App\Modules\Shop\Basket;
use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Image;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use App\Modules\Shop\Entity\Product;

//страница формирования заказа  пользователя
class Order extends Base
{
    public $sum = 0;
    public $disc = 0;
    public $orderid = 0;
    public $basketlist;

    public function __construct() {
        parent::__construct();
        
        $this->sum=0;
        $this->disc=0;
        
        $this->basketlist = Basket::getBasket()->list;
        $form = $this->add(new Form('listform'));
        $form->onSubmit($this, 'OnUpdate');

        $form->add(new \Zippy\Html\DataList\DataView('pitem', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, 'basketlist')), $this, 'OnAddRow'))->Reload();
        $form->add(new Label('summa', new \Zippy\Binding\PropertyBinding($this, 'sum')));
        $form->add(new Label('disc', new \Zippy\Binding\PropertyBinding($this, 'disc')));
        $this->OnUpdate($this);


        $form = $this->add(new Form('orderform'));
        $form->add(new DropDownChoice('delivery', Document::getDeliveryTypes($this->_tvars['np'] == 1)))->onChange($this, 'OnDelivery');
        $form->add(new DropDownChoice('payment', array(), 0)) ;


        if ($this->_tvars["isfood"]) {
            $form->delivery->setValue(Document::DEL_BOY);
        }

        $form->add(new Date('deldate', time()))->setVisible($this->_tvars["isfood"]);
        $form->add(new \Zippy\Html\Form\Time('deltime', time() + 3600))->setVisible($this->_tvars["isfood"]);


        $form->add(new TextInput('email',$_COOKIE['shop_email']??''));
        $form->add(new TextInput('phone',$_COOKIE['shop_phone']??''));
        $form->add(new TextInput('firstname',$_COOKIE['shop_fn']??''));
        $form->add(new TextInput('lastname',$_COOKIE['shop_ln']??''));
        $form->add(new TextArea('address'))->setVisible(false);
        $form->add(new TextArea('notes'));
        $form->onSubmit($this, 'OnSave');

        $cid = System::getCustomer() ;
        if($cid > 0) {
            $c =  Customer::load($cid);
            $form->phone->setText($c->phone) ;
            $form->email->setText($c->email)  ;
            $form->address->setText($c->address)  ;
            $form->firstname->setText( strlen( $c->firstname ??'') >0 ? $c->firstname : $c->customer_name )  ;
            $form->lastname->setText($c->lastname)  ;
        }

        $form->add(new AutocompleteTextInput('baycity'))->onText($this, 'onTextBayCity');
        $form->baycity->onChange($this, 'onBayCity');
        $form->add(new AutocompleteTextInput('baypoint'))->onText($this, 'onTextBayPoint');;
      
        $this->OnDelivery($form->delivery);


    }

    public function OnDelivery($sender) {

        $dt = $sender->getValue();
        
        if ($dt == Document::DEL_SELF || $dt == Document::DEL_NP) {
            $this->orderform->address->setVisible(false);
        } else {
            $this->orderform->address->setVisible(true);
        }
        
        $this->orderform->baycity->setVisible($dt  == Document::DEL_NP ) ;
        $this->orderform->baypoint->setVisible($dt == Document::DEL_NP ) ;
        
    }

    public function OnUpdate($sender) {

        $this->listform->pitem->Reload();
        $this->sum = 0;


        foreach ($this->basketlist as $product) {

            if (!is_numeric($product->quantity)) {

                $this->setWarn('Невірна кількість');
                break;
            }


            $this->sum = $this->sum + ($product->price  * $product->quantity);


        }
        $this->disc =0;
        $cid = System::getCustomer() ;
        if($cid > 0) {
            $c =  Customer::load($cid);
            $d= $c->getDiscount();

            if (doubleval($d) > 0) {
                $this->disc = \App\Helper::fa(  $this->sum * ($d/100) );
                $this->sum  =  $this->sum -  $this->disc;         
            }              
        }
   
        $this->listform->disc->setVisible($this->disc >0);

        $basket = Basket::getBasket();
        $basket->list = $this->basketlist   ;
        $basket->sendCookie()  ;
    }


    public function OnDelete($sender) {
        $item_id = $sender->owner->getDataItem()->item_id;

        $basket = Basket::getBasket();
        $basket->deleteProduct($item_id) ;
        $this->basketlist = $basket->list   ;

        $this->sum = 0;
        foreach ($this->basketlist as $p) {
            $this->sum = $this->sum + ($p->price  * $p->quantity);
        }
        
		$this->listform->pitem->Reload();

        if (count($this->basketlist)==0) {
            App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Main", 0);
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
        $firstname = trim($this->orderform->firstname->getText());
        $lastname = trim($this->orderform->lastname->getText());
        $delivery = $this->orderform->delivery->getValue();
        $payment = intval($this->orderform->payment->getValue());
        $address = $this->orderform->address->getValue();

        if ($delivery == 0) {

            $this->setError("Виберіть тип доставки");
            return;
        }
        if (($delivery == 2 || $delivery == 3) && strlen($address) == 0) {

            $this->setError("Введіть адресу");
            return;
        }
        if($shop["paysystem"]==0) {
            $payment = 2;
        }
        if ($payment == 0) {

            $this->setError("Виберіть оплату");
            return;
        }



        if (strlen($phone) != \App\Helper::PhoneL()) {
            $this->setError("Довжина номера телефона повинна бути ".\App\Helper::PhoneL()." цифр");
            return;
        }

        if ($this->_tvars["isfood"] && $time < (time() + 1800)) {
            $this->setError("Невірний час доставки");
            return;
        }
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        $order = null;
        try {


            $store_id = (int)$shop["defstore"];
            $f = $shop["defbranch"] ??0;

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

            $order->document_number = $order->nextNumber($shop["defbranch"] ?? 0);

            $amount = 0;
            $itlist = array();
            foreach ($this->basketlist as $product) {
                $item = \App\Entity\Item::load($product->item_id);
                $item->price = $product->price;
                $item->quantity = $product->quantity;

                $amount += ($item->price * $item->quantity);
                $itlist[$item->item_id] = $item;
            }

            $order->headerdata = array(
                'delivery'      => $delivery,
                'delivery_name' => $this->orderform->delivery->getValueName(),
                'email'         => $email,
                'deltime'       => $time,
                'phone'         => $phone,
                'store'         => $store_id,
                'ship_address'  => $address,
                'ship_name'     => trim($firstname.' '.$lastname),
                'shoporder'     => 1,
                'paytype'       => 2,
                'totaldisc'     => $this->disc,
                'total'         => $amount
            );
             
            
            $order->packDetails('detaildata', $itlist);

            $cid = System::getCustomer() ;
            if($cid > 0) {
                $order->customer_id = $cid;
            } else {
                $cust =  Customer::getByPhone($phone);
                if ($cust instanceof \App\Entity\Customer) {
                    $order->customer_id = $cust->customer_id;
                }

            }

            if ($order->customer_id == 0) {

                $c = new  Customer();
                $c->firstname = $firstname;
                $c->lastname= $lastname;
                $c->customer_name = trim($firstname.' '.$lastname);
                $c->email = $email;
                $c->phone = $phone;
                $c->address = $address;
                $c->type =  Customer::TYPE_BAYER;
                $c->save();
                $order->customer_id = $c->customer_id;

            }
            $order->headerdata['pricetype'] = $shop["defpricetype"];
            $order->headerdata['contact'] = trim($firstname.' '.$lastname) . ', ' . $phone;
            $order->headerdata['salesource'] = $shop['salesource'];
            $order->headerdata['shoporder'] = 1;
            if($shop['defmf']>0) {
                $order->headerdata['payment'] = $shop['defmf'];
            }

            $order->notes = trim($this->orderform->notes->getText());
            $order->amount = $amount;
            $order->payamount = $amount - $this->disc;

         //   $order->branch_id = $shop["defbranch"] ?? 0;
          
            $order->user_id = intval($shop["defuser"]??0) ;
            if($order->user_id==0) {
                $user = \App\Entity\User::getByLogin('admin') ;
                $order->user_id = $user->user_id;
            }


           $order->headerdata['baycity'] = $this->orderform->baycity->getKey();
           $order->headerdata['baycityname'] = $this->orderform->baycity->getText();
           $order->headerdata['baypoint'] = $this->orderform->baypoint->getKey();
           $order->headerdata['baypointname'] = $this->orderform->baypoint->getText();
           $order->headerdata['npaddressfull'] ='';
        
            if(strlen($order->headerdata['baycity'])>1) {
               $order->headerdata['npaddressfull']  .= (' '. $this->orderform->baycity->getText() );   
            }
            if(strlen($order->headerdata['baypoint'])>1) {
               $order->headerdata['npaddressfull']  .= (' '. $this->orderform->baypoint->getText() );   
            }
              
            
            
            $order->save();

            \App\Helper::insertstat(\App\Helper::STAT_ORDER_SHOP, 0, 0) ;


            $this->orderid = intval(preg_replace('/[^0-9]/', '', $order->document_number));
            $order->updateStatus(Document::STATE_NEW);

            if ($shop['ordertype'] == 1) {  //Кассовый чек
                $order->updateStatus(Document::STATE_EXECUTED);
            } else {
                $order->updateStatus(Document::STATE_INPROCESS);
            }


            if ($shop['ordertype'] == 2) {  //уведомление  в арм  кухни
                $n = new \App\Entity\Notify();
                $n->user_id = \App\Entity\Notify::ARMFOOD;
                $n->dateshow = time();

                $n->message = serialize(array('document_id' => $order->document_id));

                $n->save();
            }



            //   $this->setSuccess("Створено замовлення " . $order->document_number);


         //   \App\Entity\Subscribe::sendSMS($phone, "Ваше замовлення номер " . $order->document_id);
            $conn->CommitTrans();

        } catch(\Exception $ee) {
            $this->setError($ee->getMessage());
            $conn->RollbackTrans();
             
            return;
        }


        $this->orderform->notes->setText('');
        $this->basketlist = array();
        Basket::getBasket()->list = array();

        $number = preg_replace('/[^0-9]/', '', $order->document_number);

        System::setSuccessMsg("Створено замовлення номер " . $number) ;

        
        setcookie("shop_fn",$firstname) ;
        setcookie("shop_ln",$lastname) ;
        setcookie("shop_phone",$phone) ;
        setcookie("shop_email",$email) ;
        
        
        if($payment == 1) {

            App::Redirect("App\\Modules\\Shop\\Pages\\Catalog\\OrderPay", array($order->document_id)) ;
            return;
        }
        App::Redirect("App\\Modules\\Shop\\Pages\\Catalog\\Main") ;


    }

    public function OnAddRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->setDataItem($item);
        $datarow->add(new \Zippy\Html\Link\RedirectLink('pname', '\App\Modules\Shop\Pages\Catalog\ProductView', $item->item_id))->setValue($item->itemname);
        $datarow->add(new Label('price', $item->price));
        $datarow->add(new TextInput('quantity', new \Zippy\Binding\PropertyBinding($item, 'quantity'))) ;
        $datarow->add(new \Zippy\Html\Link\ClickLink('delete', $this, 'OnDelete'));
        $datarow->add(new Image('photo',$item->image_url));
    }

    public function onTextBayCity($sender) {
        $text = $sender->getText()  ;
        $api = new \App\Modules\NP\Helper();
        $list = $api->searchCity($text);

        if($list['success']!=true) return;
        $opt=[];  
        foreach($list['data'] as $d ) {
            foreach($d['Addresses'] as $c) {
               $opt[$c['Ref']]=$c['Present']; 
            }
        }
        
        return $opt;
       
    }

    public function onBayCity($sender) {
     
        $this->orderform->baypoint->setKey('');
        $this->orderform->baypoint->setText('');
    }
  
    public function onTextBayPoint($sender) {
        $text = $sender->getText()  ;
        $ref=  $this->orderform->baycity->getKey();
        $api = new \App\Modules\NP\Helper();
        $list = $api->searchPoints($ref,$text);
       
        if($list['success']!=true) return;
        
        $opt=[];  
        foreach($list['data'] as $d ) {
           $opt[$d['WarehouseIndex']]=$d['Description']; 
        }
        
        return $opt;        
    }



}
