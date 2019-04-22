<?php

namespace App\Shop\Pages;

use \Carbon\Carbon;
use \Zippy\Html\DataList\ArrayDataSource;
use \ZCL\DB\EntityDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Label;
use \Zippy\Html\Panel;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\BookmarkableLink;
use \App\Shop\Entity\ProductGroup;
use \App\Shop\Entity\Product;
use \App\Shop\Helper;
use \App\Filter;

class Catalog extends Base {

    public $group_id = 0;

    public function __construct($id) {
        parent::__construct();

        $this->group_id = $id;

        $this->add(new Label("breadcrumb", Helper::getBreadScrumbs($id), true));

        $filter = Filter::getFilter("ProductCatalog");
        $filter->group_id = $id;

        $this->add(new Form('sfilter'))->onSubmit($this, 'searchformOnSubmit');
        $this->sfilter->add(new ClickLink('sclear'))->onClick($this, 'onSClear');
        $this->sfilter->add(new ManufacturerList('mlist'));

        foreach (\App\Shop\Entity\Manufacturer::find("manufacturer_id in(select manufacturer_id from shop_products where deleted <> 1 and group_id={$this->group_id})", "manufacturername") as $key => $value) {
            $this->sfilter->mlist->AddCheckBox($key, false, $value->manufacturername);
        }
        $this->sfilter->add(new DataView('attrlist', new ArrayDataSource(Helper::getProductSearchAttributeListByGroup($this->group_id)), $this, 'attrlistOnRow'))->Reload();

        $pr = Helper::getPriceRange($this->group_id);
        $this->sfilter->add(new TextInput('pricefrom'))->setText(floor($pr["minp"]));
        $this->sfilter->add(new TextInput('priceto'))->setText(ceil($pr["maxp"]));
        ;

        $this->sfilter->add(new TextInput('searchkey'));

        if ($id > 0 && $filter->group_id != $id) {
            $filter->clean(); //переключена  группа
            $filter->group_id = $id;
        } else {
            
        }

        $this->add(new Form('sortform'));
        $this->sortform->add(new DropDownChoice('sortorder'))->onChange($this, 'onSort');


        $this->add(new DataView('catlist', new CatDataSource($this), $this, 'plistOnRow'));
        $this->add(new \Zippy\Html\DataList\Paginator('pag', $this->catlist));
        $this->catlist->setPageSize(15);
        $this->catlist->Reload();


 

        //недавно  просмотренные
        $ra = array();
        $recently = \App\Session::getSession()->recently;
        if (is_array($recently)) {
            $recently = array_reverse($recently);
            foreach ($recently as $r) {
                $ra[] = $r;
                if (count($ra) >= 4)
                    break;
            }
        }
        $this->add(new Panel("recentlyp"))->setVisible(count($ra) > 0);
        $this->recentlyp->add(new DataView('rlist', new EntityDataSource("\\App\\Shop\\Entity\\Product", "  product_id in (" . implode(",", $ra) . ")"), $this, 'rOnRow'));
        if (count($ra) > 0) {
            $this->recentlyp->rlist->Reload();
        }
    }

    //строка товара
    public function plistOnRow($row) {
        $item = $row->getDataItem();
 
        
        $row->add(new BookmarkableLink("simage", "/sp/" . $item->product_id))->setValue('/loadimage.php?id=' . $item->image_id . "&t=t");
        $row->add(new BookmarkableLink("scatname", "/sp/" . $item->product_id))->setValue($item->productname);
        $row->add(new Label("stopsold"))->setVisible($item->topsold == 1);
        $row->add(new Label("snovelty"))->setVisible($item->novelty == 1);
        $row->add(new Label("sshortdesc", $item->description));
        $row->add(new Label("sprice",  $item->price));
        $row->add(new TextInput('srated'))->setText($item->rating);
        $row->add(new ClickLink('sbuy', $this, 'OnBuy'));
        if ($item->qty > 0) {
            $row->sbuy->setValue("Купить");
        } else {
            $row->sbuy->setValue("Заказать");
        }
        $row->add(new Label('arrowup' ))->setVisible($item->chprice == 'up');
        $row->add(new Label('arrowdown' ))->setVisible($item->chprice == 'down');        
    }

 
    public function oncartdel($sender) {
        $item = $sender->getOwner()->getDataItem();
        \App\Shop\Basket::getBasket()->deleteProduct($item->product_id);
    }

