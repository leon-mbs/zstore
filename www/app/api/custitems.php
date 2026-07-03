<?php

namespace App\API;

use App\Entity\Item;
use App\Entity\CustItem;
use App\Helper as H;

class custitems extends JsonRPC
{
 

    //  список  ТМЦ
    public function itemlist($args) {

        $list = array();
        $w = '1 = 1 ';

        if ($args['customer_id'] > 0) {
            $w .= " and customer_id=" . $args['cat'];
        } else {
            throw new \Exception('Не вказано  постачальника');            
        }
        
 

        foreach (CustItem::findYield($w, 'cust_name') as $item) {
            $plist = array();

            $it = array(
               
                'cust_code'    => $item->item_code,
                'cust_name'     => $item->cust_name,
                'price'     => $item->price,
                'quantity'      => $item->quantity,
                'item_code'    => $item->item_code,
                'bar_code' => $item->bar_code,
                'brand'     => $item->brand 
                 
            );

         
            $list[] = $it;
        }


         return $list;
    }

 
     
  
}
