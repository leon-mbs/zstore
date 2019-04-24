<?php

namespace App\Shop\Pages;

use \App\Shop\Comparelist;
use \App\Shop\Helper;
use \App\System\System;
use \Zippy\WebApplication as App;

//страница  сравнения  товаров
class Compare extends Base {

    public function __construct() {
        parent::__construct();

        $this->add(new CompareGrid('comparegrid'));
    }

    protected function beforeRender() {
        parent::beforeRender();
        $comparelist = Comparelist::getCompareList()->list;
    }

}

//класс  формирующий  таблицу  сравнения
class CompareGrid extends \Zippy\Html\CustomComponent implements \Zippy\Interfaces\Requestable {

    public function getContent($attributes) {
        $result = "<table class=\"table table-stripped table-responsive  \" >";
        $comparelist = Comparelist::getCompareList();
        $attrlist = array();
        $attrnames = array();
        $attrvalues = array();

        $result .= "<tr><th> </th>";
        $url = $this->owner->getURLNode() . "::" . $this->id;
        ///цикл  по товарам
        foreach ($comparelist->list as $product) {

            $result .= ( "<th ><img style=\"height:128px\" src=\"/loadimage.php?id={$product->image_id}&t=t\"><br><a href=\"/sp/{$product->product_id}\">" . $product->productname . "</a> <a href=\"{$url}:{$product->product_id}\"><i class=\"fa fa-remove\" ></a></th>");
            $attributes = Helper::getAttributeValuesByProduct($product);
            //цикл по  атрибутам для  получения значений

            foreach ($attributes as $attr) {
                $value = $attr->attributevalue;
                if (false == in_array($attr->attribute_id, $attrlist)) {
                    $attrlist[] = $attr->attribute_id;
                }
                $attrnames[$attr->attribute_id] = $attr->attributename;
                if ($attr->attributetype == 2) {
                    $attrnames[$attr->attribute_id] = $attr->attributename . ',' . $attr->valueslist;
                }

                if ($attr->attributetype == 1) {
                    $value = $attr->attributevalue == 1 ? "Ecть" : "Нет";
                }
                if ($attr->attributevalue == '')
                    $value = "Н/Д";

                $attrvalues[$attr->attribute_id][$product->product_id] = $value;
            }
        }
        $result .= "</tr>";
        sort($attrlist, SORT_NUMERIC);
        $i = 0;
        //вывод атрибутов по  строкам
        foreach ($attrlist as $attribute_id) {

            $result .= ( "<tr ><td style=\"font-weight:bolder;\">" . $attrnames[$attribute_id] . "</td>");
            foreach ($comparelist->list as $product) {
                $result .= ( "<td>" . $attrvalues[$attribute_id][$product->product_id] . "</td>");
            }
            $result .= "</tr>";
            $i++;
        }

        return $result . "</table>";
    }

    //удаление  из  списка
    public function RequestHandle() {
        $params = App::$app->getRequest()->request_params[$this->id];

        //$params = array_keys($params);
        $comparelist = Comparelist::getCompareList();
        $comparelist->deleteProduct($params[0]);
        if ($comparelist->isEmpty()) {
            $filter = \App\Filter::getFilter("ProductCatalog");
            App::Redirect("\\App\\Shop\\Pages\\Catalog", $filter->group_id);
        }
    }

}
