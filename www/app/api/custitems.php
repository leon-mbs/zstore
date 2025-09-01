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

 
    // запись  ТМЦ.
    public function save($args) {
        if (strlen($args['item_code']) == 0) {
            throw new \Exception("Не задано артикул");
        }

        $code = Item::qstr($args['item_code']);
        $item = Item::getFirst("   item_code = {$code}  ");

        if ($item == null) {
            $item = new Item();
        }

        $item->item_code = $args['item_code'];
        $item->bar_code = $args['bar_code'];
        $item->itemname = $args['itemname'];
        $item->msr = $args['measure'];
        $item->manufacturer = $args['manufacturer'];
        $item->description = @base64_decode($args['description']);
        $item->cat_id = $args['cat_id'];
        $item->item_type = $args['item_type'];

        if ($args['price1'] > 0) {
            $item->price1 = $args['price1'];
        }
        if ($args['price2'] > 0) {
            $item->price2 = $args['price2'];
        }
        if ($args['price3'] > 0) {
            $item->price3 = $args['price3'];
        }
        if ($args['price4'] > 0) {
            $item->price4 = $args['price4'];
        }
        if ($args['price5'] > 0) {
            $item->price5 = $args['price5'];
        }

        if (strlen($item->itemname) == 0) {
            throw new \Exception("Не вказано назву");
        }
        if (strlen( $args['imageurl'] ) > 0) {

                $file = file_get_contents($args['imageurl']) ;
                if(strlen($file)>0) {
                    $tmp = tempnam(sys_get_temp_dir(), "import") ;
                    file_put_contents($tmp, $file) ;

                    $imagedata = getimagesize($tmp);
                    if (is_array($imagedata)) {
                 
                        $image = new \App\Entity\Image();
                        $image->content = file_get_contents($tmp);
                        $image->mime = $imagedata['mime'];

                        if ($imagedata[0] != $imagedata[1]) {
                            $thumb = new \App\Thumb($tmp);
                            if ($imagedata[0] > $imagedata[1]) {
                                $thumb->cropFromCenter($imagedata[1], $imagedata[1]);
                            }
                            if ($imagedata[0] < $imagedata[1]) {
                                $thumb->cropFromCenter($imagedata[0], $imagedata[0]);
                            }


                            $image->content = $thumb->getImageAsString();
                            $thumb->resize(512, 512);
                            $image->thumb = $thumb->getImageAsString();
                            $thumb->resize(128, 128);

                            $item->thumb = "data:{$image->mime};base64," . base64_encode($thumb->getImageAsString());
                        }
                  
                        $image->save();
                        $item->image_id = $image->image_id;
                    
                    }
                }
            
        }

        
        $item->save();
        return array('item_code' => $item->item_code);
    }


  
}
