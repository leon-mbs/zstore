<?php

namespace App\Entity;

/**
 * Класс-сущность  компания 
 *
 * @table=firms
 * @keyfield=firm_id
 */
class Firm extends \ZCL\DB\Entity
{

    protected function init() {
        $this->firm_id = 0;
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->details);

        $this->hours = (string)($xml->hours[0]);
   

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->details = "<details>";
       
       

        $this->details .= "</details>";

        return true;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  entrylist where   service_id = {$this->service_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? \App\Helper::l('nodelservice') : "";
    }

}
