<?php

namespace App\Entity;

/**
 * Клас-сущность  лицевой счет  контрагента
 *
 * @table=custacc
 * @view=custacc_view
 * @keyfield=ca_id
 */
class CustAcc extends \ZCL\DB\Entity
{
 
    const  B_OUT_ITEMS = 1; //  товары  покупателю
    const  B_IN_PAY = 2; //  оплата от  покупателя
    const  B_IN_ITEMS_RET = 3; //  товары от покупателя (возврат)
    const  B_OUT_PAY_RET = 4; //  оплата покупателю (возврат)
    const  B_OUT_SER = 5; //  услуги
  
    const  S_IN_ITEMS = 51; //  товары от  поставщика
    const  B_OUT_PAY = 52; //  оплата поставщику
    const  B_OUT_ITEMS_RET = 53; //  товары поставщику (возврат)
    const  B_IN_PAY_RET = 54; //  оплата от  постащика (возврат)
    const  B_IN_SER = 55; //  услуги
  


    protected function init() {

        $this->ca_id = 0;

    }


    public static function getBalance() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(sum(amount),0) as am,emp_id from  empacc  where  optype < 100 group by  emp_id   ";

        return $conn->Execute($sql);
    }

    public static function getAmountByType($y, $m, $t) {

        $dt = new \App\DateTime(strtotime($y . '-' . $m . '-01'));

        $from = $dt->startOfMonth()->getTimestamp();
        $to = $dt->endOfMonth()->getTimestamp();


        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(sum(amount),0) as am,emp_id from  empacc_view  where  optype = {$t} and document_date >=" . $conn->DBDate($from) . " and document_date <=" . $conn->DBDate($to) . "  group by  emp_id   ";

        return $conn->Execute($sql);
    }

}
