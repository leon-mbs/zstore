<?php

namespace App\Entity;

/**
 * Клас-сущность  баланс  расчетов с контрагентами
 *
 * @table=custacc
 * @view=custacc_view
 * @keyfield=ca_id
 */
class CustAcc extends \ZCL\DB\Entity
{
 
    public const  BONUS = 1; // бонусы
 

    protected function init() {
        $this->ca_id = 0;
        $this->createdon = time();
    }

    protected function afterLoad() {
        $this->createdon = strtotime($this->createdon);
    }
 
     
    
}
