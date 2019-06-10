<?php

namespace App\Entity;

/**
 * Класс-сущность  денежный счет
 *
 * @table=mfund
 * @keyfield=mf_id
 */
class MoneyFund extends \ZCL\DB\Entity {

    protected function init() {
        $this->mf_id = 0;
    }

}
