<?php

namespace App\Shop\Entity;

//класс-сущность  строки  детализации  заказа
/**
 * @keyfield=orderdetail_id
 * @table=shop_orderdetails
 * @view=shop_orderdetails_view
 */
class OrderDetail extends \ZCL\DB\Entity {

    protected function init() {
        $this->order_id = 0;
        $this->orderdetail_id = 0;
    }

}
