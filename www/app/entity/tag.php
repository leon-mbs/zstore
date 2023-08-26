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
    
    protected function init() {
        $this->id = 0;
    }
    
    
    public  static function updateTags(array $tags,int  $type,int $item_id)  {
    
       $conn = \Zdb\db::getConnect()  ;
       $conn->Execute("delete from taglist where tag_type={$type} and  item_id={$item_id}  ");

       foreach($tags as $tag) {
           $t = new  Tag();
           $t->tag_name = $tag;
           $t->tag_type = self::TYPE_CUSTOMER ;
           $t->item_id = $item_id;
           $t->save();
       }
   
        
    }
    
    public  static function getTags(int  $type,int $item_id=0)   {
       $conn = \Zdb\db::getConnect()  ;
       
       if($item_id >0){
          $ret = $conn->GetCol("select distinct tag_name from taglist where tag_type={$type} and  item_id={$item_id}  order  by tag_name ");
       } else {
          $ret = $conn->GetCol("select distinct tag_name from taglist where tag_type={$type}  order  by tag_name ");
           
       }
        
        
       return $ret; 
    }
   
    public  static function getSuggestions(int  $type)   {
       $conn = \Zdb\db::getConnect()  ;
       
       $ret = $conn->GetCol("select distinct tag_name from taglist where tag_type={$type}   order  by tag_name ");
        
        
       return $ret;  
    }
    
}
