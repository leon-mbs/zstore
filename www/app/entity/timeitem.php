<?php

namespace App\Entity;

use \App\Helper as H;

/**
 * Класс-сущность  запись  о  рабочем  времени
 *
 * @table=timesheet
 * @view=timesheet_view
 * @keyfield=time_id
 */
class TimeItem extends \ZCL\DB\Entity
{

    const TIME_WORK = 1;   //Рабочий день
    const TINE_OVER = 2;   //переработка
    const TINE_WN   = 3;   //работа в  выходные  и ночь
    // const TINE_FREE   = 4;   //отгул
    const TINE_HL    = 5;   //отпуск
    const TINE_ILL   = 6;   //больничный
    const TINE_BT    = 7;   //командировка
    const TINE_OTHER = 10;   //другое

    protected function init() {
        $this->time_id = 0;
        $this->emp_id = 0;
        $this->t_type = 0;
    }

    protected function afterLoad() {
        $this->t_start = strtotime($this->t_start);
        $this->t_end = strtotime($this->t_end);
    }

    public static function getTypeTime() {
        $list = array();
        $list[self::TIME_WORK] = H::l('ts_worktime');
        $list[self::TINE_OVER] = H::l('ts_overtime');
        $list[self::TINE_WN] = H::l('ts_wntime');
        // $list[self::TINE_FREE] = H::l('Отгул');
        $list[self::TINE_HL] = H::l('ts_hol');
        $list[self::TINE_ILL] = H::l('ts_ill');
        $list[self::TINE_BT] = H::l('ts_bt');
        $list[self::TINE_OTHER] = H::l('ts_other');

        return $list;
    }

    public function isValid() {

        if (($this->t_end - $this->t_start) < 300) {
            return "ts_invalidinterval";
        }
        if (($this->t_end - $this->t_start) > (24 * 3600 - 300)) {
            return "ts_invalidinterval";
        }
        if (($this->t_end - $this->t_start) <= $this->t_break) {
            return "ts_invalidinterval";
        }
        $conn = \ZDB\DB::getConnect();
        $t_start = $conn->DBTimeStamp($this->t_start);
        $t_end = $conn->DBTimeStamp($this->t_end);
        $sql = " select  count(*) from timesheet where  time_id <> {$this->time_id}  and   emp_id={$this->emp_id}  ";
        $cnt = $conn->GetOne($sql);
        $sql = " select  count(*)  from timesheet where time_id <> {$this->time_id}  and  emp_id={$this->emp_id}  and   (( {$t_start}  >= t_end  and  {$t_end}  > t_end) or (  {$t_start}  < t_start  and  {$t_end}  <= t_start))";
        $cnt1 = $conn->GetOne($sql);

        if ($cnt > $cnt1) {
            return "ts_intersect";
        }

        return "";
    }

}
