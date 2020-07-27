<?php

namespace App\Entity;

use \App\Helper as  H;

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
    const TINE_FREE   = 4;   //отгул
    const TINE_HL   = 5;   //отпуск
    const TINE_ILL   = 6;   //больничный
    const TINE_BT   = 7;   //командировка
    const TINE_OTHER   = 8;   //другое
    

    protected function init() {
        $this->time_id = 0;
        $this->emp_id = 0;
        $this->t_type = 0;
    }

    
    public static  function getTypeTime(){
        $list=array() ;
        $list[self::TIME_WORK] = H::l('Рабочее время');
        $list[self::TINE_OVER] = H::l('Переработка');
        $list[self::TINE_WN] = H::l('Выходные и ночные часы');
        $list[self::TINE_FREE] = H::l('Отгул');
        $list[self::TINE_HL] = H::l('Отпуск');
        $list[self::TINE_ILL] = H::l('Больничный');
        $list[self::TINE_BT] = H::l('Командировка');
        $list[self::TINE_OTHER] = H::l('Другое');
        
        return $list;
    }
    
    
    public  function isValid(){
        
        
        
        return  true;
    }
    
    
}
