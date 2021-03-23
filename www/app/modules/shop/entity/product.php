<?php

namespace App\Modules\Shop\Entity;

/**
 * Класс-сущность товар  онлайн каталога
 *
 * @table=items
 * @view=shop_products_view
 * @keyfield=item_id
 */
class Product extends \App\Entity\Item
{

 
    public $product   ;
    
    protected function init() {
        $this->product = new ProductData() ;
        
      

        $this->product->novelty = 0; //новинка
        $this->product->sold = 0;   //кол продаж
        $this->product->topsold = 0; //топ продаж

        $this->product->rating = 0;  //рейтинг
        $this->product->comments = 0; //кол отзывов
        $this->product->attributevalues = array();
        $this->product->images = array();
        
        
    }

    protected function afterLoad() {
        parent::afterLoad();
        
        $this->product = @base64_decode(@unserialize($this->extdata)) ;
        if($this->product == null){
            $this->product = new ProductData() ;
        }
   
        
    }

    protected function beforeSave() {
         
        $this->extdata = base64_encode(serialize($this->product)) ;
        
        parent::beforeSave();
    }

    //перезеписываем значения атрибутов
    protected function afterSave($update) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from shop_attributevalues where  item_id=" . $this->item_id);
        foreach ($this->product->attributevalues as $key => $value) {
            //if ($value != null) {
            $conn->Execute("insert  into shop_attributevalues (attribute_id,item_id,attributevalue) values ({$key},{$this->item_id}," . $conn->qstr($value) . ")");
            // }
        }
        
        parent::afterSave($update) ;
    }

 

    public function afterDelete() {

        parent::afterDelete() ;
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from shop_attributevalues where  item_id=" . $this->item_id);
        
    }

    /**
     * Возвращает список аттрибутов  со значениями
     *
     */
    public function getAttrList() {
        $conn = \ZCL\DB\DB::getConnect();

        //$attrlist = ProductAttribute::find("group_id=" . $this->group_id);
        $attrlist = \App\Modules\Shop\Helper::getProductAttributeListByGroup($this->cat_id);

        $ret = array();
        $attrvalues = array();
        //выбираем значения атрибутов продукта
        $rows = $conn->Execute("select attribute_id,attributevalue from shop_attributevalues where  item_id=" . $this->item_id);
        foreach ($rows as $row) {
            $attrvalues[$row['attribute_id']] = $row['attributevalue'];
        }

        foreach ($attrlist as $attr) {
            $attr->value = @$attrvalues[$attr->attribute_id];
            if (strlen($attr->value) == 0) {
                $attr->nodata = true;
            }
            $ret[] = $attr;
        }

        return $ret;
    }

    /**
     * Возвращает  ЧПУ  строку.  Если  не  задана,   возвращвет id
     *
     */
    public function getSEF() {
        return strlen($this->product->sef) > 0 ? $this->product->sef : $this->item_id;
    }

    /**
     * Загружает товар   по  ЧПУ коду
     *
     */
    public static function loadSEF($sef) {
        return self::getFirst("item_id={$sef} or sef='{$sef}'");
    }


    public function getName() {
         if(strlen($this->product->name)>0)  return  $this->product->name;
         return $this->itemname;
    }
    public function getDescription() {
         if(strlen($this->product->desc)>0)  return  $this->product->desc;
         return $this->description;
    }
    public function getImages() {
          $im = array();
          if($this->image_id >0) {
            $im[]= $this->image_id ;
          }
         if(is_array($this->product->images))  {
             foreach($this->product->images as $img) {
                 if($img != $this->image_id)   $im[]= $img;  
             }
             
         }        
         return $im;
    }

}

/**
* Вспомагательный класс для  упаковки  данных
*/
class  ProductData  extends \App\DataItem
{
     public $attributevalues;
    public $images = array();
    
}