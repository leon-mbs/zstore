<?php

namespace App\Modules\Shop\Entity;

/**
 * класс-сущность  атрибута продукта
 * @table=shop_attributes
 * @keyfield=attribute_id
 * @view=shop_attributes_view
 */
class ProductAttribute extends \ZCL\DB\Entity
{
    public $searchvalue;

    protected function init() {
        $this->attribute_id = 0;

        $this->value = '';
    }

    protected function afterDelete() {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from shop_attributevalues where  attribute_id=" . $this->attribute_id);
        $conn->Execute("delete from shop_attributes_order where  attr_id=" . $this->attribute_id);
    }

    public function hasData() {
        if ($this->attributevalue == '') {
            return false;
        }
        if ($this->attributetype == 3) {
            if ($this->attributevalue == -1) {
                return false;
            }
        }
        if ($this->attributetype == 1) {
            if ($this->attributevalue == -1) {
                return false;
            }
        }
        return true;
    }

}
