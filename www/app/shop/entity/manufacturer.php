<?php

namespace App\Shop\Entity;

/**
 * класс-сущность  атрибута производитель или бренд
 * @table=shop_manufacturers
 * @keyfield=manufacturer_id
 */
class Manufacturer extends \ZCL\DB\Entity
{

    protected function init() {
        $this->manufacturer_id = 0;
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

}
