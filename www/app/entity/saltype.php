<?php

namespace App\Entity;

/**
 * Класc-сущность   тип начислений удержаний
 *
 * @table=saltypes
 * @keyfield=st_id
 */
class SalType extends \ZCL\DB\Entity
{

    protected function init() {

        $this->st_id = 0;
        
    }

    

}
