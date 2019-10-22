<?php

namespace App\Entity;

/**
 * Клас-сущность  ТМЦ
 *
 * @table=items
 * @view=items_view
 * @keyfield=item_id
 */
class Item extends \ZCL\DB\Entity {

    protected function init() {
        $this->item_id = 0;
        $this->cat_id = 0;
        $this->curname = '';
        $this->currate = 0;
        $this->price = 0;
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->price1 = (string) ($xml->price1[0]);
        $this->price2 = (string) ($xml->price2[0]);
        $this->price3 = (string) ($xml->price3[0]);
        $this->price4 = (string) ($xml->price4[0]);
        $this->price5 = (string) ($xml->price5[0]);
        $this->curname = (string) ($xml->curname[0]);
        $this->currate = doubleval($xml->currate[0]);
        $this->pricelist = (int) $xml->pricelist[0];
        $this->term = (int) $xml->term[0];
        $this->cell = (string) $xml->cell[0];




        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<pricelist>{$this->pricelist}</pricelist>";
        $this->detail .= "<term>{$this->term}</term>";
        $this->detail .= "<cell>{$this->cell}</cell>";

        $this->detail .= "<price1>{$this->price1}</price1>";
        $this->detail .= "<price2>{$this->price2}</price2>";
        $this->detail .= "<price3>{$this->price3}</price3>";
        $this->detail .= "<price4>{$this->price4}</price4>";
        $this->detail .= "<price5>{$this->price5}</price5>";
        $this->detail .= "<curname>{$this->curname}</curname>";
        $this->detail .= "<currate>{$this->currate}</currate>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        //проверка на партии
        $sql = "  select count(*)  from  store_stock where   item_id = {$this->item_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "ТМЦ уже  используется" : "";
    }

    //Вычисляет  отпускную цену
    //$_price - цифра (заданая цена) или  наименование  цены из настроек 
    //$store - склад
    //$partion - партия
    public function getPrice($_price_, $store = 0,$partion=0) {
        $price = 0;
        $_price = 0;
        $common = \App\System::getOptions("common");
 
            if ($_price_ == 'price1')
                $_price = $this->price1;
            else if ($_price_ == 'price2')
                $_price = $this->price2;
            else if ($_price_ == 'price3')
                $_price = $this->price3;
            else if ($_price_ == 'price4')
                $_price = $this->price4;
            else if ($_price_ == 'price5')
                $_price = $this->price5;
      
          
        //если процент    
        if (strpos($_price, '%') > 0) {
                  
             $ret = doubleval(str_replace('%', '', $_price));
             if($ret>0){
                
                 if($partion>0){
                     
                 }
                 else {  //ищем последнюю закупочную  цену 
                    $conn = \ZDB\DB::getConnect();
                    
                    $sql = "  select coalesce(partion,0)  from  store_stock where   item_id = {$this->item_id}";
                    if($store>0){
                       $sql = $sql ." and store_id=" . $store;    
                    }   
                    $sql = $sql ." order  by  stock_id desc limit 0,1";  
                    $partion = $conn->GetOne($sql);
                 }
                
                 
                 $price = $partion + (int) $partion / 100 * $ret;   
             }
             
             
                  
         } else if($_price > 0){
             $price = $_price; //задана  просто  цифра
         }
          
            
        //поправка  по  валюте

        if ($common['useval'] == true) {
            $k = 1;
            if ($common['cdoll'] > 0 && $this->currate > 0 && $this->curname == 'cdoll') {
                $k = $common['cdoll'] / $this->currate;
            }
            if ($common['ceuro'] > 0 && $this->currate > 0 && $this->curname == 'ceuro') {
                $k = $common['ceuro'] / $this->currate;
            }
            if ($common['crub'] > 0 && $this->currate > 0 && $this->curname == 'crub') {
                $k = $common['crub'] / $this->currate;
            }

            $price = $price * $k;
        }

        return round($price);
    }

    public static function getPriceTypeList() {

        $common = \App\System::getOptions("common");
        $list = array();
        if (strlen($common['price1']) > 0)
            $list['price1'] = $common['price1'];
        if (strlen($common['price2']) > 0)
            $list['price2'] = $common['price2'];
        if (strlen($common['price3']) > 0)
            $list['price3'] = $common['price3'];
        if (strlen($common['price4']) > 0)
            $list['price4'] = $common['price4'];
        if (strlen($common['price5']) > 0)
            $list['price5'] = $common['price5'];

        return $list;
    }

    /**
     * возвращает количество на складах
     * 
     * @param mixed $item_id
     * @param mixed $store_id
     * @param mixed $snumber   партия проиводителя
     */
    public function   getQuantity(   $store_id = 0,$snumber="") {
        
            $conn = \ZDB\DB::getConnect();
            $sql = "  select coalesce(sum(qty),0) as qty  from  store_stock_view where   item_id = {$this->item_id} ";
            if ($store_id > 0)
                $sql .= " and store_id = " . $store_id;
           if (strlen($snumber) > 0)
                $sql .= " and  snumber = " . $conn->qstr($snumber);
            $cnt = $conn->GetOne($sql);
            return $cnt;
        
    }
    
    /**
     * возвращает сумму на складах
     * 
     * @param mixed $item_id
     * @param mixed $store_id
     */
    public   function getAmount(  $store_id = 0) {
       
            $conn = \ZDB\DB::getConnect();
            $sql = "  select coalesce(sum(qty*partion),0) as amount  from  store_stock_view where   item_id = {$this->item_id} ";
            if ($store_id > 0)
                $sql .= " and store_id = " . $store_id;
            $amount = $conn->GetOne($sql);
            return $amount;
       
    }
    
   
    /**
     * Метод  для   получения  имени  ТМЦ   для выпадающих списков
     *
     * @param mixed $criteria
     * @return []
     * @static
     */
    public static function findArrayAC( $partname,$store=0  ) {
       
        $criteria = "  disabled <> 1 ";
        if($store>0){
            $criteria .= "     and item_id in (select item_id from store_stock  where  store_id={$store})" ;
        }
        
        if (strlen($partname) > 0) {
            $partname = self::qstr('%' . $partname . '%');
            $criteria .= "  and  (itemname like {$partname} or item_code like {$partname}   or   bar_code like {$partname} )";
        }

        $entitylist = self::find($criteria);
 
        $list = array();
        foreach ($entitylist as $key => $value) {
         
            $list[$key] = $value->itemname  ;
            
        }

        return $list;
    }
   

    /**
    * генерирует новый артикул
    * 
    */
    public static function getNextArticle(){
        $conn = \ZDB\DB::getConnect();
        
        $sql = "  select max(item_id)  from  items ";
        $id = $conn->GetOne($sql);
       
        return "ID" . sprintf("%04d", ++$id);
    }
}
