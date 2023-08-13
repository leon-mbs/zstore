<?php

namespace App\Entity;

/**
 * Класс  расписания событий
 * @table=eventlist
 * @view=eventlist_view
 * @keyfield=event_id
 */
class Event extends \ZCL\DB\Entity
{
    public const TYPE_CUSTOMER = 1;
    public const TYPE_JOB = 2;
    public const TYPE_PAYMENT = 3;
    
    protected function init() {
        $this->event_id = 0;


        $this->user_id = 0;
        $this->customer_id = 0;
        $this->eventdate = time();
    }

    protected function afterLoad() {
        $this->eventdate = strtotime($this->eventdate);
    }


    public static function isNotClosedTask($user_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $cnt = Event::findCnt("isdone<>1  and user_id={$user_id} ");  //todo  type
        return $cnt;

    }

}
