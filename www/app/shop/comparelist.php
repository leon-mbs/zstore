<?php

namespace App\Shop;

use \App\System;

//клаасс  списка сравоения
class CompareList implements \Zippy\Interfaces\DataSource {

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
            if ($v[0]->group_id != $product->group_id) {
                return false;
            }
        }
        $this->list[$product->product_id] = $product;
        return true;
    }

    public function deleteProduct($product_id) {
        $this->list_ = array_values($this->list);
        $this->list = array();
        foreach ($this->list_ as $p) {
            if ($p->product_id == $product_id)
                continue;
            $this->list[$p->product_id] = $p;
        }
    }

    public function hasProsuct($product_id) {
        return isset($this->list[$product_id]);
    }

    public function isEmpty() {
        return count($this->list) == 0;
    }

    // реализация  DataSource

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return array_values($this->list);
    }

    public function getItemCount() {
        return count($this->list);
    }

}
