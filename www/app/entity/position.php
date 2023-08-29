<?php

namespace App\Entity;

/**
 * Класс-сущность должность
 *
 * @table=positions
 * @keyfield=position_id
 */
class Position extends \ZCL\DB\Entity
{
    protected function init() {
        $this->position_id = 0;

    }

}
