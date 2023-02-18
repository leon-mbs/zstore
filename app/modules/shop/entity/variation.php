<?php

namespace App\Modules\Shop\Entity;

//класс-сущность вариация  товара

/**
 * @keyfield=var_id
 * @table=shop_vars
 * @view=shop_vars_view
 */
class Variation extends \ZCL\DB\Entity
{

    protected function init() {
        $this->var_id = 0;
    }

    public  static  function delItems($var_id){
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete  from shop_varitems where  var_id=".$var_id);
    } 

}
