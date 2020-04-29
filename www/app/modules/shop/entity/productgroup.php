<?php

namespace App\Modules\Shop\Entity;

/**
 * класс-сущность  группы товаров
 * @table=shop_productgroups
 * @view=shop_productgroups_view
 * @keyfield=group_id
 * @parentfield=parent_id
 * @pathfield=mpath
 */
class ProductGroup extends \ZCL\DB\TreeEntity
{

    protected function init() {
        $this->group_id = 0;
        $this->parent_id = 0;
        $this->mpath = '';
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  shop_products where   group_id = {$this->group_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Нельзя удалять используемую группу" : "";
    }

    protected function afterDelete() {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from shop_attributes where  group_id =" . $this->group_id);
        foreach ($this->images as $image_id) {
            \App\Entity\Image::delete($image_id);
        }
    }

}
