<?php

namespace App\Modules\Shop;

use App\System;
use App\Modules\Shop\Entity\Product;

//класс  корзины
class Basket implements \Zippy\Interfaces\DataSource
{
    public $list = array();

    public static function getBasket() {

        $basket = System::getSession()->productbasket;
        if($basket==null) {
            $basket = new Basket();

            $cl = json_decode($_COOKIE['shop_cart'] ??"", true);
            if(is_array($cl)) {
                foreach($cl as $p) {
                    $item=Product::load($p['item_id']) ;
                    if($item !=null && $item->disabled !=1) {
                        $item->quantity = $p['quantity'];
                        $basket->addProduct($item) ;
                    }
                }

            }
            System::getSession()->productbasket = $basket;
        }


        return $basket;
    }

    public function addProduct($product) {

        $p = new \App\DataItem();
        $p->price  = $product->getPriceFinal();
        $p->quantity  = $product->quantity;
        $p->itemname  = $product->itemname;
        $p->item_id   = $product->item_id;
        $p->image_id   = $product->image_id;

        if (isset($this->list[$p->item_id])) {
            $this->list[$p->item_id]->quantity++;
        } else {
            $this->list[$p->item_id] = $p;
        }
        $this->sendCookie();
        \App\Helper::insertstat(\App\Helper::STAT_CARD_SHOP, 0, 0) ;

    }




    public function deleteProduct($product_id) {

        $list_ = $this->list;
        $this->list = array();
        foreach ($list_ as $p) {
            if ($p->item_id == $product_id) {
                continue;
            }
            $this->list[$p->item_id] = $p;
        }
        $this->sendCookie();
    }
    public function sendCookie() {
        $cl= array();
        foreach($this->list as $it) {
            $cl[] = array(
              "item_id"=>$it->item_id,
              "quantity"=>$it->quantity,
              "price"=>$it->price  ,
              "itemname"=>$it->itemname
            );
        }

        setcookie('shop_cart', json_encode($cl), time() + 60 * 60 * 24 * 30) ;
    }
    public function isEmpty() {
        return count($this->list) == 0;
    }

    public function Empty() {
        $this->list = array();
        $this->sendCookie();

    }

    // реализация  DataSource

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return array_values($this->list);
    }

    public function getItemCount() {
        return count($this->list);
    }

}
