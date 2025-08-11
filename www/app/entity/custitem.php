<?php

namespace App\Entity;

/**
 * Класс-сущность  товар  поставщика
 *
 * @table=custitems
 * @view=custitems_view
 * @keyfield=custitem_id
 */
class CustItem extends \ZCL\DB\Entity
{
    protected function init() {
        $this->custitem_id = 0;
        $this->item_id = 0;
        $this->updatedon = time();        
    }
 

    protected function afterLoad() {

        $this->updatedon = strtotime($this->updatedon);

        $d = @unserialize($this->details);
        if(!is_array($d)){
           $d=[]; 
        }
        $this->comment = $d['comment'];

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        
        $d = []    ;
        $d['comment'] = $this->comment;
   
        $this->details = serialize($d)  ;
        return true;
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
