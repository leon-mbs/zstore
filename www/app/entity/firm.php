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

        $this->address = (string)($xml->address[0]);
        $this->inn   = (string)($xml->inn[0]);
        $this->phone = (string)($xml->phone[0]);
        $this->shopname = (string)($xml->shopname[0]);

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();

        $this->details =  "<details>";
        $this->details .= "<address><![CDATA[{$this->address}]]></address>";
        $this->details .= "<shopname><![CDATA[{$this->shopname}]]></shopname>";
        $this->details .= "<inn>{$this->inn}</inn>";
        $this->details .= "<phone>{$this->phone}</phone>";
        $this->details .= "</details>";

        return true;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = " select count(*) from contracts where firm_id = {$this->firm_id} ";
        $cntc = $conn->GetOne($sql);
        $sql = " select count(*) from documents where content like '%<firm_id>{$this->firm_id}</firm_id>%'   ";
        $cntd = $conn->GetOne($sql);
        return ($cntc > 0 || $cntd>0 ) ? \App\Helper::l('nodelfirm') : "";
    }

    public static function getList() {
        return Firm::findArray("firm_name", "disabled <> 1", "firm_name");
    }    
    
}
