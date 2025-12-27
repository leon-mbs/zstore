<?php
 

namespace App\Entity;

/**
 * сущность для хранения движения по  ОС
 *
 * @table=excisestamps
 * @keyfield=id
 */
class Excise extends \ZCL\DB\Entity
{ 

    public function __construct(   ) {
        parent::__construct();
        
    }

    protected function init() {
         
    } 
    /**
    * проверка формата марки
    * 
    * @param mixed $stamp
    */
    public static function check($stamp) {
        return true; 
    } 
       
}