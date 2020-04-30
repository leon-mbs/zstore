<?php

namespace App\Entity;

/**
 * Класс-сущность  категория товара
 *
 * @table=item_cat
 * @keyfield=cat_id
 */
class Category extends \ZCL\DB\Entity
{

    protected function init() {
        $this->cat_id = 0;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  items where   cat_id = {$this->cat_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Категория используется в  товарах" : "";
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->price1 = (string)($xml->price1[0]);
        $this->price2 = (string)($xml->price2[0]);
        $this->price3 = (string)($xml->price3[0]);
        $this->price4 = (string)($xml->price4[0]);
        $this->price5 = (string)($xml->price5[0]);


        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();

        $this->detail = "<detail>";

        $this->detail .= "<price1>{$this->price1}</price1>";
        $this->detail .= "<price2>{$this->price2}</price2>";
        $this->detail .= "<price3>{$this->price3}</price3>";
        $this->detail .= "<price4>{$this->price4}</price4>";
        $this->detail .= "<price5>{$this->price5}</price5>";


        $this->detail .= "</detail>";

        return true;
    }

}
