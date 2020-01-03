<?php

namespace App\Entity;

/**
 * Клас-сущность  коментари
 *
 * @table=messages
 * @view=messages_view
 * @keyfield=message_id
 */
class Message extends \ZCL\DB\Entity {

    const TYPE_DOC = 1;
    const TYPE_CUST = 2;
    const TYPE_EQ = 3;
    const TYPE_NOTE = 4;
    const TYPE_ISSUE = 5;
    const TYPE_PROJECT = 6;

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
    public  static function getMessages($type,$item_d,$sort='asc'){
       return = \App\Entity\Message::find("item_type ={$type} and item_id={$item_id}","created ".$sort);
    }
 
    
    
}
