<?php

namespace App\Entity;

/**
 * Клас-сущность  ТМЦ
 *
 * @table=items
 * @view=items_view
 * @keyfield=item_id
 */
class Item extends \ZCL\DB\Entity
{

    protected function init() {
        $this->item_id = 0;
        $this->cat_id = 0;
        $this->curname = '';
        $this->currate = 0;
        $this->price = 0;
        $this->lastpart = 0; //последняя партия
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->price = (string) ($xml->price[0]);
        $this->curname = (string) ($xml->curname[0]);
        $this->currate = doubleval($xml->currate[0]);



        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<price>{$this->price}</price>";
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

    //Возвращает розничную цену
    //$partionprice - учетная цена
    public function getPrice($partionprice = 0) {
        $price = 0;
        if ($partionprice > 0) {
            if (strpos($this->price, '%') > 0) {
                $ret = doubleval(str_replace('%', '', $this->price));
                $price = $partionprice + (int) $partionprice / 100 * $ret;
            } else {
                $price = $this->price;
            }
        } else
        if ($this->lastpart > 0) {
            if (strpos($this->price, '%') > 0) {
                $ret = doubleval(str_replace('%', '', $this->price));
                $price = $this->lastpart + (int) $this->lastpart / 100 * $ret;
            } else {
                $price = $this->price;
            }
        }

        $common = \App\System::getOptions("common");
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

}
