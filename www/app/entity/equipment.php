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
        $this->detail .= "<pa_name>{$this->pa_name}</pa_name>";
        $this->detail .= "<serial>{$this->serial}</serial>";
        $this->detail .= "<code>{$this->code}</code>";
        $this->detail .= "<balance>{$this->balance}</balance>";
        $this->detail .= "<eq>{$this->eq}</eq>";
        $this->detail .= "<pa_id>{$this->pa_id}</pa_id>";
        $this->detail .= "<enterdate>{$this->enterdate}</enterdate>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        $this->emp_id = (int)($xml->emp_id[0]);
        $this->emp_name = (string)($xml->emp_name[0]);
        $this->pa_name = (string)($xml->pa_name[0]);
        $this->serial = (string)($xml->serial[0]);
        $this->code = (string)($xml->code[0]);
        $this->balance = (string)($xml->balance[0]);
        $this->enterdate = (int)($xml->enterdate[0]);
        $this->eq = (int)($xml->eq[0]);
        $this->pa_id = (int)($xml->pa_id[0]);

        parent::afterLoad();
    }

    //возвращает  оборудование для выпадающих списков
    public static function getQuipment() {
        $list = array();
        foreach (Equipment::find("disabled<>1 and detail like'%<eq>1</eq>%' ", "eq_name") as $eq) {
            $list[$eq->eq_id] = $eq->eq_name;
            if (strlen($eq->serial) > 0) {
                $list[$eq->eq_id] = $eq->eq_name . ', ' . $eq->serial;
            }


        }
        return $list;
    }

}
