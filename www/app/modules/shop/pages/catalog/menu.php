<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Entity\Category;
use App\Entity\Item;

class Menu extends \Zippy\Html\WebPage
{
    public function __construct($pm=0) {
        parent::__construct();

        $options = \App\System::getOptions('food');

        if($options['menu'] != 1) {
            http_response_code(404);
            die;
        }


        $this->_tvars['list']   = $pm >0 ;
        $this->_tvars['phone']   = $options['phone'] ;
        $this->_tvars['name']    = $options['name'] ;
        $this->_tvars['timepn']  = $options['timepn'] ;
        $this->_tvars['timesa']  = $options['timesa'] ;
        $this->_tvars['timesu']  = $options['timesu'] ;
        $this->_tvars['address']  = $options['address'] ??'';
        $this->_tvars['secondm']  = $options['foodmenu2'] > 0;

        $this->_tvars['bmname']  = $options['foodbasemenu'] > 0 ? $options['foodbasemenuname']  : 'Основне меню' ;
        $this->_tvars['smname']= $options['foodmenuname']  ;  
 
        $pcat= $options['foodbasemenu']  ?? 0;
     
        if($options['foodmenu2'] > 0 && $pm==2 )   {

            $pcat= $options['foodmenu2']   ;
        
        }
 
 
        $this->_tvars['cats'] =[];

        $cats= Category::find("detail  not  like '%<nofastfood>1</nofastfood>%' and coalesce(parent_id,0) ={$pcat}")  ;


        usort($cats, function ($a, $b) {
            return $a->order > $b->order;
        });
        foreach($cats as $cat) {
            $iw="disabled<>1 and cat_id=".$cat->cat_id;
      
            $items = [];
            foreach(Item::findYield($iw) as $item) {
                if($item->noshop ==1)  continue;
            
                $it=array(
                    'itemname'=>$item->itemname ,
                    'imglink'=>$item->getImageUrl(true) ,
                    'desc'=> substr($item->description, 0, 200) ,
                    'price'=>$item->getPrice($options['pricetype']) ,
                    'priceout'=>false ,
                    'customsize'=> $item->customsize 
                );
                if(strlen ($options['pricetypeout']??'')>1) {
                    $priceout=  $item->getPrice($options['pricetypeout']); 
                    if(doubleval($priceout) >0  && doubleval($priceout) != doubleval($it['price']) )  {
                        $it['priceout'] = $priceout;
                    }
                }
                
                $items[]   = $it;
            }            
            
            
           $this->_tvars['cats'][] = array( 
             'cat_id'=>'mt'.$cat->cat_id,
             'cat_name'=>$cat->cat_name,
             'items'=>$items 
            );
        }
        
 
    }

}
