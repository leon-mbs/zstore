<?php

namespace App\Entity;

/**
 *  Класс  уведомление
 * @table=notifies
 * @keyfield=notify_id
 */
class Notify extends \ZCL\DB\Entity
{

    const ARMFOODPROD = -1;  //арм  кухни    
    const ARMFOOD     = -2;  //арм  кассира кафе
    const DELIV       = -3;  //журнал  доставок
    const SYSTEM      = -4;  //система
    const EVENT       = -5;  //напоминание о событии
    const CHAT        = -6;  //сообщение  в  чат
    const SUBSCRIBE   = -7;  //рассылка

    protected function init() {

        $this->sender_id = 0;
        $this->notify_id = 0;
        $this->checked = 0;
        $this->user_id = 0;
        $this->message = '';
        $this->dateshow = time();
    }

    protected function afterLoad() {
        $this->dateshow = strtotime($this->dateshow);
    }

    protected function beforeSave() {
        parent::beforeSave();

        if ($this->user_id > 0) {
            $user = User::load($this->user_id);
            if ($user->emailnotify == 1 && strlen($user->email) > 0) {
                \App\Helper::sendLetter($user->email,$this->message,    \App\Helper::l("newnotify"));
            }
        }
    }

    //количество непрочитанных уведомлений 
    public static function isNotify($user_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $cnt = Notify::findCnt("checked = 0 and dateshow <= now() and user_id={$user_id} ");
        return $cnt;
    }

    public static function markRead($user_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $sql = "update notifies set checked = 1 where dateshow <= " . $conn->DBTimeStamp(time()) . " and user_id =" . $user_id;
        $conn->Execute($sql);
    }
   
    public static function toSystemLog($message) {
            $n = new  Notify();
            $n->user_id =  Notify::SYSTEM;

            $n->message = $message;
            $n->save();
    }
    
    public static function toAdmin($message) {
            $admin = \App\Entity\User::getByLogin('admin');

            $n = new  Notify();
            $n->user_id =  $admin->user_id;
            $n->sender_id =  Notify::SYSTEM;

            $n->message = $message;
            $n->save();
    }

}
