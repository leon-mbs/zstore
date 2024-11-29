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

}
