<?php

namespace App\Entity;

/**
 * Клас-сущность  лицевой счет  сотрудника
 *
 * @table=empacc
 * @view=empacc_view
 * @keyfield=ea_id
 */
class EmpAcc extends \ZCL\DB\Entity
{
    //реальные движения  по  счету изменяют  баланс
    const  INCOME_FROM_MF = 1; // перечисление  со  счета
    const  OUTCOME_TO_MF  = 2; //  перечисление на  счет
    const  SALARY         = 3; //  начисления  зарплаты
    const  SALARY_PAY     = 4; //  выплата  зарплаты
    const  ADVANCE_ACC    = 5; // авансовый отчет

    //движения  для  начислений и удержаний в  балансе  не  отражаются
    const  ADVANCE = 101; // аванс
    const  BONUS   = 102; // бонусы
    const  FINE    = 103; // штрафы


    protected function init() {

        $this->ea_id = 0;
        $this->createdon = time();

    }

    protected function afterLoad() {
        $this->createdon = strtotime($this->createdon);
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
        $sql = "select coalesce(sum(amount),0) as am,emp_id from  empacc_view  where  optype = {$t} and createdon >=" . $conn->DBDate($from) . " and createdon <=" . $conn->DBDate($to) . "  group by  emp_id   ";

        return $conn->Execute($sql);
    }

}
