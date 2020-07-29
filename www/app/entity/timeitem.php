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
   // const TINE_FREE   = 4;   //отгул
    const TINE_HL   = 5;   //отпуск
    const TINE_ILL   = 6;   //больничный
    const TINE_BT   = 7;   //командировка
    const TINE_OTHER   = 10;   //другое
    

    protected function init() {
        $this->time_id = 0;
        $this->emp_id = 0;
        $this->t_type = 0;
    }
 
    protected function afterLoad() {
        $this->t_start = strtotime($this->t_start);
        $this->t_end = strtotime($this->t_end);
    }
    
    public static  function getTypeTime(){
        $list=array() ;
        $list[self::TIME_WORK] = H::l('Рабочее время');
        $list[self::TINE_OVER] = H::l('Переработка');
        $list[self::TINE_WN] = H::l('Выходные и ночные часы');
       // $list[self::TINE_FREE] = H::l('Отгул');
        $list[self::TINE_HL] = H::l('Отпуск');
        $list[self::TINE_ILL] = H::l('Больничный');
        $list[self::TINE_BT] = H::l('Командировка');
        $list[self::TINE_OTHER] = H::l('Другое');
        
        return $list;
    }
    
    
    public  function isValid(){
        
        if(($this->t_end - $this->t_start) <  300 ) {
           return  "ts_invalidinterval";  
        }
        if(($this->t_end - $this->t_start) > (24*3600 - 300)   ) {
           return  "ts_invalidinterval";  
        }
        if(($this->t_end - $this->t_start) <=  $this->t_break ) {
           return  "ts_invalidinterval";  
        }
        $conn = \ZDB\DB::getConnect();
        $t_start = $conn->DBDate($this->t_start) ;
        $t_end = $conn->DBDate($this->t_end) ;
        $sql = " select  count(*) from timesheet where  emp_id={$this->emp_id}  and   t_start > {$t_start}  and  t_start < {$t_end}  ";     
        $cnt = $conn->GetOne($sql);
        if($cnt > 0){
            return  "ts_intersect";    
        } 
        $sql = " select  count(*) from timesheet where  emp_id={$this->emp_id}  and   t_end > {$t_start}  and  t_end < {$t_end}  ";     
        $cnt = $conn->GetOne($sql);
        if($cnt > 0){
            return  "ts_intersect";    
        } 
        return  "";
    }
    
    
}
