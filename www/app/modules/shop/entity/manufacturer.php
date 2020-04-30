<?php

namespace App\Modules\Shop\Entity;

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

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  shop_products where   manufacturer_id = {$this->manufacturer_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Производитель используется в  товарах" : "";
    }

}
