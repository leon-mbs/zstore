<?php

namespace App\Modules\Shop;

use App\System;

//класс  корзины
class Basket implements \Zippy\Interfaces\DataSource
{

    public $list = array();

    public static function getBasket() {
      
        $basket = System::getSession()->productbasket;
        if (!isset($basket)) {
            $basket = new Basket();
            $cl = @unserialize($_COOKIE['shop_cart'] );
            if(is_array($cl)){
               foreach($cl as $k=>$v){
                  $item=\App\Entity\Item::load($k) ;
                  if($item !=null && $item->disabled !=1){
                      $item->quantity = $v;
                      $this->addProduct($item) ;
                  }
               }
                  
            }
            System::getSession()->productbasket = $basket;
        }
        return $basket;
    }

    public function addProduct($product) {
        if (isset($this->list[$product->item_id])) {
            $this->list[$product->item_id]->quantity++;
        } else {
            $this->list[$product->item_id] = $product;
        }
        $this->sendCookie();
        
    }

    public function deleteProduct($product_id) {

        $this->list_ = array_values($this->list);
        $this->list = array();
        foreach ($this->list_ as $p) {
            if ($p->item_id == $product_id) {
                continue;
            }
            $this->list[$p->item_id] = $p;
        }
        $this->sendCookie();
    }
    public function sendCookie(){
        $cl= array();
        foreach($this->list as $it){
           $cl[$it->item_id] =$it->quantity;                 
        }
        
        setcookie('shop_cart',serialize($cl),0 ) ;
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
