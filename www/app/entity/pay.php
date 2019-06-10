<?php

namespace App\Entity;

/**
 * Класс-сущность  оплата
 *
 * @table=paylist
 * @keyfield=pl_id
 * @view=paylist_view
 */
class Pay extends \ZCL\DB\Entity {

    protected function init() {
        $this->pl_id = 0;
    }

}