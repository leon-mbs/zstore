<?php

namespace App\Modules\Shop\Entity;

//класс-сущность  коментария   к   товару

/**
 * @keyfield=comment_id
 * @table=shop_prod_comments
 */
class ProductComment extends \ZCL\DB\Entity
{
    protected function init() {
        $this->comment_id = 0;
    }

    public static function findByProduct($item_id) {
        return ProductComment::find(' item_id=' . $item_id, 'comment_id desc');
    }

    protected function afterLoad() {
        $this->created = strtotime($this->created);
    }

}
