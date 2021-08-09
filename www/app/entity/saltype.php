<?php

namespace App\Entity;

/**
 * Класc-сущность   тип начислений удержаний
 *
 * @table=saltypes
 * @keyfield=salcode
 */
class SalType extends \ZCL\DB\Entity
{

    protected function init() {

        $this->salcode = 0;
        
    }

    
    public  static function getList(){
        return  self::findArray("concat(salcode,' ',salname)","disabled<>1","salcode")  ;
    }
}
