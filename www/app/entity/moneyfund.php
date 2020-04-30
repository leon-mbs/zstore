<?php

namespace App\Entity;

/**
 * Класс-сущность  денежный счет
 *
 * @table=mfund
 * @keyfield=mf_id
 */
class MoneyFund extends \ZCL\DB\Entity
{

    //  const BEZNAL = 10000;
    const PREPAID = 10001;
    const CREDIT  = 10002;

    protected function init() {
        $this->mf_id = 0;
        $this->branch_id = 0;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();

        $cnt = $conn->GetOne("select count(*) from paylist where mf_id = {$this->mf_id} ");
        if ($cnt > 0) {
            return "Нельзя удалять счет с оплатами";
        }
        return "";
    }

    /**
     * возвращает баланс на  денежных счетах
     *
     */
    public static function Balance() {

        $conn = \ZDB\DB::getConnect();
        $list = array();
        $rc = $conn->Execute("select coalesce(sum(amount),0)  as amount,mf_id from paylist group by  mf_id ");
        foreach ($rc as $row) {
            $list[$row["mf_id"]] = $row["amount"];
        }
        return $list;
    }

    /**
     * список счетов для комбо
     *
     * @param mixed $beznal добавить пункт  Безналичный расчет
     * @param mixed $prepaid добавить пункт  Была предоплата
     */
    public static function getList($credit = false, $prepaid = false) {
        $ml = array();
        if ($credit) {
            $ml[self::CREDIT] = \App\Helper::l("credit");
        }
        //     if ($beznal)
        //      $ml[self::BEZNAL] = 'Безналичный расчет';
        if ($prepaid) {
            $ml[self::PREPAID] = \App\Helper::l("prepaid");
        }
        foreach (MoneyFund::findArray("mf_name", "") as $k => $v) {
            $ml[$k] = $v;
        }

        return $ml;
    }

    public static function getConstraint() {
        return \App\ACL::getBranchConstraint(true);
    }

}
