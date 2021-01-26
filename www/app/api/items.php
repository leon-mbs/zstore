<?php

namespace App\API;

class items extends JsonRPC
{
    
    public function catlist($args) {


        $list = array();
        foreach (\App\Entity\Category::find('', 'cat_name') as $cat) {
            $list[] = array('id' => $cat->cat_id, 'name' => $cat->cat_name);

        }
        return $list;
    }
    public function storelist($args) {


        $list = array();
        foreach (\App\Entity\Store::find('', 'store_name') as $store) {
            $list[] = array('id' => $store->store_id, 'name' => $store->store_name);

        }
        return $list;
    }

    public function itemlist($args) {

        $list = array();
        $w = 'disabled<> 1 ';

        if ($args['cat'] > 0) {
            $w = " and cat_id=" . $args['cat'];
        }
        //\App\Helper::log($w);
        foreach (\App\Entity\Item::find($w, 'itemname') as $item) {
        $plist = array();
        if ($item->price1 > 0) {
            $plist['price1'] = $item->price1;
        }
        if ($item->price2 > 0) {
            $plist['price2'] = $item->price2;
        }
        if ($item->price3 > 0) {
            $plist['price3'] = $item->price3;
        }
        if ($item->price4 > 0) {
            $plist['price4'] = $item->price4;
        }
        if ($item->price5 > 0) {
            $plist['price5'] = $item->price5;
        }           
    
        $list[] = array('id' => $item->item_id,
                            'code' => $item->item_code, 
                            'barcode' => $item->bar_code, 
                            'name' => $item->itemname,
                            'description' => base64_encode($item->desdescriptionc),
                            'measure' => $item->msr,
                            'manufacturer' => $item->manufacturer,
                            'category_name' => $item->cat_name,
                            'category_id' => $item->cat_id ,
                            'prices' => $plist
                            )  ;
                            

        }
        return $list;
    }

}