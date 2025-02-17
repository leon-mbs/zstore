<?php

namespace App\Modules\HR;

use App\Entity\Doc\Document;
use App\Entity\Customer;
use App\Entity\Item;
use App\System;
use App\Helper as H;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Application as App;

class Orders extends \App\Pages\Base
{
    public $_neworders = array();
    public $_eorders   = array();
   
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'horoshop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки") ;

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");


        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('status', [], 'pending'));

        $this->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));

        $this->add(new ClickLink('importbtn'))->onClick($this, 'onImport');

        $this->add(new ClickLink('refreshbtn'))->onClick($this, 'onRefresh');
        $this->add(new Form('updateform'))->onSubmit($this, 'exportOnSubmit');
        $this->updateform->add(new DataView('orderslist', new ArrayDataSource(new Prop($this, '_eorders')), $this, 'expRow'));
        $this->updateform->add(new DropDownChoice('estatus', [], 'delivered'));

        $this->add(new ClickLink('checkconn'))->onClick($this, 'onCheck');

    }


    public function filterOnSubmit($sender) {
        $modules = System::getOptions("modules");
        $defpaytype=intval($modules['hrpaytype']);
        $defstore=intval($modules['hrstore']);
        $defmf=intval($modules['hrmf']);

        $st = $this->filter->status->getValue();
         
        $this->_neworders = array();

        $token=  \App\Modules\HR\Helper::connect();
        if(strlen($token)==0) {
            return;
        } 
        
        
        
        try {
           $body=[];
           $body['token'] =$token;
           $body['status'] =$st;
   

           $ret =   \App\Modules\HR\Helper::make_request("POST", "/api/orders/get/", json_encode($body, JSON_UNESCAPED_UNICODE));
             
   
        } catch(\Exception $ee) {
            $this->setErrorTopPage($ee->getMessage());
            return;
        }


        $conn = \ZDB\DB::getConnect();

        foreach ($ret['orders'] as $hrorder) {

            $cnt  = $conn->getOne("select count(*) from documents_view where meta_name='Order' and content like '%<hrorder>{$hrorder['order_id']}</hrorder>%' ")  ;


            if (intval($cnt) > 0) { //уже импортирован
                continue;
            }

            $neworder = Document::create('Order');

            $amount=0;
            //товары
            $j=0;
            $itlist = array();
            foreach ($hrorder['products'] as $product) {
                //ищем по артикулу
                if (strlen($product['article']) == 0) {
                    continue;
                }
                $code = Item::qstr($product['article']);

                $tovar = Item::getFirst('item_code=' . $code);
                if ($tovar == null) {

                    $this->setWarn("Не знайдено артикул товара {$product['article']} в замовленні номер " . $hrorder['order_id']);
                    continue;
                }
                $tovar->quantity = H::fqty($product['quantity']);
                $tovar->price = H::fa($product['price']);
                $amount += ($product['price']*$product['quantity']);
                $j++;
                $tovar->rowid = $j;

                $itlist[$j] = $tovar;
            }

            if(count($itlist)==0) {
                return;
            }
            $neworder->packDetails('detaildata', $itlist);
            $neworder->headerdata['pricetype'] = 'price1';

            $neworder->headerdata['cemail'] = $hrorder['delivery_email'];
            $neworder->headerdata['cname'] = $hrorder['delivery_name'] ;
            $neworder->headerdata['cphone'] = $hrorder['delivery_phone'] ;
            $neworder->headerdata['hrorder'] = $hrorder['order_id'];
            $neworder->headerdata['outnumber'] = $hrorder['order_id'];
            $neworder->headerdata['hrorderback'] = 0;
            $neworder->headerdata['salesource'] = $modules['hrsalesource'];
            $neworder->headerdata['paytype'] = $defpaytype;  //постоплата
            $neworder->headerdata['payment'] = $defmf;   
            $neworder->headerdata['store'] = $defstore;   
 
           
            $neworder->headerdata['hrclient'] = $hrorder['delivery_name'] ;

            $neworder->amount = H::fa($amount);
            $neworder->payamount = H::fa($amount);


            $neworder->document_date = strtotime($hrorder['stat_created']);
            if($neworder->document_date==0) {
                $neworder->document_date = time()   ;
            }           
            $neworder->notes = "HR номер:{$hrorder['order_id']};";
            $neworder->notes .= " Клієнт:" .$hrorder['delivery_name'] ;
            if (strlen($hrorder['delivery_email']) > 0) {
                $neworder->notes .= " Email:" . $hrorder['delivery_email'] . ";";
            }
            if (strlen($hrorder['delivery_phone']) > 0) {
                $neworder->notes .= " Тел:" . str_replace('+', '', $hrorder['delivery_phone']). ";";
            }
            if (strlen($hrorder['delivery_option']['name']) > 0) {
                $neworder->notes .= " Доставка:" . ($hrorder['delivery_type']['title']??'')  . ";";
            }

            if (strlen($hrorder['delivery_address']) > 0) {
                $neworder->notes .= " Адреса:" . $hrorder['delivery_city'] .' '. $hrorder['delivery_address']. ";";
            }
            if (strlen($hrorder['comment']) > 0) {
                $neworder->notes .= " Комментар:" . $hrorder['comment'] . ";";
            }


            $this->_neworders[$hrorder['order_id']] = $neworder;
        }

        $this->neworderslist->Reload();
    }

    public function noOnRow($row) {
        $order = $row->getDataItem();

        $row->add(new Label('number', $order->headerdata['hrorder']));
        $row->add(new Label('customer', $order->headerdata['hrclient']));
        $row->add(new Label('amount', round($order->amount)));
        $row->add(new Label('comment', $order->notes));
        $row->add(new Label('date', \App\Helper::fdt($order->document_date)));
    }

    public function onImport($sender) {
        $modules = System::getOptions("modules");

        foreach ($this->_neworders as $shoporder) {
            $shoporder->document_number = $shoporder->nextNumber();
            if (strlen($shoporder->document_number) == 0) {
                $shoporder->document_number = 'HR-00001';
            }

            if ( $modules['hrinsertcust'] == 1) {
                $phone = \App\Util::handlePhone($shoporder->headerdata['cphone'] )  ;
                $cust = Customer::getByPhone($phone);
                if ($cust == null) {
                    $cust = Customer::getByEmail($shoporder->headerdata['cemail']);
                }   
                if ($cust == null &&strlen($shoporder->headerdata['cname']) >0 && ( strlen($phone) >0 || strlen($shoporder->headerdata['cemail'])>0 ) ) {
                    $cust = new Customer();
                    $cust->customer_name =  $shoporder->headerdata['cname'];
                    $cust->phone = $phone;

                    $cust->type = Customer::TYPE_BAYER;

                    $cust->email = $shoporder->headerdata['cemail'];
                    $cust->comment = "Клiєнт Хорошоп";
                    $cust->save();
                }        
                if($cust != null) {         
                    $shoporder->customer_id = $cust->customer_id;
                }
            }


            $shoporder->save();
            $shoporder->updateStatus(Document::STATE_NEW);
            $shoporder->updateStatus(Document::STATE_INPROCESS);
            if($modules['pusetpayamount']==1) {
                $shoporder->updateStatus(Document::STATE_WP);
            }


        }

        $this->setInfo("Імпортовано ".count($this->_neworders)." замовлень");

        $this->_neworders = array();
        $this->neworderslist->Reload();
    }

    public function onRefresh($sender) {

        $this->_eorders = Document::find("meta_name='Order' and content like '%<hrorderback>0</hrorderback>%' and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }

    public function expRow($row) {
        $order = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($order, 'ch')));
        $row->add(new Label('number2', $order->document_number));
        $row->add(new Label('number3', $order->headerdata['hrorder']));
        $row->add(new Label('date2', \App\Helper::fdt($order->document_date)));
        $row->add(new Label('amount2', $order->amount));
        $row->add(new Label('customer2', $order->headerdata['hrclient']));
        $row->add(new Label('state', Document::getStateName($order->state)));
    }

    public function exportOnSubmit($sender) {
        $modules = System::getOptions("modules");
        $st = $this->updateform->estatus->getValue();
        if($st==0){
            return;
        }

        $elist = array();
        foreach ($this->_eorders as $order) {
            if ($order->ch == false) {
                continue;
            }
            $elist[] = $order;
        }
        if (count($elist) == 0) {
            $this->setError('Не обрано ордер');
            return;
        }


        $token=  \App\Modules\HR\Helper::connect();
        if(strlen($token)==0) {
            return;
        } 
        
         $body=[];
         $body['token'] =$token;
         $body['orders'] =[];
        

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans(); 
        try {
            
            foreach ($elist as $order) {

                $body['orders'][]  = array('order_id'=>$order->headerdata['hrorder'],'status'=>$st);


                $order->headerdata['hrorderback'] = 1;
                $order->save();
            }            
            
          $ret =   \App\Modules\HR\Helper::make_request("POST", "/api/orders/update/", json_encode($body, JSON_UNESCAPED_UNICODE));
          $conn->CommitTrans();

        } catch(\Exception $ee) {
            $conn->RollbackTrans();
            
            $this->setErrorTopPage($ee->getMessage());
            return;
        }
  

        $this->setSuccess("Оновлено ".count($elist)." замовлень");



        $this->_eorders = Document::find("meta_name='Order' and content like '%<hrorderback>0</hrorderback>%' and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }
 
    public function onCheck($sender) {

        $token=  \App\Modules\HR\Helper::connect();
        if(strlen($token)==0) {
            return;
        }        
        try {
                $stlist=[];
                
                $body=[];
                $body['token'] =$token;
       

                $ret =   \App\Modules\HR\Helper::make_request("POST", "/api/orders/get_available_statuses", json_encode($body, JSON_UNESCAPED_UNICODE));
             
      
                foreach($ret['statuses'] as $st){
                    $title = $st['title']['ua'] ?? '';
                    if($title=='') {
                        $title = $st['title']['ru'] ?? '';                        
                    }
                    
                    if(strlen($title) >0){
                         $stlist[$st['id']]=$title;
                    }
                    
                }
      
      
                $this->filter->status->setOptionList($stlist);      
                $this->updateform->estatus->setOptionList($stlist);      
            
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
    }

}
