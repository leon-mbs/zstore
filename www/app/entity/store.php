<?php

namespace App\Entity;

/**
 * Клас-сущность  склад
 *
 * @table=stores
 * @keyfield=store_id
 */
class Store extends \ZCL\DB\Entity
{
    protected function init() {

        $this->store_id = 0;
        $this->branch_id = 0;
        $this->disabled = 0;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  store_stock where   store_id = {$this->store_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Не можна видалити склад, що використовується" : '';
    }

    /**
     * список складов для комбо
     *
     */
    public static function getList() {
        return Store::findArray('storename', 'disabled <> 1', 'store_id');
    }
    /**
    * без ограничений по  фииалам
    *
    */
    public static function getListAll() {
        $conn = \ZDB\DB::getConnect();
        $sql = "  select storename,store_id  from  stores where disabled <> 1 ";
        $list = array();
        foreach($conn->Execute($sql) as $row) {
            $list[$row['store_id']]  = $row['storename'];
        }


        return $list;
    }

    public static function getConstraint() {
        $br = \App\ACL::getBranchConstraint();
        if (strlen($br) > 0) {
            $br = " (" . $br . " or coalesce(branch_id,0)=0)  ";
        }  //склады не  привязаные к  филиалу
        return $br;
    }

}
