<?php

namespace App\Entity;

use ZCL\DB\Entity;

/**
 * Класс  расписания событий
 * @table=eventlist
 * @view=eventlist_view
 * @keyfield=event_id

 */
class Event extends Entity
{

    protected function init() {
        $this->event_id = 0;
        $this->notify_id = 0;

        $this->user_id = 0;
        $this->eventdate = time();
    }

    protected function afterLoad() {
        $this->eventdate = strtotime($this->eventdate);
    }

}
