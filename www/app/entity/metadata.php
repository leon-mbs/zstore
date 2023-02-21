<?php

namespace App\Entity;

/**
 * Клас-сущность  метаданные
 *
 * @table=metadata
 * @keyfield=meta_id
 */
class MetaData extends \ZCL\DB\Entity
{

    const METATYPE_DOC = 1;
    const METATYPE_REP = 2;
    const METATYPE_REG = 3;
    const METATYPE_REF = 4;
    const METATYPE_SER = 5;

    public static function getNames() {
        $list = array();
        $list[1] = "Документ";
        $list[2] = "Звіт";
        $list[3] = "Журнал";
        $list[4] = "Довідник";
        $list[5] = "Сервісна сторінка";

        return $list;
    }

}
