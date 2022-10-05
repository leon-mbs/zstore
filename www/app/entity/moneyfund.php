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


    protected function init() {
        $this->mf_id = 0;
        $this->branch_id = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail>";
        $this->detail .= "<beznal>{$this->beznal}</beznal>";
        $this->detail .= "<btran>{$this->btran}</btran>";
        $this->detail .= "<btranin>{$this->btranin}</btranin>";
        $this->detail .= "<bank><![CDATA[{$this->bank}]]></bank>";
        $this->detail .= "<bankacc><![CDATA[{$this->bankacc}]]></bankacc>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        if (strlen($this->detail) == 0) {
            return;
        }

        $xml = simplexml_load_string($this->detail);
        $this->beznal = intval($xml->beznal[0]);
        $this->btran = floatval($xml->btran[0]);
        $this->btranin = floatval($xml->btranin[0]);
        $this->bank = (string)($xml->bank[0]);
        $this->bankacc = (string)($xml->bankacc[0]);

        parent::afterLoad();
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();

        $cnt = $conn->GetOne("select count(*) from paylist_view where mf_id = {$this->mf_id} ");
        if ($cnt > 0) {
            return \App\Helper::l("nodelmf");
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
        $rc = $conn->Execute("select coalesce(sum(amount),0)  as amount,mf_id from paylist_view where  paytype <=1000  group by  mf_id ");
        foreach ($rc as $row) {
            $list[$row["mf_id"]] = $row["amount"];
        }
        return $list;
    }

    /**
     * список счетов для комбо
     *
     *
     * @param mixed $nal 0 - все, 1- нол,2- безнал
     */
    public static function getList($nal = 0) {
        $ml = array();

        foreach (MoneyFund::find("") as $k => $v) {
            if ($nal == 1 && $v->beznal == 1) {
                continue;
            }
            if ($nal == 2 && $v->beznal != 1) {
                continue;
            }
            $ml[$k] = $v->mf_name;
        }

        return $ml;
    }

    public static function getConstraint() {
        return \App\ACL::getBranchConstraint();
    }

}
