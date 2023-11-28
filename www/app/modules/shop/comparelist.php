<?php

namespace App\Modules\Shop;

use App\System;

//класс  списка сравнения
class CompareList  // implements \Zippy\Interfaces\DataSource
{
    public $list = array();

    public static function getCompareList() {

        $comparelist = System::getSession()->comparelist;
        if (!isset($comparelist)) {
            $comparelist = new CompareList();
            System::getSession()->comparelist = $comparelist;
        }
        return $comparelist;
    }

    public function addProduct($product) {
        if (count($this->list) > 0) {
            $v = array_values($this->list);
            if ($v[0]->cat_id != $product->cat_id) {
                return false;
            }
        }
        $this->list[$product->item_id] = $product;
        return true;
    }

    public function deleteProduct($item_id) {
        $list_ = array_values($this->list);
        $this->list = array();
        foreach ($list_ as $p) {
            if ($p->item_id == $item_id) {
                continue;
            }
            $this->list[$p->item_id] = $p;
        }
    }

    public function hasProsuct($item_id) {
        return isset($this->list[$item_id]);
    }

    public function isEmpty() {
        return count($this->list) == 0;
    }
    public function getItemCount() {
        return count($this->list)  ;
    }


}
