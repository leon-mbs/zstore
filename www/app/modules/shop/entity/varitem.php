<?php

namespace App\Modules\Shop\Entity;

//класс-сущность вариация  товара

/**
 * @keyfield=varitem_id
 * @table=shop_varitems
 * @view=shop_varitems_view
 */
class VarItem extends \ZCL\DB\Entity
{
    protected function init() {
        $this->varitem_id = 0;
    }


    //доступные товары
    public static function getFreeItems($attr_id) {
        $conn = \ZCL\DB\DB::getConnect();

        $sql= "select distinct  a.item_id, itemname,item_code,a.attributevalue from items  i  join  shop_attributevalues  a on i.item_id=a.item_id where  a.item_id not in(select item_id from shop_varitems) and   attribute_id={$attr_id} order  by  itemname ";

        $items = $conn->Execute($sql)  ;

        $list = array();
        foreach($items as $item) {
            if(strlen($item['attributevalue'])==0) {
                continue;
            }
            $name = $item['itemname'] ;
            if(strlen($item['item_code'])>0) {
                $name = $name .', '. $item['item_code'];
            }
            $name = $name .', '. $item['attributevalue'];
            $list[$item['item_id']]= $name ;
        }

        return $list;
    }




}
