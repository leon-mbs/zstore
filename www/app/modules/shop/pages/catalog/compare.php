<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Modules\Shop\Comparelist;
use App\Modules\Shop\Helper;
use \App\Application as App;

//страница  сравнения  товаров
class Compare extends Base
{

    public function __construct() {
        parent::__construct();
        $this->add(new \Zippy\Html\Link\ClickLink('backtolist', $this, 'OnBack'));

        $this->add(new CompareGrid('comparegrid'));
    }

    protected function beforeRender() {
        parent::beforeRender();
        $comparelist = Comparelist::getCompareList()->list;
    }

    public function OnBack($sender) {

        $filter = \App\Filter::getFilter("ProductCatalog");

        App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Catalog", $filter->cat_id);
    }

}

//класс  формирующий  таблицу  сравнения
class CompareGrid extends \Zippy\Html\CustomComponent implements \Zippy\Interfaces\Requestable
{

    public function getContent($attributes) {
        $result = "<table     class=\"comparetable\" >";
        $comparelist = Comparelist::getCompareList();
        $attrlist = array();
        $attrnames = array();
        $attrvalues = array();
        $options = \App\System::getOptions('shop');

        $nodata = \App\Helper::l("shopattrnodata");
        $yes = \App\Helper::l("shopattryes");
        $no = \App\Helper::l("shopattrno");

        $result .= "<tr><th></th>";
        $url = $this->owner->getURLNode() . "::" . $this->id;
        ///цикл  по товарам
        foreach ($comparelist->list as $product) {

            $result .= (" <th ><img class=\"compareimage\" src=\"/loadshopimage.php?id={$product->image_id}&t=t\"><br><br><a class=\"mt-2 text-dark\" href=\"/sp/{$product->item_id}\">" . $product->itemname . "</a> <br>   <b>" .  \App\Helper::fa( $product->getPriceFinal() ). ' ' . $options['currencyname'] . "</b>     &nbsp;  &nbsp;  &nbsp;  &nbsp;     <a href=\"{$url}:{$product->item_id}\"><i class=\"fa fa-trash text-danger\" ></i></a></th>");
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
                    if ($attr->attributevalue == 0) {
                        $value = $no;
                    }
                    if ($attr->attributevalue == 1) {
                        $value = $yes;
                    }
                }
                if ($attr->hasData() == false) {
                    $value = $nodata;
                }

                $attrvalues[$attr->attribute_id][$product->item_id] = $value;
            }
        }
        $result .= "</tr>";
        sort($attrlist, SORT_NUMERIC);
        $i = 0;
        //вывод атрибутов по  строкам
        foreach ($attrlist as $attribute_id) {

            $result .= ("<tr ><td style=\"font-weight:bolder;\">" . $attrnames[$attribute_id] . "</td>");
            foreach ($comparelist->list as $product) {
                $result .= ("<td>" . $attrvalues[$attribute_id][$product->item_id] . "</td>");
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

            App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Catalog", $filter->cat_id);
        }
    }

}
