<?php

namespace App\Entity;

/**
 * Клас-сущность  владелец фирмы
 *
 * @table=companies
 * @keyfield=company_id
 */
class Company extends \ZCL\DB\Entity {

    protected function init() {
        $this->company_id = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<details>";

        $this->details .= "<inn>{$this->inn}</inn>";
        $this->details .= "<mfo>{$this->mfo}</mfo>";
        $this->details .= "<bankaccount>{$this->bankaccount}</bankaccount>";


        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);


        $this->inn = (string) ($xml->inn[0]);
        $this->mfo = (string) ($xml->mfo[0]);
        $this->bankaccount = (string) ($xml->bankaccount[0]);


        parent::afterLoad();
    }

    public function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  firms where   company_id = {$this->company_id}";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0)
            return "Используется  в  фирмах";

        return "";
    }

    public static function getList() {
        return Company::findArray("company_name", "", "company_name");
    }

}
