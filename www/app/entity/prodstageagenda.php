<?php

namespace App\Entity;

use App\Helper;
use App\System;

/**
 * Класс-сущность  производственный процесс
 *
 * @table=prodstageagenda
 * @view=prodstageagenda_view
 * @keyfield=sta_id
 */
class ProdStageAgenda extends \ZCL\DB\Entity
{
    protected function init() {
        $this->sta_id = 0;
    }

    protected function afterLoad() {
        $this->startdate = strtotime($this->startdate);
        $this->enddate = strtotime($this->enddate);
    }

}
