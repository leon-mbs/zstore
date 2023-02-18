<?php

namespace App\Entity;

/**
 * Класc-сущность   тип начислений удержаний
 *
 * @table=saltypes
 * @keyfield=st_id
 */
class SalType extends \ZCL\DB\Entity
{

    protected function init() {

        $this->salcode = 0;

    }


    public static function getList() {
        $list = array();

        foreach (self::find("disabled<>1", "salcode") as $v) {
            $list[$v->salcode] = $v->salcode . ', ' . $v->salname;
        };

        return $list;
    }
}
