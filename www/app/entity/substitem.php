<?php

namespace App\Entity;

/**
 * Класс-сущность замена товара
 *
 * @table=substitems
 * @keyfield=id
 */
class SubstItem extends \ZCL\DB\Entity
{
    protected function init() {
        $this->id = 0;
    }
 

    
 

    //поиск  в  номенклатуре
    public function findItem() {
        $item=null;
        $op= intval(\App\System::getOption('common','ci_compare') );
        if($op==0 && strlen($this->cust_code ??'') > 0 && strlen($this->brand ??'') > 0) {
            $item = Item::getFirst("item_code='{$this->cust_code}' and manufacturer='{$this->brand}'") ;    
        }
        if($op==1 && strlen($this->bar_code ??'') > 0  ) {
            $item = Item::getFirst("bar_code='{$this->bar_code}'  ") ;    
        }
        if($op==2 && strlen($this->bar_code ??'') > 0  ) {
            $item = Item::getFirst("bar_code='{$this->bar_code}'  ") ;    
            if($item == null && strlen($this->cust_code ??'') > 0 && strlen($this->brand ??'') > 0  ) {
                $item = Item::getFirst("item_code='{$this->cust_code}' and manufacturer='{$this->brand}'") ;    
            }
        }
        
      
         return $item;
    }
    
}
