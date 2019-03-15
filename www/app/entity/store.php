<?php

namespace App\Entity;

/**
 * Клас-сущность  склад
 *
 * @table=stores
 * @keyfield=store_id
 */
class Store extends \ZCL\DB\Entity
{

    protected function init() {

        $this->store_id = 0;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        // $sql = "  select count(*)  from  store_stock where   store_id = {$this->store_id}";
        //   $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? false : true;
    }

    /**
     * список складов для комбо
     * 
     */
    public static function getList() {
        return Store::findArray("storename", "");
    }

}
