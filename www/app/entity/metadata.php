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
    public const METATYPE_DOC = 1;
    public const METATYPE_REP = 2;
    public const METATYPE_REG = 3;
    public const METATYPE_REF = 4;
    public const METATYPE_SER = 5;

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
