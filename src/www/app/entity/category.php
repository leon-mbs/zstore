<?php

namespace App\Entity;

/**
 * Клас-сущность  категория товара
 *
 * @table=item_cat
 * @keyfield=cat_id
 */
class Category extends \ZCL\DB\Entity
{

    protected function init() {
        $this->cat_id = 0;
    }

}
