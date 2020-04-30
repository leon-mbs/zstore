<?php

namespace App\Entity;

/**
 *  Класс  уведомление
 * @table=notifies
 * @keyfield=notify_id
 */
class Notify extends \ZCL\DB\Entity
{

    protected function init() {

        $this->sender_id = 0;
        $this->notify_id = 0;
        $this->checked = 0;
        $this->user_id = 0;
        $this->dateshow = time();
    }

    protected function afterLoad() {
        $this->dateshow = strtotime($this->dateshow);
    }

    //количество непрочианных уведомлений 
    public static function isNotify($user_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $cnt = Notify::findCnt("checked=0 and dateshow <= now() and user_id={$user_id} ");
        return $cnt;
    }

    public static function markRead($user_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $sql = "update notifies set checked = 1 where dateshow <= " . $conn->DBTimeStamp(time()) . " and user_id =" . $user_id;
        $conn->Execute($sql);
    }

}
