<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Customer;
use App\Modules\Shop\Basket;
use App\System;
use App\Helper as H;
use App\Entity\Pay;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Image;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;

//страница оплаты заказа
class OrderPay extends Base
{
    private $order;
    private $c;


    public function __construct($orderid=0) {
        parent::__construct();


        $this->order = Document::load($orderid);
        if($this->order == null) {
            App::RedirectHome() ;
            return;
        }

        $this->order = $this->order->cast();

        $cid = System::getCustomer() ;
        $this->c =  Customer::load($cid);
        if($this->c == null) {
            App::RedirectHome() ;
            return;
        }

        $number = preg_replace('/[^0-9]/', '', $this->order->document_number);

        $this->_tvars['onumber'] = $number;
        $this->_tvars['detail'] = array();
        $this->_tvars['total'] = 0;

        foreach($this->order->unpackDetails('detaildata') as $item) {
            $this->_tvars['detail'][] = array(
              'itemname'=>$item->itemname,
              'qty'=> H::fqty($item->quantity),
              'price'=> H::fa($item->price),
              'sum'=>H::fa($item->price * $item->quantity)
            );

            $this->_tvars['total']  = H::fa($this->order->payamount);

        }


    }


    public function onPayed($args, $post) {
        $order= Document::load($this->orderid) ;

        $order->payed = \App\Entity\Pay::addPayment($order->document_id, $order->document_date, $order->payed, $order->headerdata['payment'], 'WayForPay');
     
        \App\Entity\IOState::addIOState($this->document_id, $this->payed, \App\Entity\IOState::TYPE_BASE_INCOME);
        $order->save();

        return json_encode(array(), JSON_UNESCAPED_UNICODE);
    }


    public function payLP($args, $post=null) {
        if($args[0]=='success') {
            $shop = System::getOptions("shop");

             $this->order->payed =  Pay::addPayment($this->order->document_id, time(), $this->order->payamount, $shop['mf_id'], 'LiqPay ID:'.$args[1]);
         
            \App\Entity\IOState::addIOState($this->order->document_id, $this->order->payed, \App\Entity\IOState::TYPE_BASE_INCOME);
            $this->order->save();
            $this->order->updateStatus(Document::STATE_PAYED)   ;

        }
    }

    public function dataLP($args, $post=null) {
        $shop = System::getOptions("shop");


        $private_key = $shop['lqpriv']; 

        $data = array(
                  'version'=> 3,
                  'public_key'=> $shop['lqpublic'], 
                  'action'=> 'pay',
                  'amount'=> H::fa($this->order->payamount),
                  'currency'=> 'UAH',
                  'description'=> 'Оплата товару',
                  'order_id'=> $this->order->document_number,
                  'language'=> 'uk'
              );
        $data = json_encode($data, JSON_UNESCAPED_UNICODE)  ;
        $data = base64_encode($data)  ;

        $ret = array();
        $ret['data']  = $data;
        $ret['sign']  =  base64_encode(sha1($private_key.$data.$private_key, 1));


        return json_encode($ret, JSON_UNESCAPED_UNICODE);


    }


    public function payWP($args, $post=null) {


        $shop = System::getOptions("shop");

        $payed =  Pay::addPayment($this->order->document_id, time(), $this->order->payamount, $shop['mf_id'], 'WayForPay');
        if ($payed > 0) {
            $this->order->payed = $payed;
        }
        \App\Entity\IOState::addIOState($this->order->document_id, $this->order->payed, \App\Entity\IOState::TYPE_BASE_INCOME);
        $this->order->save();
        $this->order->updateStatus(Document::STATE_PAYED)   ;




    }
    public function dataWP($args, $post=null) {
        $shop = System::getOptions("shop");

        $private_key = $shop['wpsecret']; // 'flk3409refn54t54t*FNJRET';

        $data = array( );
        $data['merchantAccount']  = $shop['wpmacc']  ;
        $data['merchantAuthType']  = 'SimpleSignature' ;
        $data['merchantDomainName']  = $shop['wpsite']  ;
        $data['orderReference']  = $this->order->document_number ;
        $data['orderDate']  = $this->order->document_date ;
        $data['amount']  = $this->order->payamount ;
        $data['currency']  = 'UAH'  ;
        $data['productName']  = array() ;
        $data['productCount']  = array() ;
        $data['productPrice']  = array() ;
        $data['language']  = "UA" ;
        $data['defaultPaymentSystem']  = "card" ;
        $data['clientFirstName']  = "tester" ;
        $data['clientLastName']  = "tester" ;
        //    $data['clientAddress']  = "UA" ;
        //      $data['clientCity']  = "UA" ;
        $data['clientPhone']  = "380631234567" ;


        foreach($this->order->unpackDetails('detaildata') as $item) {
            $data['productName'][] = $item->itemname;
            $data['productCount'][] = $item->quantity;
            $data['productPrice'][] = $item->price;

        }

        $forsign = array();
        $forsign[]= $data['merchantAccount'] ;
        $forsign[]= $data['merchantDomainName'] ;
        $forsign[]= $data['orderReference'] ;
        $forsign[]= $data['orderDate'] ;
        $forsign[]= $data['amount'] ;
        $forsign[]= $data['currency'] ;
        $forsign[]= implode(';', $data['productName']) ;
        $forsign[]= implode(';', $data['productCount']) ;
        $forsign[]= implode(';', $data['productPrice']) ;



        $data['merchantSignature'] =   hash_hmac('md5', implode(';', $forsign), $private_key) ;


        return json_encode($data, JSON_UNESCAPED_UNICODE);


    }

    public function dataQR($args, $post=null) {
        $shop = System::getOptions("shop");

        $data=[];
        $qr = $this->order->getQRPay() ;

        if(is_array($qr)) {
            $data['qrerror'] = false;
            $data['img'] = $qr['qr'] ;
            $data['url'] = $qr['url'] ;
        } else {
            $data['qrerror'] = true;
        }


        return json_encode($data, JSON_UNESCAPED_UNICODE)  ;
    }

    public const FIELDS_DELIMITER  = ';';
    public const DEFAULT_CHARSET   = 'utf8';

}
