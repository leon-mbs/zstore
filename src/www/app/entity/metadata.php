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
    const METATYPE_REF = 3;
    const METATYPE_REG = 4;

    protected function beforeDelete() {
        return true;
    }

    public static function getNames() {
        $list = array();
        $list[1] = "Документ";
        $list[2] = "Отчет";
        $list[3] = "Журнал";
        $list[4] = "Справочник";
        $list[5] = "Онлайн каталог";


        return $list;
    }

}
