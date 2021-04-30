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
        $list[1] = \App\Helper::l("md_doc");
        $list[2] = \App\Helper::l("md_rep");
        $list[3] = \App\Helper::l("md_reg");
        $list[4] = \App\Helper::l("md_ref");
        $list[5] = \App\Helper::l("md_ser");

        return $list;
    }

}
