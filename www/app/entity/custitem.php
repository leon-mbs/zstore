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
    
    }

 

    protected function afterLoad() {
        
        $this->updatedon = strtotime($this->updatedon);
         
        
        //распаковываем  данные из item detail
        $xml = simplexml_load_string($this->detail);
        $this->itemprice = (string)($xml->price1[0]);

   
        parent::afterLoad();
    }

 
   
}
