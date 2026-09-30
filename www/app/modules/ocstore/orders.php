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
    public $_siteid    = 0;   //выбранный  сайт

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        //выбор  сайта  показывается  если  сайтов  больше  одного
        $sites = array();
        foreach (Helper::sites() as $site) {
            $sites[$site['id']] = $site['name'];
        }
        $this->_siteid = intval(System::getSession()->ocsiteid);
        if (isset($sites[$this->_siteid]) == false) {
            $this->_siteid = intval(Helper::site()['id'] ?? 0);
        }
        $this->_tvars['ocmulti'] = count($sites) > 1;

        $this->add(new Form('siteform'));
        $this->siteform->add(new DropDownChoice('site', $sites, $this->_siteid))->onChange($this, 'onSite');

        $site = Helper::site($this->_siteid);
        $statuses = Helper::statuses($this->_siteid);
        if (is_array($statuses) == false) {
            $statuses = array();
            $this->setWarn('Натисніть Перевірити з`єднання  ');
        }

        $defpaytype=intval($site['paytype']??0);

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('status', $statuses, 0));
        $this->add(new Form('filter2'))->onSubmit($this, 'onImport');
        $pt=[];
        $pt[1] = 'Оплата зразу (передплата)';
        $pt[2] = 'Постоплата';
        $pt[3] = 'Оплата в Чеку або ВН';
        $pt[4] = 'Тільки списати зі складу';
          
        $this->filter2->add(new DropDownChoice('paytype',$pt, $defpaytype));
         
        $this->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));

        
        $this->add(new ClickLink('refreshbtn'))->onClick($this, 'onRefresh');
        $this->add(new Form('updateform'))->onSubmit($this, 'exportOnSubmit');
        $this->updateform->add(new DataView('orderslist', new ArrayDataSource(new Prop($this, '_eorders')), $this, 'expRow'));
        $this->updateform->add(new DropDownChoice('estatus', $statuses, 0));

        $this->add(new ClickLink('checkconn'))->onClick($this, 'onCheck');

    }

    //сменился  сайт
    public function onSite($sender) {
        $this->_siteid = intval($sender->getValue());
        System::getSession()->ocsiteid = $this->_siteid;

        $this->_neworders = array();
        $this->neworderslist->Reload();
        $this->_eorders = array();
        $this->updateform->orderslist->Reload();

        $site = Helper::site($this->_siteid);
        $this->filter2->paytype->setValue(intval($site['paytype'] ?? 0));

        $this->updateStatuses();
        if (is_array(Helper::statuses($this->_siteid)) == false) {
            $this->setWarn('Натисніть Перевірити з`єднання  ');
        }
    }

    //списки  статусов  выбранного  сайта
    private function updateStatuses() {
        $statuses = Helper::statuses($this->_siteid);
        if (is_array($statuses) == false) {
            $statuses = array();
        }
        if ($statuses != $this->filter->status->getOptionList()) {
            $this->filter->status->setOptionList($statuses);
            $this->updateform->estatus->setOptionList($statuses);
        }
    }

    public function filterOnSubmit($sender) {

        $status = $this->filter->status->getValue();
        if ($status == 0) {
            $this->setError('Не обрано статус');
            return;
        }

        $this->_neworders = array();
        $fields = array(
            'status_id' => $status,
        );
        $data = Helper::request($this->_siteid, 'api/zstore/orders', $fields);
        $this->updateStatuses();
        $orders = $data === false ? false : Helper::rows($this->_siteid, $data, 'orders');
        $warn = array();
        if ($orders !== false) {

            foreach ($orders as $ocorder) {

                //один  номер  заказа  может  быть  на  разных  сайтах
                if (Helper::isImported($this->_siteid, $ocorder['order_id'], $ocorder['date_added'] ?? '')) { //уже импортирован
                    continue;
                }
                foreach ($ocorder['_products_'] as $product) {
                    $code = trim($product['sku']);
                    if ($code == "") {
                        $warn[] = "№ {$ocorder['order_id']}: не задано артикул товару {$product['name']}";
                    }
                }

                $order = new \App\DataItem($ocorder);

                $this->_neworders[$ocorder['order_id']] = $order;
            }

            $this->neworderslist->Reload();
        }
        $this->showWarnings($warn);
    }

    //предупреждения  одним  сообщением: первые  три  и  сколько  еще
    private function showWarnings($warn) {
        if (count($warn) == 0) {
            return;
        }
        $text = implode('. ', array_slice($warn, 0, 3));
        if (count($warn) > 3) {
            $text .= '. І ще ' . (count($warn) - 3);
        }
        $this->setWarn($text);
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
        $pt=$sender->paytype->getValue() ;
        if($pt==0){
            $this->setError('Не вказано тип оплати')  ;
            return;
        } 
        if($sender->paytype->getValue() ==4) {
            $this->onOutcome( );            
        }   else{
            $this->onOrder( ); 
        }
        
    }
    public function onOrder(  ) {
        $defpaytype = $this->filter2->paytype->getValue() ;

        $site = Helper::site($this->_siteid);   //настройки  сайта, с  которого  заказы
        if ($site == null) {
            $this->setError('Не задано сайт OpenCart');
            return;
        }
        $defstore=intval($site['storeid'] ?? 0);
        $defmf=intval($site['mf'] ?? 0);
 
        $i = 0;
        $warn = array();
        if (Helper::lock() == false) {
            $this->setError('Імпорт замовлень зараз виконує інший користувач. Спробуйте за хвилину');
            return;
        }
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        try{     
           foreach ($this->_neworders as $shoporder) {

            //пока  список  был  на  экране, заказ  мог  импортировать  другой  пользователь
            if (Helper::isImported($site['id'], $shoporder->order_id, $shoporder->date_added)) {
                $warn[] = "№ {$shoporder->order_id} уже імпортовано";
                continue;
            }

            $neworder = Document::create('Order');
            $neworder->document_date = strtotime($shoporder->date_added);
  
            $neworder->document_number = $neworder->nextNumber();
            if (strlen($neworder->document_number) == 0) {
                $neworder->document_number = 'OC00001';
            }
            $total =0;
            $j=0;           //товары
            $tlist = array();
            $notfound = array();
            foreach ($shoporder->_products_ as $product) {
                //ищем по артикулу
                if (strlen($product['sku']) == 0) {
                    $notfound[] = $product['name'];
                    continue;
                }
                $code = Item::qstr($product['sku']);

                $tovar = Item::getFirst('item_code=' . $code);
                if ($tovar == null) {

                    $notfound[] = $product['name'];
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
            if(count($tlist)==0) { //ни  одного  товара  по  артикулу - заказ  пропускаем, остальные  импортируются
                $warn[] = "№ {$shoporder->order_id} не імпортовано - не знайдено за артикулом: " . implode(', ', $notfound);
                continue;
            }
            if (count($notfound) > 0) {
                $warn[] = "№ {$shoporder->order_id} імпортовано без товарів, яких не знайдено за артикулом: " . implode(', ', $notfound);
            }
            $neworder->packDetails('detaildata', $tlist);
            $neworder->amount = \App\Helper::fa($total);
            $neworder->payamount = \App\Helper::fa($shoporder->total);

            $neworder->headerdata['totaldisc']  = $neworder->amount - $neworder->payamount;


            $neworder->headerdata['outnumber'] = $shoporder->order_id;
            $neworder->headerdata['ocorder'] = $shoporder->order_id;
            $neworder->headerdata['ocsite'] = $site['id'];
            $neworder->headerdata['ocorderback'] = 0;
            $neworder->headerdata['pricetype'] = 'price1';
            $neworder->headerdata['salesource'] = $site['salesource'] ?? 0;
            $neworder->headerdata['paytype'] = $defpaytype;  
            $neworder->headerdata['paytypename'] = $this->filter2->paytype->getValueName() ;  
            $neworder->headerdata['payment'] = $defmf ; 
            if($neworder->headerdata['paytype']==2) {
                $neworder->headerdata['waitpay'] =1;   //ждет оплату
            }
            $neworder->headerdata['store'] = $defstore ; 
      
            //данные  покупателя  и  доставки  в  своих  полях, что  писать  в  примечание - в  настройках  сайта
            $info = Helper::orderInfo($shoporder);
            $neworder->notes = Helper::notes($site, $shoporder);

            $neworder->headerdata['occlient'] = $info['name'];
            if ($info['phone'] != '') {
                $neworder->headerdata['phone'] = $info['phone'];
            }
            $neworder->headerdata['email'] = $info['email'];
            $neworder->headerdata['ship_address'] = $info['address'];
            if ($info['delivery'] > 0) {
                $neworder->headerdata['delivery'] = $info['delivery'];
                $neworder->headerdata['delivery_name'] = $info['delivery_name'];
            }
            $neworder->headerdata['ocshipping'] = $info['shipping'];
            $neworder->headerdata['ocpayment'] = $info['payment'];

            if (($site['insertcust'] ?? 0) == 1) {
                $cust = Helper::customer($site, $shoporder);
                if ($cust != null) {
                    $neworder->customer_id = $cust->customer_id;
                    $neworder->headerdata['customer_name'] = $cust->customer_name;
                }
            }
            
            if($defmf >0) {
               $neworder->headerdata['payment'] = $defmf;

            }
            if ($neworder->headerdata['paytype'] == 2) {
                $neworder->setHD('waitpay',1); 
            }        
           
        
                $neworder->save();
                
                 
                $neworder->updateStatus(Document::STATE_NEW);
      
                $neworder->updateStatus(\App\Entity\Doc\Document::STATE_WAIT);
              
                if($neworder->headerdata['store']>0) {
                    $neworder->reserve();   //если задан  склад резервируем товары
                }

                Helper::afterImport($neworder, $site['id'], $shoporder);
      
           
            $i++;
        }
        
           $conn->CommitTrans();
            Helper::unlock();
          
        } catch(\Throwable $ee){
            global $logger;
            $conn->RollbackTrans();
            Helper::unlock();
           
            $this->setError($ee->getMessage());

            $logger->error( $ee->getMessage() . " OCStore " );
                 
           
            return;
        }        
        
        $this->setInfo("Імпортовано {$i} замовлень");
        $this->showWarnings($warn);
        
        $this->_neworders = array();
        $this->neworderslist->Reload();
    }

    //только  списание
    public function onOutcome( ) {
        $site = Helper::site($this->_siteid);   //настройки  сайта, с  которого  заказы
        if ($site == null) {
            $this->setError('Не задано сайт OpenCart');
            return;
        }

        $store=intval($site['storeid'] ?? 0);
        $kassa=intval($site['mf'] ?? 0);
        
        
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
        $warn = array();
        if (Helper::lock() == false) {
            $this->setError('Імпорт замовлень зараз виконує інший користувач. Спробуйте за хвилину');
            return;
        }
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $i = 0;
            foreach ($this->_neworders as $shoporder) {

                //пока  список  был  на  экране, заказ  мог  импортировать  другой  пользователь
                if (Helper::isImported($site['id'], $shoporder->order_id, $shoporder->date_added)) {
                    $warn[] = "№ {$shoporder->order_id} уже імпортовано";
                    continue;
                }

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
                $notfound = array();
                foreach ($shoporder->_products_ as $product) {
                    //ищем по артикулу
                    if (strlen($product['sku']) == 0) {
                        $notfound[] = $product['name'];
                        continue;
                    }
                    $code = Item::qstr($product['sku']);

                    $tovar = Item::getFirst('item_code=' . $code);
                    if ($tovar == null) {

                        $notfound[] = $product['name'];
                        continue;
                    }
                    $tovar->quantity = $product['quantity'];
                    $tovar->price = \App\Helper::fa($product['price']);
                    $totalpr += ($tovar->quantity * $tovar->price);
                    $j++;
                    $tovar->rowid = $j;

                    $tlist[$j] = $tovar;
                }
                if (count($tlist) == 0) { //ни  одного  товара  по  артикулу - заказ  пропускаем, остальные  импортируются
                    $warn[] = "№ {$shoporder->order_id} не імпортовано - не знайдено за артикулом: " . implode(', ', $notfound);
                    continue;
                }
                if (count($notfound) > 0) {
                    $warn[] = "№ {$shoporder->order_id} імпортовано без товарів, яких не знайдено за артикулом: " . implode(', ', $notfound);
                }
                $neworder->packDetails('detaildata', $tlist);

                $neworder->headerdata['store'] = $store;
                $neworder->headerdata['store_name'] = \App\Entity\Store::load($store)->storename ?? '';
                $neworder->headerdata['ocorder'] = $shoporder->order_id;
                $neworder->headerdata['ocsite'] = $site['id'];
                $neworder->headerdata['outnumber'] = $shoporder->order_id;



                $neworder->amount = \App\Helper::fa($totalpr);

                if ($shoporder->total > $totalpr) {
                    $neworder->headerdata['ship_amount'] = $shoporder->total - $totalpr;
                    $neworder->headerdata['delivery'] = Document::DEL_SELF;
                    $neworder->headerdata['delivery_name'] = 'Самовивіз';
                }

                $neworder->payamount = 0;
                $neworder->payed = 0;
                $info = Helper::orderInfo($shoporder);
                $neworder->notes = Helper::notes($site, $shoporder);
                $neworder->headerdata['ship_address'] = $info['address'];
                $neworder->headerdata['phone'] = $info['phone'];
                $neworder->headerdata['email'] = $info['email'];
                if (($site['insertcust'] ?? 0) == 1) {
                    $cust = Helper::customer($site, $shoporder);
                    if ($cust != null) {
                        $neworder->customer_id = $cust->customer_id;
                        $neworder->headerdata['customer_name'] = $cust->customer_name;
                    }
                }
                $neworder->save();
                $neworder->updateStatus(Document::STATE_NEW);
                $neworder->updateStatus(Document::STATE_EXECUTED);
                $neworder->updateStatus(Document::STATE_DELIVERED);

                Helper::afterImport($neworder, $site['id'], $shoporder);

                $i++;
            }

            $conn->CommitTrans();
            Helper::unlock();


        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            Helper::unlock();


            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " OCStore ");
            return;
        }

        $this->setInfo("Імпортовано {$i} замовлень");
        $this->showWarnings($warn);

        $this->_neworders = array();
        $this->neworderslist->Reload();
    }

    public function onCheck($sender) {

        System::getSession()->ocsiteid = $this->_siteid;
        Helper::connect($this->_siteid);
        \App\Application::Redirect("\\App\\Modules\\OCStore\\Orders");
    }

    public function onRefresh($sender) {

        $this->_eorders = Document::find("meta_name='Order' and content like '%<ocorderback>0</ocorderback>%' and " . Helper::docWhere($this->_siteid) . " and state <> " . Document::STATE_NEW);
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

        $st= $this->updateform->estatus->getValue();
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
        //статусы  уходят  на  тот  сайт, с  которого  заказы
        if (Helper::sendStatuses($this->_siteid, $elist) == false) {
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


        $this->_eorders = Document::find("meta_name='Order' and content like '%<ocorderback>0</ocorderback>%' and " . Helper::docWhere($this->_siteid) . " and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }

}
