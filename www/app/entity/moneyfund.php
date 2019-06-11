<?php

namespace App\Entity;

/**
 * Класс-сущность  денежный счет
 *
 * @table=mfund
 * @keyfield=mf_id
 */
class MoneyFund extends \ZCL\DB\Entity {

    protected function init() {
        $this->mf_id = 0;
    }
    
    protected function beforeDelete() {
  
        $conn = \ZDB\DB::getConnect();

        $cnt = $conn->GetOne("select count(*) from paylist where mf_id = {$this->mf_id} ");
        if ($cnt > 0) {
            return "Нельзя удалять счет с оплатами";
        }
        return "";
    }
    
    public static function Balance() {
  
        $conn = \ZDB\DB::getConnect();
        $list = array();
        $rc = $conn->Execute("select coalesce(sum(amount),0)  as amount,mf_id from paylist group by  mf_id ");
        foreach($rc as $row){
            $list[$row["mf_id"]] = $row["amount"] ;
        }
        return $list;
    }
    
    
    
}
