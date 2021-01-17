<?php

namespace App\API;

class items extends JsonRPC
{
    /**
     * список категорий
     *
     */
    public function catlist($args) {


        $list = array();
        foreach (\App\Entity\Category::find('', 'cat_name') as $cat) {
            $list[] = array('id' => $cat->cat_id, 'name' => $cat->cat_name);

        }
        return $list;
    }

    public function itemlist($args) {

        $list = array();
        $w = '';

        if ($args['cat'] > 0) {
            $w = "cat_id=" . $args['cat'];
        }
        \App\Helper::log($w);
        foreach (\App\Entity\Item::find($w, 'itemname') as $item) {
            $list[] = array('code' => $item->item_code, 'name' => $item->itemame);

        }
        return $list;
    }

}