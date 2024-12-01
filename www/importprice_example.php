<?php
/**
* пример  файла  импорта прайсов  поставщиков
* вызов  с браузера  <адрес сайта> /importprice.php
*/
 
require_once 'init.php';


 
 die;// убрать для  работы

 \App\System::checkIP()  ;  //проверка  IP (задается  на станице  администратора)
 
try{    

        $data=[];//загрузка  данныхчерез  файл или API поставщика
        
 
        $customer_id=; //взять с таблицы customers
        // млм найти по имени
        $c= \App\Entity\Customer::getFirst("customer_name='имя' ")  ;
        $customer_id  = $c->customer_id;
        
        
        $cnt = 0;
       
        foreach ($data as $row) {
               $citem = new \App\Entity\CustItem() ;
               $citem->customer_id = $customer_id;
               $citem->cust_name = // нименование  товара  у поставщика
               $citem->cust_code = // код (артикул)     у поставщика
               $citem->bar_code = // штрих код
               $citem->price = // цена  у поставшика 
               $citem->quantity = // количество  у поставшика
               $citem->comment = // примечание
               $citem->brand =$brand; // бренд
               $it =  $citem->findItem();  // поиск  соответствия  в  справочнике  ТМЦ (алгоритм задается  в  журнале товары у  поставщмка )
               if($it != null) {
                   $citem->item_id= $it->item_id; 
               } 
               
               $citem->save()  ;
               $cnt++;
        }
        echo "<br> Імпортовано {$cnt} ТМЦ";
        
} catch (Exception $e) {
    echo $e->getMessage();
    $logger->error($e);
}
 