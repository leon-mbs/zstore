<?php

namespace App\Entity;

/**
 * Класс-сущность комплект ТМЦ
 *
 * @table=item_set
 * @view=item_set_view
 * @keyfield=set_id
 */
class ItemSet extends \ZCL\DB\Entity
{
    protected function init() {
        $this->set_id = 0;
    }

}
