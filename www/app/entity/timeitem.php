<?php

namespace App\Entity;

/**
 * Класс-сущность  запись  о  рабочем  времени
 *
 * @table=timesheet
 * @view=timesheet_view
 * @keyfield=time_id
 */
class TimeItem extends \ZCL\DB\Entity
{

    protected function init() {
        $this->time_id = 0;
        $this->emp_id = 0;
        $this->t_type = 0;
    }

    
    public static  function getTypeTiime(){
        $list=array() ;
        
        
        return $list;
    }
    
    
}
