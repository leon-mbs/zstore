<?php

namespace App\Shop\Entity;

/**
 * класс-сущность  атрибута продукта
 * @table=shop_attributes
 * @keyfield=attribute_id
 * @view=shop_attributes_view
 */
class ProductAttribute extends \ZCL\DB\Entity
{

    public $searchvalue;
    public $nodata = 0;

    protected function init() {
        $this->attribute_id = 0;
        $this->showinlist = 0;
        $this->value = '';
    }

    protected function beforeDelete() {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from shop_attributevalues where  attribute_id=" . $this->attribute_id);
        $conn->Execute("delete from shop_attributes_order where  attr_id=" . $this->attribute_id);
    }

}
