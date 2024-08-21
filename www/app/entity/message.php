<?php

namespace App\Entity;

/**
 * Клас-сущность  коментари
 *
 * @table=messages
 * @view=messages_view
 * @keyfield=message_id
 */
class Message extends \ZCL\DB\Entity
{
    public const TYPE_DOC      = 1;
    public const TYPE_CUST     = 2;
    public const TYPE_CHAT     = 3;
    public const TYPE_NOTE     = 4;
    public const TYPE_ISSUE    = 5;
    public const TYPE_PROJECT  = 6;
    public const TYPE_CONTRACT = 7;
    public const TYPE_CUSTCHAT = 8;
    public const TYPE_EMP = 9;


    protected function init() {
        $this->user_id = 0;
        $this->item_id = 0;
        $this->created = time();
    }

    protected function afterLoad() {
        $this->created = strtotime($this->created);
    }

    /**
     * возвращает список сообщений
     *
     * @param mixed $type
     * @param mixed $item_d
     * @param mixed $sort
     */
    public static function getMessages($type, $item_id) {
        return \App\Entity\Message::find("item_type ={$type} and item_id={$item_id}", "message_id ");
    }

    /**
     * список  участников  дискусии (напррмер для  уведомлений)
     */
    public static function getParticipants($type, $item_id) {
        $conn = \ZDB\DB::getConnect();
        $sql = "select distinct  user_id from  messages where  item_type ={$type} and item_id={$item_id} ";
        return $conn->getCol($sql);
    }

}
