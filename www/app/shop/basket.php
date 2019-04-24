<?php

namespace App\Shop;

use \App\System;

//класс  корзины
class Basket implements \Zippy\Interfaces\DataSource {

    public $list = array();

    public static function getBasket() {

        $basket = System::getSession()->productbasket;
        if (!isset($basket)) {
            $basket = new Basket();
            System::getSession()->productbasket = $basket;
        }
        return $basket;
    }

    public function addProduct($product) {
        $this->list[$product->product_id] = $product;
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
