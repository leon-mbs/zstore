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

    const TYPE_DOC = 1;
    const TYPE_CUST = 2;

    protected function afterLoad() {
        $this->created = strtotime($this->created);
    }

}
