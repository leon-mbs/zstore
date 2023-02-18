<?php

namespace App\Entity;

/**
 * Класс-сущность  производственный участок
 *
 * @table=parealist
 * @keyfield=pa_id
 */
class ProdArea extends \ZCL\DB\Entity
{

    protected function init() {
        $this->pa_id = 0;
    }

}