    public function oncompdel($sender) {
        $item = $sender->getOwner()->getDataItem();
        \App\Shop\CompareList::getCompareList()->deleteProduct($item->product_id);
    }

    public function rOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new BookmarkableLink("rimage", "/sp/" . $item->product_id))->setValue('/loadimage.php?id=' . $item->image_id . "&t=t");
        $row->add(new BookmarkableLink("rname", "/sp/" . $item->product_id))->setValue($item->productname);
    }

    public function onSClear($sender) {
        $this->sfilter->clean();
        $pr = Helper::getPriceRange($this->group_id);
        $this->sfilter->pricefrom->setText(floor($pr["minp"] / 100));
        $this->sfilter->priceto->setText(ceil($pr["maxp"] / 100));
        ;

        $this->catlist->Reload();
    }

    public function OnBuy($sender) {

        $product = $sender->getOwner()->getDataItem();
        $product->quantity = 1;
        \App\Shop\Basket::getBasket()->addProduct($product);
        $this->setSuccess("Товар  добавлен  в   корзину");
      
        $this->resetURL();
    }

    public function searchformOnSubmit($sender) {

        $this->catlist->Reload();
    }

    public function onSort($sender) {

        $this->catlist->Reload();
    }

//строка  атрибута
    public function attrlistOnRow($row) {
        $attr = $row->getDataItem();


        $row->add(new FilterAttributeComponent('attrdata', $attr));
    }

  

}

class CatDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        // $filter = Filter::getFilter("ProductCatalog");

        $conn = \ZDB\DB::getConnect();
        $form = $this->page->sfilter;
        $where = "deleted <> 1 and  group_id = " . $this->page->group_id;
        $where .= " and price >= " . $form->pricefrom->getText();
        $where .= " and price <= " . $form->priceto->getText();
        $sk = $form->searchkey->getText();
        if (strlen(trim($sk)) > 0) {
            $where .= " and productname like " . $conn->qstr("%{$sk}%");
        }
        $mlist = $form->mlist->getCheckedList();
        if (count($mlist) > 0) {
            $where .= " and manufacturer_id in (" . implode(",", $mlist) . ")";
        }
        $ar = $form->attrlist->getDataRows();
        foreach ($ar as $r) {
            $attr = $r->getComponent("attrdata");
            if (count($attr->value) > 0) {

                $ar = $attr->value;
                if (count($ar) > 0) {

                    $where .= " and  product_id in(select product_id  from  shop_attributevalues   where   attribute_id = " . $attr->productattribute->attribute_id;


                    $where .= " and (1=2 ";
                    foreach ($ar as $arv) {
                        $where .= " or attributevalue like " . $conn->qstr("%{$arv}%");
                    }

                    $where .= ")";
                }


                $where .= " )";
            }
        }
        //$logger->info($where);
        return $where;
    }

    public function getItemCount() {
        return Product::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $order = "productname";
        $sort = $this->page->sortform->sortorder->getValue();
        if ($sort == 0)
            $order = "price asc";
        if ($sort == 1)
            $order = "price desc";
        if ($sort == 2)
            $order = "rating desc";
        if ($sort == 3)
            $order = "comments desc";
        if ($sort == 4)
            $order = "sold desc";
        if ($sort == 5)
            $order = "productname";


        return Product::find($this->getWhere(), $order, $count, $start);
    }

}

//компонент атрибута  товара для фильтра
//выводит  элементы  формы  ввода   в  зависимости  от  типа  атрибута
class FilterAttributeComponent extends \Zippy\Html\CustomComponent implements \Zippy\Interfaces\SubmitDataRequest {

    public $productattribute = null;
    public $value = array();

    public function __construct($id, $productattribute) {
        parent::__construct($id);
        $this->productattribute = $productattribute;
    }

