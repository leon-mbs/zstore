<?php

namespace App\;

use \App\System;

/**
 * Класс  для  упрвления доступом к метаобьектам
 */
class ACL
{

    private static $_metas = array();

    private static function load() {
        if (count(self::$_metas) > 0)
            return;

        $conn = \ZDB\DB::getConnect();
        $rows = $conn->Execute("select * from metadata ");
        foreach ($rows as $row) {
            self::$_metas[$row['meta_type'] . '_' . $row['meta_name']] = $row['meta_id'];
        }
    }

    public static function checkShowReport($rep) {
        if (System::getUser()->acl != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['2_' . $rep];
        $aclshow = explode(',', System::getUser()->aclshow);
        $aclview = explode(',', System::getUser()->aclview);

        return is_array($meta_id, $aclshow);
    }

}
