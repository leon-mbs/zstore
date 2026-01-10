<?php

namespace App\Widgets;

use App\Entity\Category;
use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Label;
use Zippy\Html\Panel;

/**
 * Виджет для подбора  товаров через  картинки
 */
class CatItemList extends \Zippy\Html\PageFragment
{
    private $_food = false;
    private $_p         = null;

    /**
     *
     *
     * @param mixed $id
     * @param mixed $food
 
     */
    public function __construct($id, $food = false) {
        parent::__construct($id);
        $this->_food = $food;

        $this->add(new Label('_catitemlist_')) ;



    }

    public function init() {


        $path = $this->id;
        $owner =  $this->getOwner() ;
        while(($owner    instanceof \Zippy\Html\WebPage)==false) {
            $path = $owner->id.'::'.$path ;
            $owner =  $owner->getOwner() ;
        }
        $this->_catitemlist_->setAttribute('path', $path);
        $this->_p = $this->getPageOwner()  ;

    }


    public function loaddata($args, $post=null) {
        $post = json_decode($post)   ;

        $ret=[];
        $ret['cats'] = [];
        $ret['items']= [];
        $ret['prev'] = 0;
        if($args[0] > 0) {
            $cat = Category::load($args[0]) ;
            $ret['prev'] = (int)$cat->parent_id ;
        }

        $where= "coalesce(parent_id,0)=".$args[0] ;
        if($this->_food == true) {
            $where .= " and detail  not  like '%<nofastfood>1</nofastfood>%' ";

        }

        $catlist = Category::find($where, "cat_name");
        if(count($catlist) > 0) {

            foreach($catlist as $cat) {
                $ret['cats'][] = array(
                  'cat_id'=>$cat->cat_id,
                  'cat_name'=>$cat->cat_name,
                  'image'=>  $cat->getImageUrl()
                ) ;
            }

        } else {
            

            foreach(Item::findYield('disabled<>1  and  item_type in (1,4 )  and cat_id=' . $args[0]) as $prod) {
                $ret['items'][] = array(
                  'item_id'=>$prod->item_id,
                  'itemname'=>$prod->itemname,
                  'price'=>$prod->getPrice(),
                  'image'=> $prod->getImageUrl()
                ) ;
            }


        }
        return $this->_p->jsonOK($ret);

        
    }

    public function loaditems($args, $post=null) {
        $post = json_decode($post)   ;

        $where = "disabled <> 1 ";
        if(strlen($post->searchkey) > 0) {
            $_sk= Item::qstr($post->searchkey);
            $_skn= Item::qstr('%'.$post->searchkey.'%');

            $where = $where. " and ( itemname  like {$_skn}  or item_code= {$_sk} or bar_code= {$_sk}  ) " ;
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
