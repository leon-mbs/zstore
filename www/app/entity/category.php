<?php

namespace App\Entity;

/**
 * Класс-сущность  категория товара
 *
 * @table=item_cat
 * @keyfield=cat_id
 */
class Category extends \ZCL\DB\Entity {

    protected function init() {
        $this->cat_id = 0;
    }
    
    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  items where   cat_id = {$this->cat_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Категория используется в  товарах" : ""; 
    }
}
