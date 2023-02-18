<?php

namespace App\Modules\PPO;

/**
 * Класс запись z-отчета
 * @table=ppo_zformrep
 * @keyfield=id
 */
class ZRecord extends \ZCL\DB\Entity
{

    protected function init() {
        $this->id = 0;
    }

    protected function afterLoad() {
        $this->createdon = strtotime($this->createdon);
    }

}
