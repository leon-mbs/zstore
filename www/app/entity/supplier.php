<?php

namespace App\Entity;

/**
 * Класс-сущность  поставщик
 *
 * @table=suppliers
 * @keyfield=sup_id
 */
class Supplier extends \ZCL\DB\Entity
{

 
    protected function init() {
        $this->sup_id = 0;
 
    }

     protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
  
        $this->detail .= "<site>{$this->site}</site>";
 
        $this->detail .= "<contact><![CDATA[{$this->contact}]]></contact>";
        $this->detail .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
 
        $this->site = (string)($xml->site[0]);
        $this->contact = (string)($xml->contact[0]);
        $this->comment = (string)($xml->comment[0]);

    
        parent::afterLoad();
    }

    public function beforeDelete() {

        $conn = \ZDB\DB::getConnect();

        $sql = "  select count(*)  from  supitems where   sup_id = {$this->sup_id}  ";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            return  \App\Helper::l("supisuseditem");  
        }
        return "";
    }

 
    
}
