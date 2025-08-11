<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Entity\Category;
use App\Entity\Item;

class Menu extends \Zippy\Html\WebPage
{
    public function __construct($pm=0,$tn=0) {
        parent::__construct();

        $options = \App\System::getOptions('common');
         
        if($options['usefood'] != 1) {
            http_response_code(404);
            die;
        }  
     
        $options = \App\System::getOptions('food');
       
        $this->_tvars['alert']   = false ;     
  
        if($pm=='tableno')  {
            $pm=0;
            $this->_tvars['alert']   = true ; 
            
            $n = new \App\Entity\Notify();
            $n->user_id = \App\Entity\Notify::ARMFOOD;
            $n->dateshow = time();

            $n->message = serialize(array('tableno' => $tn));

            $n->save();                 
        
        }

 
        $this->_tvars['list']   = $pm >0 ;
        $this->_tvars['phone']   = $options['phone'] ;
        $this->_tvars['name']    = $options['name'] ;
        $this->_tvars['timepn']  = $options['timepn'] ;
        $this->_tvars['timesa']  = $options['timesa'] ;
        $this->_tvars['timesu']  = $options['timesu'] ;
        $this->_tvars['address']  = $options['address'] ??'';
        $this->_tvars['secondm']  = $options['foodmenu2'] > 0;
        $this->_tvars['secondm3']  = $options['foodmenu3'] > 0;
        $this->_tvars['secondm4']  = $options['foodmenu4'] > 0;

        $this->_tvars['bmname']  = $options['foodbasemenu'] > 0 ? $options['foodbasemenuname']  : 'Основне меню' ;
        $this->_tvars['smname']= $options['foodmenuname']  ;  
        $this->_tvars['smname3']= $options['foodmenuname3']  ;  
        $this->_tvars['smname4']= $options['foodmenuname4']  ;  
 
        $pcat= $options['foodbasemenu']  ?? 0;
     
        if($options['foodmenu2'] > 0 && $pm==2 )   {

            $pcat= $options['foodmenu2']   ;
        
        }
        if($options['foodmenu3'] > 0 && $pm==3 )   {

            $pcat= $options['foodmenu3']   ;
        
        }
        if($options['foodmenu4'] > 0 && $pm==4 )   {

            $pcat= $options['foodmenu4']   ;
        
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
                    'desc'=>  $item->description ,
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
