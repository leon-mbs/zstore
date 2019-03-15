<?php

namespace App\Entity;

/**
 * Клас-сущность  категория товара
 *
 * @table=services
 * @keyfield=service_id
 */
class Service extends \ZCL\DB\Entity
{

    protected function init() {
        $this->service_id = 0;
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->hours = (string) ($xml->hours[0]);
        $this->price = (string) ($xml->price[0]);



        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<price>{$this->price}</price>";
        $this->detail .= "<hours>{$this->hours}</hours>";

        $this->detail .= "</detail>";

        return true;
    }

}
