<?php

namespace App\Entity;

use App\Helper as H;

/**
 * Класс-сущность  запись  о  рабочем  времени
 *
 * @table=timesheet
 * @view=timesheet_view
 * @keyfield=time_id
 */
class TimeItem extends \ZCL\DB\Entity
{
    public const TIME_WORK = 1;   //Рабочий день
    public const TINE_OVER = 2;   //переработка
    public const TINE_WN   = 3;   //работа в  выходные  и ночь
    // const TINE_FREE   = 4;   //отгул
    public const TINE_HL    = 5;   //отпуск
    public const TINE_ILL   = 6;   //больничный
    public const TINE_BT    = 7;   //командировка
    public const TINE_OTHER = 10;   //другое

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
        $list[self::TIME_WORK] = 'Робочий час';
        $list[self::TINE_OVER] = 'Переробка';
        $list[self::TINE_WN] = 'Вихідні, нічна зміна';
        // $list[self::TINE_FREE] = 'Отгул';
        $list[self::TINE_HL] = 'Відпустка';
        $list[self::TINE_ILL] = 'Лікарняний';
        $list[self::TINE_BT] = 'Відрядження';
        $list[self::TINE_OTHER] = 'Інше';

        return $list;
    }

    public function isValid() {

        if (($this->t_end - $this->t_start) < 300) {
            return "Невірний інтервал";
        }
        if (($this->t_end - $this->t_start) > (24 * 3600 - 300)) {
            return "Невірний інтервал";
        }
        if (($this->t_end - $this->t_start) <= $this->t_break) {
            return "Невірний інтервал";
        }
        $conn = \ZDB\DB::getConnect();
        $t_start = $conn->DBTimeStamp($this->t_start);
        $t_end = $conn->DBTimeStamp($this->t_end);
        $sql = " select  count(*) from timesheet where  time_id <> {$this->time_id}  and   emp_id={$this->emp_id}  ";
        $cnt = $conn->GetOne($sql);
        $sql = " select  count(*)  from timesheet where time_id <> {$this->time_id}  and  emp_id={$this->emp_id}  and   (( {$t_start}  >= t_end  and  {$t_end}  > t_end) or (  {$t_start}  < t_start  and  {$t_end}  <= t_start))";
        $cnt1 = $conn->GetOne($sql);

        if ($cnt > $cnt1) {
            return "Інтервал перетинається з існуючим";
        }

        return "";
    }

}
