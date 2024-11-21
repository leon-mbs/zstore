<?php
 
namespace App\Entity;

/**
 * Класс-сущность  тэг
 *
 * @table=taglist
 * @keyfield=id
 */
class Tag extends \ZCL\DB\Entity
{
    public  const  TYPE_CUSTOMER=1;
    public  const  TYPE_OFFICEDCO=2;
    public  const  TYPE_ITEM=3;
    
    protected function init() {
        $this->id = 0;
    }
    
    
    public  static function updateTags(array $tags,int  $type,int $item_id)  {
    
       $conn = \ZDB\DB::getConnect()  ;
       $conn->Execute("delete from taglist where tag_type={$type} and  item_id={$item_id}  ");

       foreach($tags as $tag) {
           $t = new  Tag();
           $t->tag_name = $tag;
           $t->tag_type = $type;
           $t->item_id = $item_id;
           $t->save();
       }
   
        
    }
    
    public  static function getTags(int  $type,int $item_id=0)   {
       $conn = \ZDB\DB::getConnect()  ;
       
       if($item_id >0){
          $r = $conn->GetCol("select distinct tag_name from taglist where tag_type={$type} and  item_id={$item_id}  order  by tag_name ");
       } else {
          $r = $conn->GetCol("select distinct tag_name from taglist where tag_type={$type}  order  by tag_name ");
           
       }
       
       $ret=[]; 
       foreach($r as $t)  {
           if(strlen($t) >0) {
               $ret[]=$t;
           }
       }
       return $ret; 
    }
   
    public  static function getSuggestions(int  $type)   {
       $conn = \ZDB\DB::getConnect()  ;
       
       $ret = $conn->GetCol("select distinct tag_name from taglist where tag_type={$type}   order  by tag_name ");
        
        
       return $ret;  
    }
    
}
