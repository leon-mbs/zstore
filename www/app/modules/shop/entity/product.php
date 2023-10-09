<?php

namespace App\Modules\Shop\Entity;

/**
 * Класс-сущность товар  онлайн каталога
 *
 * @table=items
 * @view=items_view
 * @keyfield=item_id
 */
class Product extends \App\Entity\Item
{
    public $productdata;

    protected function init() {
        parent::init() ;

        $this->productdata = new ProductData();

        $this->productdata->desc = '';

        $this->productdata->sold = 0;   //кол продаж

        $this->productdata->rating = 0;  //рейтинг
        $this->productdata->comments = 0; //кол отзывов
        $this->productdata->attributevalues = [];
        $this->productdata->images = [];

    }

    protected function afterLoad() {
        parent::afterLoad();
        if(strlen($this->extdata) >0)
        {    
            $this->productdata = @unserialize(@base64_decode($this->extdata));
        }
        else{
            $this->productdata = new ProductData();
        }
    }

    protected function beforeSave() {

        $this->extdata = base64_encode(serialize($this->productdata));

        parent::beforeSave();
    }

    //перезеписываем значения атрибутов
    protected function afterSave($update) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from shop_attributevalues where  item_id=" . $this->item_id);
        foreach ($this->productdata->attributevalues as $key => $value) {
            //if ($value != null) {
            $conn->Execute("insert  into shop_attributevalues (attribute_id,item_id,attributevalue) values ({$key},{$this->item_id}," . $conn->qstr($value) . ")");
            // }
        }

        parent::afterSave($update);
    }

    public function afterDelete() {

        parent::afterDelete();
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
            $attr->value = $attrvalues[$attr->attribute_id]  ?? '';
            if (strlen($attr->value) == 0) {
                // $attr->nodata = 1;
            }
            $ret[] = $attr;
        }

        return $ret;
    }

    //для сортировки
    public function getPriceFinal() {
        if ($this->actionprice > 0) {
            return $this->actionprice;
        } else {
            $options = \App\System::getOptions('shop');
            return $this->getPrice($options['defpricetype']);
        }
    }

    public function getRating() {
        $r = 0;
        if ($this->comments > 0) {
            return round($this->ratings / $this->comments);
        }
        return $r;
    }

    /**
     * Возвращает  ЧПУ  строку.  Если  не  задана,   возвращвет id
     *
     */
    public function getSEF() {
        return strlen($this->sef) > 0 ? $this->sef : '/sp/' . $this->item_id;
    }

    /**
     * Загружает товар   по  ЧПУ коду
     *
     */
    public static function loadSEF($sef) {
        $sef = trim($sef, '/');
        return self::getFirst("   detail like '%<sef>{$sef}</sef>%' ");
    }

    public function getDescription() {
        if (strlen($this->productdata->desc) > 0) {
            return $this->productdata->desc;
        }
        return $this->description;
    }

    public function getImages($includecover = false) {
        $im = array();
        if ($this->image_id > 0 && $includecover) {
            $im[] = $this->image_id;
        }
        if (is_array($this->productdata->images)) {
            foreach ($this->productdata->images as $img) {
                if ($img != $this->image_id) {
                    $im[] = $img;
                }
            }
        }
        return $im;
    }


    /**
    * возвращает  вариации
    *
    */
    public function getVarList($defpricetype) {
        $conn = \ZCL\DB\DB::getConnect();

        $var_id = $conn->GetOne("select coalesce(var_id,0) from shop_varitems where  item_id=".$this->item_id);
        if($var_id>0) {
            $items=array();

            foreach (VarItem::findYield("var_id=".$var_id) as $vi) {
                $prod = Product::load($vi->item_id) ;
                $vi->price = $prod->getPurePrice($defpricetype);
                $vi->actionprice = $prod->getActionPrice();
                $vi->hasaction = $prod->hasAction() ;

                $items[] = $vi;
            }

            $var = Variation::load($var_id) ;
            $this->vattrname =  $var->attributename;

            return $items;
        } else {
            return array();
        }
    }



}

/**
 * Вспомагательный класс для  упаковки  данных относящихся к  каталогу
 */
class ProductData extends \App\DataItem
{
    public $attributevalues = array();
    public $images          = array();

}
