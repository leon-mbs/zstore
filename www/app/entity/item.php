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
      



        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<pricelist>{$this->pricelist}</pricelist>";
        $this->detail .= "<term>{$this->term}</term>";
    
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

        return $this->checkDelete();
    }

    public function checkDelete() {

        $conn = \ZDB\DB::getConnect();
        
        $sql = "  select count(*)  from  store_stock where   item_id = {$this->item_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? false : true;
    }

    //Вычисляет  отпускную цену
    //$_price - цифра (заданая цена) или  наименование  цены из настроек 
    //$partionprice - учетная цена
    public function getPrice($_price_, $partionprice = 0) {
        $_price = 0;
        $common = \App\System::getOptions("common");
        if ($_price_ > 0) {
            
        } else {
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
        }
        if (strlen($_price) == 0)
            return 0;

        $price = 0;
        if ($partionprice > 0) {
            if (strpos($_price, '%') > 0) {
                $ret = doubleval(str_replace('%', '', $_price));
                $price = $partionprice + (int) $partionprice / 100 * $ret;
            } else {
                $price = $_price;
            }
            /* } else
              if ($this->lastpart > 0) {
              if (strpos($_price, '%') > 0) {
              $ret = doubleval(str_replace('%', '', $_price));
              $price = $this->lastpart + (int) $this->lastpart / 100 * $ret;
              } else {
              $price = $_price;
              } */
        } else {
            if (strpos($_price, '%') > 0) {

                return 0;
            } else {
                $price = $_price;
            }
        }


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
     */
    public static function getQuantity($item_id, $store_id = 0) {
        if ($item_id > 0) {
            $conn = \ZDB\DB::getConnect();
            $sql = "  select coalesce(sum(qty-wqty+rqty),0) as qty  from  store_stock_view where   item_id = {$item_id} ";
            if ($store_id > 0)
                $sql .= " and store_id = " . $store_id;
            $cnt = $conn->GetOne($sql);
            return $cnt;
        }
        return 0;
    }

}
