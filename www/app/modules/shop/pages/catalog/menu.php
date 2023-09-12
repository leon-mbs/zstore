<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Entity\Category;
use App\Entity\Item;

class Menu extends \Zippy\Html\WebPage
{
    public function __construct($pcat=0) {
        parent::__construct();

        $options = \App\System::getOptions('food');

        if($options['menu'] != 1) {
            http_response_code(404);
            die;
        }


        $this->_tvars['phone']   = $options['phone'] ;
        $this->_tvars['name']    = $options['name'] ;
        $this->_tvars['timepn']  = $options['timepn'] ;
        $this->_tvars['timesa']  = $options['timesa'] ;
        $this->_tvars['timesu']  = $options['timesu'] ;
        $this->_tvars['logo']  = $options['logo'] ;



        $this->_tvars['cats'] =[];

        $cats= Category::find("detail  not  like '%<nofastfood>1</nofastfood>%'")  ;


        usort($cats, function ($a, $b) {
            return $a->order > $b->order;
        });


        foreach($cats as $cat) {

            if($pcat > 0  &&  $cat->parent_id != $pcat) {
                continue;
            }
            if($pcat == 0 &&  $cat->parent_id > 0) {
                continue;
            }

            $iw="disabled<>1 and cat_id=".$cat->cat_id;

            $items = [];
            foreach(Item::findYield($iw) as $item) {

                $items[]=array(
                    'itemname'=>$item->itemname ,
                    'imglink'=>"/loadshopimage.php?id=".  $item->image_id ,
                    'desc'=> substr($item->description, 0, 200) ,
                    'price'=>$item->getPrice() ,
                    'vol'=> strlen($item->weight)>0 ? $item->weight." г" : $item->volume." мл"
                );

            }


            $this->_tvars['cats'][] = array(
              'cat_id'=>$cat->cat_id,
              'hasitems'=>count($items) > 0,
              'items'=>$items,
              'link'=>"/menu/".$cat->cat_id,
              'cat_name'=>$cat->cat_name,
              'cat_desc'=> strlen($cat->cat_desc) > 0 ? $cat->cat_desc : false,
            );


        }




    }

}
