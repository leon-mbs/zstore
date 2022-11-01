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
use WayForPay\SDK\Collection\ProductCollection;
use WayForPay\SDK\Credential\AccountSecretTestCredential;
use WayForPay\SDK\Domain\Client;
use WayForPay\SDK\Domain\Product;
use WayForPay\SDK\Wizard\PurchaseWizard;
//страница оплаты заказа
class OrderPay extends Base
{

    
    private $order;
    private $c;
    

    public function __construct($orderid=0) {
        parent::__construct();
       
        
        $this->order = Document::load($orderid);
        if($this->order == null){
            App::RedirectHome() ;
            return;
        }        
        
        $this->order = $this->order->cast();      
        
        $cid = System::getCustomer() ;
        $this->c =  Customer::load($cid);
        if($this->c == null){
            App::RedirectHome() ;
            return;
        }        
    
        $number = preg_replace('/[^0-9]/', '', $this->order->document_number);
    
        $this->_tvars['onumber'] = $number;
        $this->_tvars['detail'] = array();
        
        foreach($this->order->unpackDetails('detaildata') as $item){
            $this->_tvars['detail'][] = array(
              'itemname'=>$item->itemname,
              'qty'=> H::fqty( $item->qty),
              'price'=> H::fa($item->price),
              'sum'=>H::fa($item->price * $item->qty)
            );             
        }
        
         
    }
 
 
    public  function onPayed($args, $post) {
         $order= Document::load($this->orderid) ;
           
         $payed = \App\Entity\Pay::addPayment($order->document_id, $order->document_date, $order->payed, $order->headerdata['payment'],   'WayForPay');
         if ($payed > 0) {
             $order->payed = $payed;
    
         }
         \App\Entity\IOState::addIOState($this->document_id, $this->payed, \App\Entity\IOState::TYPE_BASE_INCOME);
         $order->save();
                   
         return json_encode(array(), JSON_UNESCAPED_UNICODE);
    }
 
 
    public  function payLP($args,$post=null) {
        if($args[0]=='success') {
             $shop = System::getOptions("shop");
   
             $payed =  Pay::addPayment($this->order->document_id,time(),$this->order->payed,$shop['mf_id'],'LiqPay ID:'.$args[1]);
             if ($payed > 0) {
                 $this->order->payed = $payed;
             }
             \App\Entity\IOState::addIOState($this->order->document_id, $this->order->payed, \App\Entity\IOState::TYPE_BASE_INCOME);
             $this->order->save();              
             $this->order->updateStatus(Document::STATE_PAYED)   ;
             
        }
    }
    
    public  function dataLP($args,$post=null) {
          $shop = System::getOptions("shop");
   
         
          $private_key = $shop['lp_priv']; //'sandbox_JOBg3ngEMQcBSjknmoSQgfYT2KC3N0Dmau17XKV2';

          $data = array(
                    'version'=> 3,
                    'public_key'=> $shop['lp_public'], //'sandbox_i2218966209',
                    'action'=> 'pay',
                    'amount'=> H::fa($this->order->payed),
                    'currency'=> 'UAH',
                    'description'=> 'Оплата товару',
                    'order_id'=> $this->order->document_number,
                    'language'=> 'uk'
                );
          $data = json_encode($data,JSON_UNESCAPED_UNICODE)  ;
          $data = base64_encode($data)  ;
        
        $ret = array();
        $ret['data']  = $data;
        $ret['sign']  =  base64_encode(sha1($private_key.$data.$private_key, 1));;
        
        
        return json_encode($ret, JSON_UNESCAPED_UNICODE);     
         
        
    }
 
 
   public  function dataWP($args,$post=null) {
          $private_key = 'flk3409refn54t54t*FNJRET';

          $data = array( );
                
        
         $data = implode(';', $data);
        
         $ret['sign'] =   hash_hmac('md5', $data, $private_key) ;       
       
    return json_encode($ret, JSON_UNESCAPED_UNICODE);     
                   
                
   }
    const FIELDS_DELIMITER  = ';';
    const DEFAULT_CHARSET   = 'utf8';

}
