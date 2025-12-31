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
    public static function checkFormat($stamp) {
        $pattern = '/^[A-Z]{4}\d{6}$/'; 

        return (bool)   preg_match($pattern, $stamp)  ;
    }
   
    public static function checkUsed($stamp) {
        $st=Excise::getFirst("stamp=". Excise::qstr($stamp) ) ;

        return $st;
    }
   
     public static function insert(string $st ,int $item_id,int $document_id) {
        if(strlen(trim($st))==0) return;
        
        $stamp = new Excise();
        $stamp->stamp =  $st  ;
        $stamp->item_id = $item_id;
        $stamp->document_id = $document_id;
        $stamp->save();

    
    }      
}