<?php

namespace App\Entity;

/**
 * Клас-сущность  промокод
 *
 * @table=promocodes
 * @keyfield=id
 */
class PromoCode extends \ZCL\DB\Entity
{
    protected function init() {
        $this->id = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<detail>";
//        $this->detail .= "<emp_name>{$this->emp_name}</emp_name>";

        $this->details .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);
    //    $this->emp_id = (int)($xml->emp_id[0]);
   
        parent::afterLoad();
    }


    public static function findByCode($code) {
  
    }

}
