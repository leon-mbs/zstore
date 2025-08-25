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

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");
        $statuses = System::getSession()->statuses;
        if (is_array($statuses) == false) {
            $statuses = array();
            $this->setWarn('Нажміть перевірити з`єднання  ');
        }

        $defpaytype=intval($modules['ocpaytype']??2);
        $defstore=intval($modules['ocstore']);
        $defmf=intval($modules['ocmf']??0);
           
        
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('status', $statuses, 0));
        $this->add(new Form('filter2'))->onSubmit($this, 'onImport');
        $pt=[];
        $pt[1] = 'Оплата зразу (передплата)';
        $pt[2] = 'Постоплата';
        $pt[3] = 'Оплата в Чеку або ВН';
        $pt[4] = 'Тiльки списати зi складу';
          
        $this->filter2->add(new DropDownChoice('paytype',$pt, $defpaytype));
         
        $this->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));

        
        $this->add(new ClickLink('refreshbtn'))->onClick($this, 'onRefresh');
        $this->add(new Form('updateform'))->onSubmit($this, 'exportOnSubmit');
        $this->updateform->add(new DataView('orderslist', new ArrayDataSource(new Prop($this, '_eorders')), $this, 'expRow'));
        $this->updateform->add(new DropDownChoice('estatus', $statuses, 0));

        $this->add(new ClickLink('checkconn'))->onClick($this, 'onCheck');

    }

    public function filterOnSubmit($sender) {
   
        if(strlen(System::getSession()->octoken)==0) {
            Helper::connect();
        }
        $modules = System::getOptions("modules");

        $status = $this->filter->status->getValue();

        $this->_neworders = array();
        $fields = array(
            'status_id' => $status,
        );
        $url = $modules['ocsite'] . '/index.php?route=api/zstore/orders&' . System::getSession()->octoken;
        if($modules['ocv4']==1) {
            $url = $modules['ocsite'] . '/index.php?route=api/zstore.orders&' . System::getSession()->octoken;
        }
        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {
            return;
        }
        $data = json_decode($json, true);
        if (!isset($data)) {
            $this->setError("Невірна відповідь");
            \App\Helper::log($json);
            return;
        }
        if ($data['error'] == "") {
            $conn = \ZDB\DB::getConnect();


            foreach ($data['orders'] as $ocorder) {


                $cnt  = $conn->getOne("select count(*) from documents_view where (meta_name='Order' or meta_name='TTN') and content like '%<ocorder>{$ocorder['order_id']}</ocorder>%'  and (CURRENT_DATE - INTERVAL 1 MONTH) < document_date  ")  ;

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

            $this->neworderslist->Reload();
        } else {
            $data['error']  = str_replace("'", "`", $data['error']) ;

            $this->setErrorTopPage($data['error']);
        }
    }

    public function noOnRow($row) {
        $order = $row->getDataItem();

        $row->add(new Label('number', $order->order_id));
        $row->add(new Label('customer', $order->firstname . ' ' . $order->lastname));
        $row->add(new Label('amount', \App\Helper::fa($order->total)));
        $row->add(new Label('comment', $order->comment));
        $row->add(new Label('date', \App\Helper::fdt(strtotime($order->date_modified))));
    }

    public function onImport($sender) {
          
        if($sender->paytype->getValue() ==4) {
            $this->onOutcome( );            
        }   else{
            $this->onOrder( ); 
        }
        
    }
    public function onOrder(  ) {
        $defpaytype = $this->filter2->paytype->getValue() ;
            
        $modules = System::getOptions("modules");
        $defstore=intval($modules['ocstoreid']);
        $defmf=intval($modules['ocmf']);
 
        $i = 0;
        foreach ($this->_neworders as $shoporder) {


            $neworder = Document::create('Order');
            $neworder->document_date = strtotime($shoporder->date_added);
  
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
                if (array($product['_options_'])) {
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
                return;
            }
            $neworder->packDetails('detaildata', $tlist);
            $neworder->amount = \App\Helper::fa($total);
            $neworder->payamount = \App\Helper::fa($shoporder->total);

            $neworder->headerdata['totaldisc']  = $neworder->amount - $neworder->payamount;


            $neworder->headerdata['outnumber'] = $shoporder->order_id;
            $neworder->headerdata['ocorder'] = $shoporder->order_id;
            $neworder->headerdata['ocorderback'] = 0;
            $neworder->headerdata['pricetype'] = 'price1';
            $neworder->headerdata['salesource'] = $modules['ocsalesource'];
            $neworder->headerdata['paytype'] = $defpaytype;  
            $neworder->headerdata['paytypename'] = $this->filter2->paytype->getValueName() ;  
            $neworder->headerdata['payment'] = $defmf ; 
            if($neworder->headerdata['paytype']==2) {
                $neworder->headerdata['waitpay'] =1;   //ждет оплату
            }
            $neworder->headerdata['store'] = $defstore ; 
      
            $neworder->notes = "OC номер: {$shoporder->order_id};";

            $neworder->headerdata['occlient'] = $shoporder->firstname . ' ' . $shoporder->lastname;
            $neworder->notes .= " Клiєнт: " . $shoporder->firstname . ' ' . $shoporder->lastname . ";";
            if( $modules['ocinsertcust'] == 1  && strlen($shoporder->telephone ??'' )>0 ) {
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
                    $cust->comment = "Клiєнт OpenCart";
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
                $neworder->headerdata['phone'] = $phone;            
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
  
            $neworder->updateStatus(\App\Entity\Doc\Document::STATE_INPROCESS);
          
            if($neworder->headerdata['store']>0) {
                $neworder->reserve();   //если задан  склад резервируем товары
            }            


            $i++;
        }
        $this->setInfo("Імпортовано {$i} замовлень");

        $this->_neworders = array();
        $this->neworderslist->Reload();
    }

    //только  списание
    public function onOutcome( ) {
        $modules = System::getOptions("modules");
       
        $store=intval($modules['ocstoreid']);
        $kassa=intval($modules['ocmf']);
        
        
        if ($store == 0) {
            $this->setError("Не задано склад");
            return;
        }
        if ($kassa == 0) {
            $this->setError("Не задано касу");
            return;
        }
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

                        $this->setWarn("Не знайдено артикул товара {$product['name']} в замовленні номер " . $shoporder['order_id']);
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

                        $this->setWarn("Не знайдено артикул товара {$product['name']} в замовленні номер " . $shoporder['order_id']);
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
                $neworder->headerdata['store_name'] = $this->filter2->store->getValueName();
                $neworder->headerdata['ocorder'] = $shoporder->order_id;
                $neworder->headerdata['outnumber'] = $shoporder->order_id;



                $neworder->amount = \App\Helper::fa($totalpr);

                if ($shoporder->total > $totalpr) {
                    $neworder->headerdata['ship_amount'] = $shoporder->total - $totalpr;
                    $neworder->headerdata['delivery'] = Document::DEL_SELF;
                    $neworder->headerdata['delivery_name'] = 'Самовивіз';
                }

                $neworder->payamount = 0;
                $neworder->payed = 0;
                $neworder->notes = "OC номер:{$shoporder->order_id};";
                $neworder->notes .= " Клiєнт:" . $shoporder->firstname . ' ' . $shoporder->lastname . ";";
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
        $this->neworderslist->Reload();
    }

    public function onCheck($sender) {

        Helper::connect();
        \App\Application::Redirect("\\App\\Modules\\OCStore\\Orders");
    }

    public function onRefresh($sender) {

        $this->_eorders = Document::find("meta_name='Order' and content like '%<ocorderback>0</ocorderback>%' and state <> " . Document::STATE_NEW);
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
        $modules = System::getOptions("modules");

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
        $url = $modules['ocsite'] . '/index.php?route=api/zstore/updateorder&' . System::getSession()->octoken;
        if($modules['ocv4']==1) {
            $url = $modules['ocsite'] . '/index.php?route=api/zstore.updateorder&' . System::getSession()->octoken;
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
