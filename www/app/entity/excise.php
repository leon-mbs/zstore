<?php
 

namespace App\Entity;

/**
 * сущность для хранения движения по  ОС
 *
 * @table=excisestamps
 * @view=excisestamps_view
 * @keyfield=id
 */
class Excise extends \ZCL\DB\Entity
{ 

    public function __construct(   ) {
        parent::__construct();
        
    }

    protected function init() {
         
    } 
  
    protected function afterLoad() {
            $this->document_date = strtotime($this->document_date);
    }
    
    /**
    * проверка формата марки
    * 
    * @param mixed $stamp
    */
    public static function check($stamp) {
        $pattern = '/^[A-Z]{4}\d{6}$/'; 

        return (bool)   preg_match($pattern, $stamp)) ;
   }
       
}