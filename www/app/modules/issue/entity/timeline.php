<?php

namespace App\Modules\Issue\Entity;

/**
 *  Класс  инкапсулирующий  запись рабочаго  времени
 * @table=issue_time
 * @view=issue_time_view
 * @keyfield=id
 */
class TimeLine extends \ZCL\DB\Entity
{
    protected function init() {
        $this->id = 0;
        $this->issue_id = 0;

        $this->user_id = 0;
        $this->duration = 0;
    }

    protected function afterLoad() {

        $this->createdon = strtotime($this->createdon);
    }

}
