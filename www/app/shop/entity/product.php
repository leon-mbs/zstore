<?php

namespace App\Shop\Entity;

//класс-сущность  товара
/**
 * @keyfield=product_id
 * @table=shop_products
 * @view=shop_products_view
 */
class Product extends \ZCL\DB\Entity {

    public $attributevalues;
    public $images= array();

    protected function init() {
        $this->product_id = 0;
        $this->item_id = 0;

        $this->image_id = 0;
        $this->group_id = 0;
        $this->price = 0;
     
        $this->novelty = 0; //новинка
        $this->sold = 0;   //кол продаж
        $this->topsold = 0; //топ продаж

        $this->rating = 0;  //рейтинг
        $this->comments = 0; //кол отзывов
        $this->attributevalues = array();
        $this->images = array();
        $this->created = time();
    }

    protected function afterLoad() {

        $xml = @simplexml_load_string($this->detail);

        $this->item_id = (int) ($xml->item_id[0]);
        $this->image_id = (int) ($xml->image_id[0]);
        $images = (string) ($xml->images[0]);
        $this->topsold = (int) ($xml->topsold[0]);
        
        $this->item_code = (string) ($xml->item_code[0]);
        $this->chprice = (string) ($xml->chprice[0]);
        $this->description = (string) ($xml->description[0]);
        $this->fulldescription = (string) ($xml->fulldescription[0]);
        $this->aboutus = (string) ($xml->aboutus[0]);
        $this->contact = (string) ($xml->contact[0]);
        $this->delivery = (string) ($xml->delivery[0]);

        $this->rating = round($this->rating);
        $this->created = strtotime($this->created);

        if (strlen($images) > 0) {
            $this->images = explode(',', $images);
        }

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<item_id>{$this->item_id}</item_id>";
        $this->detail .= "<image_id>{$this->image_id}</image_id>";
        $this->detail .= "<images>" . implode(',', $this->images) . "</images>";
        $this->detail .= "<topsold>{$this->topsold}</topsold>";
        
        $this->detail .= "<item_code>{$this->item_code}</item_code>";
        $this->detail .= "<chprice>{$this->chprice}</chprice>";
        $this->detail .= "<description><![CDATA[{$this->description}]]></description>";
        $this->detail .= "<fulldescription><![CDATA[{$this->fulldescription}]]></fulldescription>";
        $this->detail .= "<aboutus><![CDATA[{$this->aboutus}]]></aboutus>";
        $this->detail .= "<contact><![CDATA[{$this->contact}]]></contact>";
        $this->detail .= "<delivery><![CDATA[{$this->delivery}]]></delivery>";

        $this->detail .= "</detail>";

        return true;
    }

    //перезеписываем значения атрибутов
    protected function afterSave($update) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from shop_attributevalues where  product_id=" . $this->product_id);
        foreach ($this->attributevalues as $key => $value) {
            //if ($value != null) {
            $conn->Execute("insert  into shop_attributevalues (attribute_id,product_id,attributevalue) values ({$key},{$this->product_id}," . $conn->qstr($value) . ")");
            // }
        }
    }

    protected function beforeDelete() {

        if (false == $this->checkDelete())
            return false;

        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from shop_attributevalues where  product_id=" . $this->product_id);
        \App\Entity\Image::delete($this->image_id);
    }

    public function checkDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from   documents_view where  meta_name='Order' and content like '%<product_id>{$this->product_id}</product_id>%' ";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? false : true;
    }

    /**
     * Возвращает список аттрибутов  со значениями
     * 
     */
    public function getAttrList() {
        $conn = \ZCL\DB\DB::getConnect();

        //$attrlist = ProductAttribute::find("group_id=" . $this->group_id);
        $attrlist = \App\Shop\Helper::getProductAttributeListByGroup($this->group_id);

        $ret = array();
        $attrvalues = array();
        //выбираем значения атриутов продукта
        $rows = $conn->Execute("select attribute_id,attributevalue from shop_attributevalues where  product_id=" . $this->product_id);
        foreach ($rows as $row) {
            $attrvalues[$row['attribute_id']] = $row['attributevalue'];
        }

        foreach ($attrlist as $attr) {
            $attr->value = @$attrvalues[$attr->attribute_id];
            if (strlen($attr->value) == 0)
                $attr->nodata = true;
            $ret[] = $attr;
        }

        return $ret;
    }

    /**
     * Возвращает  ЧПУ  строку.  Если  не  задана,   возвращвет id
     * 
     */
    public function getSEF() {
        return strlen($this->sef) > 0 ? $this->sef : $this->product_id;
    }

    /**
     * Загружает товар   по  ЧПУ коду
     * 
     */
    public static function loadSEF($sef) {
        return self::findFirst("product_id={$sef} or sef='{$sef}'");
    }

}
