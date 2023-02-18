<?php
//синхронизация  с  опенкартом    

require_once 'init.php';

$status_name = "Ожидание";    //  статус  импортируемого  заказа

try {
        $conn = \ZDB\DB::getConnect();

        $logger->info("Синхронізація з опенкарт ");
    
        \App\Modules\OCStore\Helper::connect() ;
    
        $statuses =  \App\System::getSession()->statuses;
        if(is_array($statuses)== false  || count($statuses)==0) {
            
            $logger->error("Проблеми зі з'єднанням");
      
            return;
        }
        $status=0;
        foreach($statuses as  $k=>$v){
            if($v==$status_name) $status = $k;
            
        }
    
        $modules = \App\System::getOptions("modules");
         
        $site = $modules['ocsite'];
        $apiname = $modules['ocapiname'];
        $key = $modules['ockey'];
        $site = trim($site, '/');

        $url = $site . '/index.php?route=api/login';

        $fields = array(
            'username' => $apiname,
            'key' => $key
        );
        
        $json = \App\Modules\OCStore\Helper::do_curl_request($url, $fields);
     
    
        if ($json === false)
            return;
        $data = json_decode($json, true);
        if ($data==null) {
            $logger->error($json);
            return;
        }
       if (is_array($data) && count($data) == 0) {
            $logger->error('Немає даних відповіді');
            return;
        }

        if (is_array($data['error'])) {
            $logger->error(implode(' ', $data['error']));
            return;
        } else
        if (strlen($data['error']) > 0) {
            $logger->error($data['error']);
            return;
        }

        if (strlen($data['success']) > 0) {

            if (strlen($data['api_token']) > 0) { //версия 3
                $token = "api_token=" . $data['api_token'];
            }
            if (strlen($data['token']) > 0) { //версия 2.3
                $token = "token=" . $data['token'];
            }
            if(strlen($token)==0) {
                echo  "Помилка. См. лог";
                return;
            }
            echo  "<br>З'єднання успішно";
            $logger->info("З'єднання успішно");
        }
        
            //список  артикулов
            $url = $site . '/index.php?route=api/zstore/articles&' . $token;
            $json = \App\Modules\OCStore\Helper::do_curl_request($url);
            if ($json === false)
                return;
            $data = json_decode($json, true);
            if (strlen($data['error']) > 0) {
                $logger->error($data['error']);
                return;
            }
            $articles = array();
            foreach($data['articles'] as $a){
               if(strlen($a) > 0) {
                   $articles[] = $a;        
               }
            }
            
        
        $qlist = array();
        $plist = array();
        $newitems = array();
        $items = \App\Entity\Item::find("disabled <> 1 ");
        foreach ($items as $item) {
            if (strlen($item->item_code) == 0)
                continue;


            $qlist[$item->item_code] = $item->getQuantity();
            $plist[$item->item_code] = $item->getPrice($modules['ocpricetype']);

  
                
         }
        unset($items);
        
        //обновление  количеств 
        if(true){
            $data = json_encode($qlist);

            $fields = array(
                'data' => $data
            );
            $url = $site. '/index.php?route=api/zstore/updatequantity&' . $token;
            $json = \App\Modules\OCStore\Helper::do_curl_request($url, $fields);
            if ($json === false)
                return;
            $data = json_decode($json, true);

            if ($data['error'] != "") {
                $logger->error($data['error']);
                return;
            }
            
            echo  "<br>Оновлена кількість";
            $logger->info("Оновлена кількість ");
        }
        
        //обновление  цен
        if(true){
            $data = json_encode($plist);

            $fields = array(
                'data' => $data
            );
            $url = $site. '/index.php?route=api/zstore/updateprice&' . $token;
            $json = \App\Modules\OCStore\Helper::do_curl_request($url, $fields);
            if ($json === false)
                return;
            $data = json_decode($json, true);

            if ($data['error'] != "") {
                $logger->error($data['error']);
                return;
            }
            

            $logger->info("Оновлені ціни");
             echo  "<br>Оновлені ціни";
         }
  
        //импорт товаров
        if(true){

            $url = $site . '/index.php?route=api/zstore/getproducts&' . $token;
            $json = \App\Modules\OCStore\Helper::do_curl_request($url, $fields);
            if ($json === false)
                return;
            $data = json_decode($json, true);
       
          
            if ($data['error'] != "") {
                $logger->error($data['error']);
                return;
            }
         
             foreach ($data['products'] as $product) {

                if (strlen($product['sku']) == 0) continue;
      
                $item = \App\Entity\Item::getFirst("item_code=" . \App\Entity\Item::qstr($product['sku']));
                if($item instanceof \App\Entity\Item)  {
                   $item->itemname = str_replace('&quot;', '"', $product['name']);    
                   $item->save();
                } else {
                    $item = new  \App\Entity\Item();
                    $item->itemname = str_replace('&quot;', '"', $product['name']);
                    $item->item_code = $product['sku'];
                    $item->bar_code = $product['sku'];
                    $item->save();
                  
                }

                
             }
        }
        
        
        //импорт заказов
     
       if(true){
     
            $fields = array(
                'status_id' => $status
            );
              
            $url = $site . '/index.php?route=api/zstore/orders&' . $token;
            $json = \App\Modules\OCStore\Helper::do_curl_request($url, $fields);
            if ($json === false)
                return;
            $data = json_decode($json, true);
       
          
            if ($data['error'] != "") {
                $logger->error($data['error']);
                return;
            }

               $neworders = array();
            
               foreach ($data['orders'] as $ocorder) {


                  $cnt  = $conn->getOne("select count(*) from documents_view where (meta_name='Order' or meta_name='TTN') and content like '%<ocorder>{$ocorder['order_id']}</ocorder>%'")  ;

                    if ( intval($cnt) > 0) { //уже импортирован
                         continue;
                    }
     

                    $order = new \App\DataItem($ocorder);
                          
                    $neworders[$ocorder['order_id']] = $order;
                }  
          
            $i = 0;
            foreach ($neworders as $shoporder) {


                $neworder = \App\Entity\Doc\Document::create('Order');
                $neworder->document_number = $neworder->nextNumber();
                $neworder->document_date = strtotime($shoporder->date_added);

                if (strlen($neworder->document_number) == 0) {
                    $neworder->document_number = 'OC00001';
                }
                $neworder->customer_id = $modules['occustomer_id'];
                $j=0;           //товары
                $tlist = array();
                foreach ($shoporder->_products_ as $product) {
                    //ищем по артикулу 
                    if (strlen($product['sku']) == 0) {
                        continue;
                    }
                    $code = \App\Entity\Item::qstr($product['sku']);

                    $tovar = \App\Entity\Item::getFirst('item_code=' . $code);
                    if ($tovar == null) {

                       
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

                    $tlist[$j] = $tovar;
                }
                if(count($tlist)==0)  {
                    return;
                }
                $neworder->packDetails('detaildata', $tlist);
                $neworder->amount = \App\Helper::fa($shoporder->total);
                
                if($modules['ocsetpayamount']==1){
                   $neworder->payamount = $neworder->amount;
                 
                }
                
                $neworder->headerdata['salesource'] = \App\Helper::getDefSaleSource();
                $neworder->headerdata['outnumber'] = $shoporder->order_id;
                $neworder->headerdata['ocorder'] = $shoporder->order_id;
                $neworder->headerdata['ocorderback'] = 0;
                $neworder->headerdata['pricetype'] = 'price1';
            
                $neworder->notes = "OC номер:{$shoporder->order_id};";

                $neworder->headerdata['occlient'] = $shoporder->firstname . ' ' . $shoporder->lastname;
                $neworder->notes .= " Клієнт:" . $shoporder->firstname . ' ' . $shoporder->lastname . ";";

                if ($shoporder->customer_id > 0 && $modules['ocinsertcust'] == 1) {
                    $cust = \App\Entity\Customer::getFirst("detail like '%<shopcust_id>{$shoporder->customer_id}</shopcust_id>%'");
                    if ($cust == null) {
                        $cust = new \App\Entity\Customer();
                        $cust->shopcust_id = $shoporder->customer_id;
                        $cust->customer_name = $shoporder->firstname . ' ' . $shoporder->lastname;
                        $cust->address = $shoporder->shipping_city . ' ' . $shoporder->shipping_address_1;
                        $cust->type = \App\Entity\Customer::TYPE_BAYER;
                        $cust->phone = \App\Util::handlePhone($shoporder->telephone);
                        $cust->email = $shoporder->email;
                        $cust->comment = "Клієнт ІМ";
                        $cust->save();
                    }
                    $neworder->customer_id = $cust->customer_id;
                }


                if (strlen($shoporder->email) > 0) {
                    $neworder->notes .= " Email:" . $shoporder->email . ";";
                }
                if (strlen($shoporder->telephone) > 0) {
                    $neworder->notes .= " Тел:" . $shoporder->telephone . ";";
                }
                $neworder->notes .= " Адреса:" . $shoporder->shipping_city . ' ' . $shoporder->shipping_address_1 . ";";
                $neworder->notes .= " Оплата:" . $shoporder->payment_method . ";";
                $neworder->notes .= " Комментар:" . $shoporder->comment . ";";
                $neworder->save();
                $neworder->updateStatus(\App\Entity\Doc\Document::STATE_NEW);
                $neworder->updateStatus(\App\Entity\Doc\Document::STATE_INPROCESS);

                $i++;
            }
           
            $logger->info("Імпортовано {$i} замовлень ");
            echo  "<br>Імпортовано {$i} замовлень ";
       }     
      
       die;

} catch (Exception $e) {
    echo $e->getMessage();
    $logger->error($e);
}
