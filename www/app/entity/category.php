<?php

namespace App\Entity;

/**
 * Класс-сущность  категория товара
 *
 * @table=item_cat
 * @keyfield=cat_id
 */
class Category extends \ZCL\DB\Entity
{

    protected function init() {
        $this->cat_id = 0;
        $this->parent_id = 0;
        $this->image_id = 0;
    }

 

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->price1 = (string)($xml->price1[0]);
        $this->price2 = (string)($xml->price2[0]);
        $this->price3 = (string)($xml->price3[0]);
        $this->price4 = (string)($xml->price4[0]);
        $this->price5 = (string)($xml->price5[0]);
        $this->image_id = (int)$xml->image_id[0];
        $this->noshop = (int)$xml->noshop[0];
     

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();

        $this->detail = "<detail>";

        $this->detail .= "<price1>{$this->price1}</price1>";
        $this->detail .= "<price2>{$this->price2}</price2>";
        $this->detail .= "<price3>{$this->price3}</price3>";
        $this->detail .= "<price4>{$this->price4}</price4>";
        $this->detail .= "<price5>{$this->price5}</price5>";
        $this->detail .= "<image_id>{$this->image_id}</image_id>";
        $this->detail .= "<noshop>{$this->noshop}</noshop>";
      

        $this->detail .= "</detail>";

        return true;
    }
     
    
    public  function hasChild(){
        $conn = \ZDB\DB::getConnect();
       
        $sql = "  select count(*)  from  item_cat where  parent_id = {$this->cat_id} ";
        $cnt = $conn->GetOne($sql);
        return  $cnt > 0   ;
        
    }

    public static  function findFullData(){
        $clist = Category::find('','cat_name');
         
        foreach($clist as $c){
           
            $c->parents = $c->getParents($clist);
            
            $names=array();
            foreach($c->parents as $p)  {
                $names[]=  $clist[$p]->cat_name;
            }
            $names = array_reverse($names) ;
            $names[]  =  $c->cat_name;
            $c->full_name =  implode('/',$names)   ;
        }
      
        return $clist;
        
    }
    
    private    function getParents(  &$clist){
         $p = array();
        
         if(  $clist[$this->parent_id]  instanceof  Category) {
            $p[]= $this->parent_id;
            $pp = $clist[$this->parent_id]->getParents($clist); 
            if(count($pp)>0) $p = array_merge($p,$pp) ;
         }
         return $p;
    }
    
    
    public static function getList(){
        return Category::findArray("cat_name", "cat_id in (select cat_id from items  )", "cat_name")  ;
    }  
    
    
}
