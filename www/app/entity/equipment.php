<?php

namespace App\Entity;

/**
 * Клас-сущность  оборудование
 *
 * @table=equipments
 * @keyfield=eq_id
 */
class Equipment extends \ZCL\DB\Entity
{

    protected function init() {
        $this->eq_id = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail><emp_id>{$this->emp_id}</emp_id>";
        $this->detail .= "<emp_name>{$this->emp_name}</emp_name>";
        $this->detail .= "<serial>{$this->serial}</serial>";
        $this->detail .= "<code>{$this->code}</code>";


        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        $this->emp_id = (int)($xml->emp_id[0]);
        $this->emp_name = (string)($xml->emp_name[0]);
        $this->serial = (string)($xml->serial[0]);
        $this->code = (string)($xml->code[0]);


        parent::afterLoad();
    }

}