    public function getContent($attributes) {
        $name = $this->productattribute->attributename;
        if ($this->productattribute->attributetype == 2) {
            $name = $name . ", " . $this->productattribute->valueslist;
        }

        $ret = "<a href=\"#a{$this->productattribute->attribute_id}\" data-toggle=\"collapse\"  class=\"filtertiem\" >{$name} <span class=\"caret\"></span></a>";
        $ret .= "<div id=\"a{$this->productattribute->attribute_id}\" class=\"collapse\" >";

        //'Есть/Нет'
        if ($this->productattribute->attributetype == 1) {

            $ret .= "<div class=\"checkbox\">";
            $checked = "";
            if (in_array("1", $this->value)) {
                $checked = ' checked="on"';
            }
            $ret .= "<label><input type=\"checkbox\"  name=\"{$this->id}[]\" value=\"1\" {$checked} /> Есть</label><br>";
            $checked = "";
            if (in_array("0", $this->value)) {
                $checked = ' checked="on"';
            }

            $ret .= "<label><input type=\"checkbox\"  name=\"{$this->id}[]\" value=\"0\"  {$checked} /> Нет</label>";
            $ret .= "</div>";
        }
        //'Число'
        if ($this->productattribute->attributetype == 2) {
            $filter = Filter::getFilter("ProductCatalog");
            $list = Helper::getAttrValues($filter->group_id, $this->productattribute->attribute_id);

            $ret .= "<div class=\"checkbox\">";


            foreach ($list as $value) {
                $ret .= "<label>";

                if (in_array($value, $this->value))
                    $checked = ' checked="on"';
                else
                    $checked = "";

                $name = $this->id;
                $ret = $ret . "<input name=\"{$name}[]\" type=\"checkbox\" value=\"{$value}\"  {$checked}> {$value}";
                $ret .= "</label><br>";
            }
            $ret .= "</div>";
        }
        //'Список'
        if ($this->productattribute->attributetype == 3) {

            $ret .= "<div class=\"checkbox\">";
            $list = explode(',', $this->productattribute->valueslist);

            foreach ($list as $value) {
                $ret .= "<label>";
                if (in_array($value, $this->value))
                    $checked = ' checked="on"';
                else
                    $checked = "";

                $name = $this->id;
                $ret = $ret . "<input name=\"{$name}[]\" type=\"checkbox\"  value=\"{$value}\"  {$checked}> {$value}";
                $ret .= "</label><br>";
            }
            $ret .= "</div>";
        }
        //'Набор'
        if ($this->productattribute->attributetype == 4) {

            $ret .= "<div class=\"checkbox\">";


            $list = explode(',', $this->productattribute->valueslist);

            foreach ($list as $value) {
                $ret .= "<label>";

                if (in_array($value, $this->value))
                    $checked = ' checked="on"';
                else
                    $checked = "";

                $name = $this->id;
                $ret = $ret . "<input name=\"{$name}[]\" type=\"checkbox\"  value=\"{$value}\"  {$checked}> {$value}";
                $ret .= "</label><br>";
            }

            $ret .= "</div>";
        }

        //'Строка'
        /* if ($this->productattribute->attributetype == 5) {
          $ret .= "<div class=\"form-group\">";
          $ret .= "<textarea name=\"{$this->id}\" type=\"text\"  cols=\"20\" rows=\"3\"   class=\"form-control\" >{$this->productattribute->value}</textarea> ";
          $ret .= "</div>";
          } */

        return $ret . "</div>";
    }

    //Вынимаем данные формы  после  сабмита
    public function getRequestData() {

        $this->value = @array_values($_POST[$this->id]);
        if (!is_array($this->value))
            $this->value = array();
    }

    public function clean() {
        $this->value = array();
    }

}

class ManufacturerList extends \Zippy\Html\Form\CheckBoxList {

    public function RenderItem($name, $checked, $caption = "", $attr = "", $delimiter = "") {
        return " 
   
    <div class=\"form-check\"   >
        
        <input class=\"form-check-input\"   type=\"checkbox\" name=\"{$name}\" {$attr} {$checked}    >
        <label class=\"form-check-label mr-sm-2\"   >{$caption}</label>
    </div>     
     
     
     ";
    }

}
