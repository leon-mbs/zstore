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
        $this->event_type = 0;


        $this->user_id = 0;
        $this->customer_id = 0;
        $this->eventdate = time();
        $this->isdone = 0;
    }



    public static function isNotClosedTask($user_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $cnt = Event::findCnt("isdone<>1 and event_type in(0,2) and user_id={$user_id} ");
        return $cnt;

    }
    protected function beforeSave() {
        parent::beforeSave();
        $this->details = "<details>";
        $this->details .= "<amount>{$this->amount}</amount>";
        $this->details .= "<paytype>{$this->paytype}</paytype>";
        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        $this->eventdate = strtotime($this->eventdate);
        $this->createdon = strtotime($this->createdon);

        $xml = @simplexml_load_string($this->details);


        $this->amount = (float)($xml->amount[0]);
        $this->paytype = (int)($xml->paytype[0]);

        parent::afterLoad();
    }
}
