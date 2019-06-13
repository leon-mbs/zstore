<?php

namespace App\Entity;

/**
 * Класс-сущность  производственный участок
 *
 * @table=parealist
 * @keyfield=pa_id
 */
class ProdArea extends \ZCL\DB\Entity {

    protected function init() {
        $this->pa_id = 0;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        //   $sql = "  select count(*)  from  store_stock where   store_id = {$this->store_id}";
         //  $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Нельзя удалять используемый склад" : true;
    }
    
    
}
