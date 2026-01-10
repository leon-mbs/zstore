<?php

namespace App\Widgets;

use App\Entity\Category;
use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Label;
use Zippy\Html\Panel;

/**
 * Виджет для подбора  товаров   для страниц  на  vue
 */
class ItemList extends \Zippy\Html\PageFragment
{
    private $_p         = null;

    /**
     *
     *
     * @param mixed $id
     */
    public function __construct($id) {
        parent::__construct($id);

        $this->add(new Label('_itemlist_')) ;  // URI путь к  виджету


    }

    public function init() {


        $path = $this->id;
        $owner =  $this->getOwner() ;
        while(($owner    instanceof \Zippy\Html\WebPage)==false) {
            $path = $owner->id.'::'.$path ;
            $owner =  $owner->getOwner() ;
        }
        $this->_itemlist_->setAttribute('path', $path);
        $this->_p = $this->getPageOwner()  ;

    }


    public function loaditems($args, $post=null) {
        $post = json_decode($post)   ;

        $where = "disabled <> 1 ";
        if($post->wissearchonstore ==true) {
            $where = "   disabled <> 1 and  ( select coalesce(sum(st1.qty),0 ) from store_stock st1 where st1.item_id= items_view.item_id ) >0 ";
        }
        $br = \App\ACL::getBranchConstraint();
        if (strlen($br) > 0) {
           $where .= " and  item_id in (select item_id from store_stock where  store_id in (select store_id from stores where {$br} ))  "; 
        }


        if(strlen($post->searchkey) > 0) {
            $det = Item::qstr('%' . "<cflist>%{$post->searchkey}%</cflist>" . '%');
            $_sk= Item::qstr($post->searchkey);
            $_skn= Item::qstr('%'.$post->searchkey.'%');

            $where = $where. " and ( itemname  like {$_skn}  or item_code= {$_sk} or bar_code= {$_sk}  or detail like {$det}  ) " ;
        }

        if($post->searchcat > 0) {
            $where = $where. " and cat_id= ". $post->searchcat;
        }
        if(strlen($post->searchbrand) > 0) {
            $where = $where. " and manufacturer = ". Item::qstr($post->searchbrand);
        }

        $all =  Item::findCnt($where) ;
        $items = [];

        foreach(Item::findYield($where, 'itemname asc', $post->pagesize, $post->currpage * $post->pagesize)  as $it) {
            $items[]= array(
             'item_id'=>$it->item_id,
             'itemname'=>$it->itemname,
             'item_code'=>$it->item_code,
             'bar_code'=>$it->bar_code,
             'brand'=>$it->manufacturer
            );


        }


        return json_encode(array('items'=>$items,'rowscnt'=>$all), JSON_UNESCAPED_UNICODE);

    }


}
