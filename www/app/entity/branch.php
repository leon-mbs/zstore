<?php

namespace App\Entity;

/**
 * Клас-сущность  филиал
 *
 * @table=branches
 * @keyfield=branch_id
 */
class Branch extends \ZCL\DB\Entity {

    protected function init() {
        $this->branch_id = 0;
        $this->disabled = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<details>";
  
        $this->details .= "<address><![CDATA[{$this->address}]]></address>";
        $this->details .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->details .= "<phone><![CDATA[{$this->phone}]]></phone>";

        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);
   
        $this->address = (string) ($xml->address[0]);
        $this->comment = (string) ($xml->comment[0]);
        $this->phone = (string) ($xml->phone[0]);

        parent::afterLoad();
    }

    public function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  documents where   branch_id = {$this->branch_id}";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0)
            return "На  филиал есть  ссылки  в  документах"; 

        $sql = "  select count(*)  from  stores where   branch_id = {$this->branch_id}";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0)
            return "Филиал используется в складах"; 

        $sql = "  select count(*)  from  mfund where   branch_id = {$this->branch_id}";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0)
            return "Филиал используется в денежных счетах"; 
            
        return "";    
    }

    public static function getList() {
        return Branch::findArray("branch_name", "disabled<>1","branch_name");
    }
   
   
}
