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

        
        $this->c =  Customer::load($this->order->customer_id);
        if($this->c == null) {
           // App::RedirectHome() ;
          //  return;
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
