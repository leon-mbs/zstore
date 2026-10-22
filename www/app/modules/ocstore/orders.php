<?php

namespace App\Modules\OCStore;

use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Customer;
use App\System;
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
    private $_m=null;

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $mlist=[] ;
        
        foreach( System::getOption("modules","oclist",[]) as $m){
            $mlist[$m->id]=$m->salesourcename;
        };
     
    
        
        $this->add(new Form('mform'))->onSubmit($this, 'mOnSubmit');
        $this->mform->add(new DropDownChoice('mlist', $mlist ));
        
      
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('status',System::getSession()->statuses??[] ));
        $this->add(new Form('filter2'))->onSubmit($this, 'onImport');
         
       
        $this->filter2->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));
         
        $this->add(new ClickLink('refreshbtn'))->onClick($this, 'onRefresh');
        $this->add(new Form('updateform'))->onSubmit($this, 'exportOnSubmit');
        $this->updateform->add(new DataView('orderslist', new ArrayDataSource(new Prop($this, '_eorders')), $this, 'expRow'));
        $this->updateform->add(new DropDownChoice('estatus',System::getSession()->statuses??[] ));

       
    }

    public function mOnSubmit($sender) {  
        $mlist=System::getOption("modules","oclist",[])  ;
        $id=  $this->mform->mlist->getValue();
        $this->_m= $mlist[ $id] ?? null;
       
        if($this->_m== null) {
            $this->setError('Не вибраний магазин  ');
            return;
         
        }
        $c=Helper::connect($this->_m);
        if( $c)  {
          $this->setSuccess('Ok') ;
        }
        $statuses = System::getSession()->statuses;
        if (is_array($statuses)  ) {
           $this->filter->status->setOptionlist($statuses) ;
           $this->updateform->estatus->setOptionlist($statuses) ;

        }
                 
    }
  
    public function filterOnSubmit($sender) {
   
        
        
        $status = intval( $this->filter->status->getValue() );
        
        if($status==0) {
            return;
        }

        $this->_neworders = array();
        $fields = array(
            'status_id' => $status,
        );
        $url = $this->_m->site. '/index.php?route=api/zstore/orders&' . System::getSession()->octoken;
        if($this->_m->ocv4==1) {
            $url = $this->_m->site. '/index.php?route=api/zstore.orders&' . System::getSession()->octoken;
        }
        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {
             $this->setError("Невірна відповідь");
      
            return;
        }
        $data = json_decode($json, true);
        if (!isset($data)) {
            $this->setError("Невірна відповідь");
         //   \App\Helper::log($json);
            return;
        }
        if ($data['error'] == "") {
            $conn = \ZDB\DB::getConnect();


            foreach ($data['orders'] as $ocorder) {

                $oldocid= $ocorder['order_id'].'g' ;
                $ocid= ''.$this->_m->id.'_'.$ocorder['order_id'] ;
                
                $cnt  = $conn->getOne("select count(*) from documents_view where (meta_name='Order' or meta_name='TTN') and (content like '%<ocorder>{$oldocid}</ocorder>%' or content like '%<ocorder><![CDATA[{$ocid}]]></ocorder>%'  )  ")  ;

                if (intval($cnt) > 0) { //уже импортирован
                    continue;
                }
                foreach ($ocorder['_products_'] as $product) {
                    $code = trim($product['sku']);
                    if ($code == "") {
                        $this->setWarn("Не задано артикул товара {$product['name']} в замовленні номер " . $ocorder['order_id']);
                    }
                }

                $order = new \App\DataItem($ocorder);

                $this->_neworders[$ocorder['order_id']] = $order;
            }

            $this->filter2->neworderslist->Reload();
        } else {
            $data['error']  = str_replace("'", "`", $data['error']) ;

            $this->setErrorTopPage($data['error']);
        }
    }

    public function noOnRow($row) {
        $order = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($order, 'ch')));
 
        $row->add(new Label('number', $order->order_id));
        $row->add(new Label('customer', $order->firstname . ' ' . $order->lastname));
        $row->add(new Label('amount', \App\Helper::fa($order->total)));
        $row->add(new Label('comment', $order->comment));
        $row->add(new Label('date', \App\Helper::fdt(strtotime($order->date_modified))));
    }

    public function onImport($sender) {
       
        if($this->_m->usettn==1) {
            $this->onOutcome( );            
        }   else{
            $this->onOrder( ); 
        }
        
    }
    public function onOrder(  ) {
     
        $i = 0;
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        try{     
           foreach ($this->_neworders as $shoporder) {

            if(!$shoporder->ch ) continue;
            $neworder = Document::create('Order');
         //   $neworder->document_date = strtotime($shoporder->date_added);
            $neworder->document_date = time();
            
            $neworder->document_number = $neworder->nextNumber();
            if (strlen($neworder->document_number) == 0) {
                $neworder->document_number = 'OC00001';
            }
            $total =0;
            $j=0;           //товары
            $tlist = array();
            foreach ($shoporder->_products_ as $product) {
                //ищем по артикулу
                if (strlen($product['sku']) == 0) {
                    continue;
                }
                $code = Item::qstr($product['sku']);

                $tovar = Item::getFirst('item_code=' . $code);
                if ($tovar == null) {

                    $this->setWarn("Не знайдено артикул товара {$product['name']} в замовленні номер ". $shoporder->order_id);
                    continue;
                }
                $tovar->quantity = $product['quantity'];
                $tovar->price = str_replace(',', '.', $product['price']);
                $desc = '';
                if (is_array($product['_options_'])) {
                    foreach ($product['_options_'] as $k => $v) {
                        $desc = $desc . $k . ':' . $v . ';';
                    }
                }
                //$tovar->octoreoptions = serialize($product['_options_']);
                $tovar->desc = $desc;
                $j++;
                $tovar->rowid = $j;
                $total  = $total +  ($tovar->quantity * $tovar->price) ;
                $tlist[$j] = $tovar;
            }
            if(count($tlist)==0) {
                continue;
            }
            $neworder->packDetails('detaildata', $tlist);
            $neworder->amount = \App\Helper::fa($total);
            $neworder->payamount = \App\Helper::fa($shoporder->total);

            $neworder->headerdata['totaldisc']  = $neworder->amount - $neworder->payamount;


            $neworder->headerdata['outnumber'] = ''.$this->_m->id.'_'.$shoporder->order_id;
            $neworder->headerdata['ocshopid'] =  $this->_m->id ;
            $neworder->headerdata['ocorder'] =  ''.$this->_m->id.'_'.$shoporder->order_id;
            $neworder->headerdata['ocorderback'] = 0;
            $neworder->headerdata['pricetype'] = $this->_m->pricetype;
            $neworder->headerdata['salesource'] = $this->_m->salesource;
            $neworder->headerdata['doreserve'] = $this->_m->reserve;
            $neworder->headerdata['dostore'] = $this->_m->dostore;
            $neworder->headerdata['paytype'] = $this->_m->paytype;  
            $neworder->headerdata['paytypename'] = $this->_m->paytypename ;  
            $neworder->headerdata['payment'] = $this->_m->mf ; 
            if($neworder->headerdata['paytype']==2) {
                $neworder->headerdata['waitpay'] =1;   //ждет оплату
            }
            $neworder->headerdata['store'] = $this->_m->storeid ; 
            $neworder->headerdata['dostore'] = $this->_m->dostore ; 
            $neworder->headerdata['reserve'] = $this->_m->reserve ; 
      
            $neworder->notes = "OC номер: {$shoporder->order_id};";

            $neworder->headerdata['occlient'] = $shoporder->firstname . ' ' . $shoporder->lastname;
            $neworder->notes .= " Клієнт : " . $shoporder->firstname . ' ' . $shoporder->lastname . ";";
            if( $this->_m->insertcust == 1  && strlen($shoporder->telephone ??'' )>0 ) {
                $cust=null;
 
                $phone=\App\Util::handlePhone($shoporder->telephone);
                
                if ($shoporder->customer_id > 0 ) {
                    $cust = Customer::getFirst("detail like '%<shopcust_id>{$shoporder->customer_id}</shopcust_id>%'");
                }
                if ($cust == null) {
                    $cust = Customer::getByPhone($phone) ;
                }   
     
                         
                if ($cust == null) {
                    $cust = new Customer();
                    $cust->customer_name = trim($shoporder->lastname . ' ' . $shoporder->firstname);
                    $cust->address = $shoporder->shipping_city . ' ' . $shoporder->shipping_address_1;
                    $cust->type = Customer::TYPE_BAYER;
                    $cust->phone = $phone;
                    $cust->email = $shoporder->email;
                    $cust->comment = "Клієнт  OpenCart";
                    $cust->save();
                }
                
                if ($cust != null) {
                    if ($shoporder->customer_id > 0) {
                       $cust->shopcust_id = $shoporder->customer_id;
                       $cust->save();
                    }
                    
                    $neworder->customer_id = $cust->customer_id;
                }
            }
            if (strlen($shoporder->email) > 0) {
                $neworder->notes .= " Email:" . $shoporder->email . ";";
            }
            if (strlen($shoporder->telephone) > 0) {
                $neworder->notes .= " Тел: " . $shoporder->telephone . ";";
                $neworder->headerdata['phone'] = $shoporder->telephone;            
            }
            $neworder->notes .= " Адреса:" . $shoporder->shipping_city . ' ' . $shoporder->shipping_address_1 . ";";
            $neworder->notes .= " Оплата:" . $shoporder->payment_method . ";";
            $neworder->notes .= " Коментар:" . $shoporder->comment . ";";
            
            $neworder->headerdata['ship_address']  = $shoporder->shipping_city . ' ' . $shoporder->shipping_address_1  ;
            
            if($modules['ocmf'] >0) {
               $neworder->headerdata['payment'] = $modules['ocmf'];
        
            }
            if ($neworder->headerdata['paytype'] == 2) {
                $neworder->setHD('waitpay',1); 
            }        
           
        
                $neworder->save();
                
                 
                $neworder->updateStatus(Document::STATE_NEW);
      
                $neworder->updateStatus(\App\Entity\Doc\Document::STATE_WAIT );
              
                if($neworder->headerdata['store']>0) {
                 //   $neworder->reserve();   //если задан  склад резервируем товары
                }  
      
           
            $i++;
        }
        
           $conn->CommitTrans();
          
        } catch(\Throwable $ee){
            global $logger;
            $conn->RollbackTrans();
           
            $this->setError($ee->getMessage());

            $logger->error( $ee->getMessage() . " OCStore " );
                 
           
            return;
        }        
        
        $this->setInfo("Імпортовано {$i} замовлень");
        
        $this->_neworders = array();
        $this->filter2->neworderslist->Reload();
    }

    //только  списание
    public function onOutcome( ) {
        
      
        $store = $this->_m->storeid;
  
        $allowminus = \App\System::getOption("common", "allowminus");

        if ($allowminus != 1) {
            foreach ($this->_neworders as $shoporder) {

                foreach ($shoporder->_products_ as $product) {
                    //ищем по артикулу
                    if (strlen($product['sku']) == 0) {
                        continue;
                    }
                    $code = Item::qstr($product['sku']);

                    $tovar = Item::getFirst('item_code=' . $code);
                    if ($tovar == null) {

                        $this->setWarn("Не знайдено артикул товара {$product['name']} в замовленні номер " . $shoporder->order_id);
                        continue;
                    }
                    $tovar->quantity = $product['quantity'];

                    $qty = $tovar->getQuantity($store);
                    if ($qty < $tovar->quantity) {
                        $this->setError("На складі всього ".\App\Helper::fqty($qty)." ТМЦ {$tovar->itemname}. Списання у мінус заборонено");
                        return;
                    }
                }
            }
        }
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $i = 0;
            foreach ($this->_neworders as $shoporder) {


                $neworder = Document::create('TTN');
                $neworder->document_date = time();
                $neworder->headerdata['sent_date'] = time();
                $neworder->headerdata['delivery_date'] = time()+(3600*24);
                $neworder->document_number = $neworder->nextNumber();
                if (strlen($neworder->document_number) == 0) {
                    $neworder->document_number = 'ТТН-00001';
                }

                //товары
                $j=0;
                $totalpr = 0;
                $tlist = array();
                foreach ($shoporder->_products_ as $product) {
                    //ищем по артикулу
                    if (strlen($product['sku']) == 0) {
                        continue;
                    }
                    $code = Item::qstr($product['sku']);

                    $tovar = Item::getFirst('item_code=' . $code);
                    if ($tovar == null) {

                        $this->setWarn("Не знайдено артикул товара {$product['name']} в замовленні номер " . $shoporder->order_id);
                        continue;
                    }
                    $tovar->quantity = $product['quantity'];
                    $tovar->price = \App\Helper::fa($product['price']);
                    $totalpr += ($tovar->quantity * $tovar->price);
                    $j++;
                    $tovar->rowid = $j;

                    $tlist[$j] = $tovar;
                }
                $neworder->packDetails('detaildata', $tlist);

                $neworder->headerdata['store'] = $store;
                $neworder->headerdata['store_name'] = \App\Entity\Store::load($store)->storename ?? '';
                
                $neworder->headerdata['outnumber'] = ''.$this->_m->id.'_'.$shoporder->order_id;
                $neworder->headerdata['ocshopid'] =  $this->_m->id ;
                $neworder->headerdata['ocorder'] =  ''.$this->_m->id.'_'.$shoporder->order_id;
    


                $neworder->amount = \App\Helper::fa($totalpr);

                if ($shoporder->total > $totalpr) {
                    $neworder->headerdata['ship_amount'] = $shoporder->total - $totalpr;
                    $neworder->headerdata['delivery'] = Document::DEL_SELF;
                    $neworder->headerdata['delivery_name'] = 'Самовивіз';
                }

                $neworder->payamount = 0;
                $neworder->payed = 0;
                $neworder->notes = "OC номер:{$shoporder->order_id};";
                $neworder->notes .= " Клієнт :" . $shoporder->firstname . ' ' . $shoporder->lastname . ";";
                if (strlen($shoporder->email) > 0) {
                    $neworder->notes .= " Email:" . $shoporder->email . ";";
                }
                if (strlen($shoporder->telephone) > 0) {
                    $neworder->notes .= " Тел:" . $shoporder->telephone . ";";
                }
                $neworder->notes .= " Адреса:" . $shoporder->shipping_city . ' ' . $shoporder->shipping_address_1 . ";";
                $neworder->notes .= " Коментар:" . $shoporder->comment . ";";
                $neworder->save();
                $neworder->updateStatus(Document::STATE_NEW);
                $neworder->updateStatus(Document::STATE_EXECUTED);
                $neworder->updateStatus(Document::STATE_DELIVERED);

                $i++;
            }

            $conn->CommitTrans();


        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();


            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " OCStore ");
            return;
        }

        $this->setInfo("Імпортовано {$i} замовлень");

        $this->_neworders = array();
        $this->filter2->neworderslist->Reload();
    }
 

    public function onRefresh($sender) {                                                                             
          
        $this->_eorders = Document::find("meta_name='Order' and content like '%<ocorderback>0</ocorderback>%'  and state <>1 and state <>23   and content like '%<ocshopid>{$this->_m->id}</ocshopid>%'    " );
        $this->updateform->orderslist->Reload();
    }

    public function expRow($row) {
        $order = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($order, 'ch')));
        $row->add(new Label('number2', $order->document_number));
        $row->add(new Label('number3', $order->headerdata['ocorder']));
        $row->add(new Label('date2', \App\Helper::fd($order->document_date)));
        $row->add(new Label('amount2', $order->amount));
        $row->add(new Label('customer2', $order->headerdata['occlient']));
        $row->add(new Label('state', Document::getStateName($order->state)));
    }

    public function exportOnSubmit($sender) {
         
        $st = $this->updateform->estatus->getValue();
        if ($st == 0) {

            $this->setError('Не обрано статус');
            return;
        }
        $elist = array();
        foreach ($this->_eorders as $order) {
            if ($order->ch == false) {
                continue;
            }
            $elist[$order->headerdata['ocorder']] = $st;
        }
        if (count($elist) == 0) {

            $this->setError('Не обрано ордер');
            return;
        }
        $data = json_encode($elist);

        $fields = array(
            'data' => $data
        );
   
        $url = $this->_m->site. '/index.php?route=api/zstore/updateorder&' . System::getSession()->octoken;
        if($this->_m->ocv4==1) {
            $url = $this->_m->site. '/index.php?route=api/zstore.updateorder&' . System::getSession()->octoken;
        }
  
        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {
            return;
        }
        $data = json_decode($json, true);

        if ($data['error'] != "") {
            $data['error']  = str_replace("'", "`", $data['error']) ;

            $this->setErrorTopPage($data['error']);
            return;
        }

        $this->setSuccess("Оновлено ".count($elist)." замовлень");

        foreach ($this->_eorders as $order) {
            if ($order->ch == false) {
                continue;
            }
            $order->headerdata['ocorderback'] = 1;
            $order->save();
        }


        $this->_eorders = Document::find("meta_name='Order' and content like '%<ocorderback>0</ocorderback>%' and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }

}
