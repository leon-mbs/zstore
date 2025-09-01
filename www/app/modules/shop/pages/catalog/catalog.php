<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Filter;
use App\Modules\Shop\Entity\Product;
use App\Modules\Shop\Helper;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
 
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class Catalog extends Base
{
    public $cat_id    = 0;
    public $_isfilter = false; //отфильтрованы  ли  данные
    public $_list     = array();

    public function __construct($id = 0) {
        parent::__construct();
        $id = intval($id);
        $options = \App\System::getOptions('shop');

        $this->cat_id = $id;

        $this->add(new BookmarkableLink("filterbtn"));
        $this->add(new Label("breadcrumb", Helper::getBreadScrumbs($id), true));

        $filter = Filter::getFilter("ProductCatalog");
        $filter->cat_id = $id;

        $this->add(new Form('sfilter'))->onSubmit($this, 'searchformOnSubmit');
        $this->sfilter->add(new ClickLink('sclear'))->onClick($this, 'onSClear');
        $this->sfilter->add(new ManufacturerList('mlist'));

        foreach (Helper::getManufacturers($this->cat_id) as $m) {
            $this->sfilter->mlist->AddCheckBox($m, false, $m);
        }
        $this->sfilter->add(new DataView('attrlist', new ArrayDataSource(Helper::getProductSearchAttributeListByGroup($this->cat_id)), $this, 'attrlistOnRow'))->Reload();

        if ($id > 0 && $filter->cat_id != $id) {
            $filter->clean(); //переключена  группа
            $filter->cat_id = $id;
        }

        $this->add(new Form('sortform'));
        $this->sortform->add(new DropDownChoice('sortorder', 5))->onChange($this, 'onSort');

        $this->add(new DataView('productlist', new ArrayDataSource($this, '_list'), $this, 'plistOnRow'));
        $this->add(new \Zippy\Html\DataList\Pager('pag', $this->productlist));
        $this->productlist->setPageSize(24);
        if($options['pagesize'] >0) {
            $this->productlist->setPageSize($options['pagesize']);
        }

        $this->UpdateList();

        //недавно  просмотренные
        $ra = array();
        $recently = \App\Session::getSession()->recently;
        if (is_array($recently)) {
            $recently = array_reverse($recently);
            foreach ($recently as $r) {
                $ra[] = $r;
                if (count($ra) >= 4) {
                    break;
                }
            }
        }
        $this->add(new Panel("recentlyp"))->setVisible(count($ra) > 0);
        $this->recentlyp->add(new DataView('rlist', new EntityDataSource("\\App\\Modules\\Shop\\Entity\\Product", "  item_id in (" . implode(",", $ra) . ")"), $this, 'rOnRow'));


        if (count($ra) > 0) {
            $this->recentlyp->rlist->Reload();
        }

        $this->_tvars['fcolor'] = "class=\"btn btn-success\"";

    }

    private function UpdateList() {
        $options = \App\System::getOptions('shop');
        $conn = \ZDB\DB::getConnect();

        $this->_list = array();

        $fields = "items_view.*";
        $fields .= ",coalesce((  select     count(0)   from  shop_prod_comments c   where     c.item_id = items_view.item_id ),0) AS comments";
        $fields .= ",coalesce((  select     sum(r.rating)   from  shop_prod_comments r   where    r.item_id = items_view.item_id),0) AS ratings";
        $store = "";
        if (( $options['defstore'] ?? 0 ) > 0) {
            $store = " s.store_id={$options['defstore']}  and ";
        }
        $fields .= ",coalesce((  select     sum(s.qty)   from  store_stock s  where  {$store}  s.item_id = items_view.item_id) ,0) AS qty";
        $fields .= ",coalesce((  select     sum(0-e.quantity)   from  entrylist_view e     where   e.quantity < 0 and  e.item_id = items_view.item_id),0) AS sold";

        $where = "cat_id = {$this->cat_id} and disabled <> 1 and detail  not  like '%<noshop>1</noshop>%' ";

        $mlist = $this->sfilter->mlist->getCheckedList();
        if (count($mlist) > 0) {
            $_mlist = array();
            foreach ($mlist as $m) {
                $_mlist[] = $conn->qstr($m);
            }


            $where .= " and manufacturer in (" . implode(",", $_mlist) . ") ";
        }
        if ($this->_isfilter == true) {

            $ar = $this->sfilter->attrlist->getDataRows();
            foreach ($ar as $r) {
                $attr = $r->getComponent("attrdata");
                if (count($attr->value) > 0) {

                    $ar = $attr->value;
                    if (count($ar) > 0) {

                        $where .= " and  item_id in(select item_id  from  shop_attributevalues   where   attribute_id = " . $attr->productattribute->attribute_id;

                        $where .= " and (1=2 ";
                        foreach ($ar as $arv) {
                            $where .= " or attributevalue like " . $conn->qstr("%{$arv}%");
                        }

                        $where .= ")";
                    }


                    $where .= " )";
                }
            }
        } //filtered

        //не  в вариациях


        $wherenovar = $where ." and  item_id  not in(select item_id from shop_varitems)     ";

        foreach (Product::findYield($wherenovar, 'itemname', -1, -1, $fields) as $prod) {
            if($options['noshowempty'] == 1  && $prod->qty <= 0) {
                continue;
            }

            $prod->price = $prod->getPrice($options['defpricetype']);

            $this->_list[] = $prod;
        }

        $sql   = "select min(item_id) from shop_varitems where item_id in (select item_id from items where {$where} ) group by var_id" ;

        $ids= $conn->GetCol($sql);

        if(count($ids)>0) {
            foreach (Product::findYield("item_id in(". implode(',', $ids)  ."  )", 'itemname', -1, -1, $fields) as $prod) {
                $prod->price = $prod->getPrice($options['defpricetype']);

                $this->_list[] = $prod;
            }

        }



        $sort = $this->sortform->sortorder->getValue();

        if ($sort == 0) {
            //  $order = "price asc";
            usort($this->_list, function ($a, $b) {
                return $a->getPriceFinal() > $b->getPriceFinal();
            });
        }
        if ($sort == 1) {
            // $order = "price desc";
            usort($this->_list, function ($a, $b) {
                return $a->getPriceFinal() < $b->getPriceFinal();
            });
        }
        if ($sort == 2) {
            //  $order = "rating desc";
            usort($this->_list, function ($a, $b) {
                return $a->getRating() < $b->getRating();
            });
        }
        if ($sort == 3) {
            //  $order = "comments desc";
            usort($this->_list, function ($a, $b) {
                return $a->comments < $b->comments;
            });
        }

        if ($sort == 4) {
            // $order = "sold desc";
            usort($this->_list, function ($a, $b) {
                return $a->sold < $b->sold;
            });
        }
        if ($sort == -1) {
            // $order = "productname";
            usort($this->_list, function ($a, $b) {
                return $a->itemname > $b->itemname;
            });
        }

        $this->productlist->Reload();
    }

    //строка товара
    public function plistOnRow($row) {
        $item = $row->getDataItem();
        $options = \App\System::getOptions('shop');

        $row->add(new BookmarkableLink("simage", $item->getSEF()))->setValue(  $item->getImageUrl(true,true));
        $row->add(new BookmarkableLink("scatname", $item->getSEF()))->setValue($item->itemname);
        $price = $item->getPurePrice($options['defpricetype']);
        $price = \App\Helper::fa($price);
        $row->add(new Label("sprice", $price . ' ' . $options['currencyname']));
    
        
        $row->add(new Label("scustomsize", $item->customsize  ));
        $row->add(new Label("sactionprice", \App\Helper::fa($item->getActionPrice()). ' ' . $options['currencyname']))->setVisible(false);
        $row->add(new Label('saction'))->setVisible(false);

        if ($item->hasAction()) {
            $row->sprice->setAttribute('style', 'font-size:smaller;text-decoration:line-through');
            $row->sactionprice->setVisible(true);
            $row->saction->setVisible(true);
        }

        $row->add(new TextInput('srated'))->setText($item->getRating());
        $row->add(new Label('scomments'))->setText("Відгуків (".$item->comments.")");
        $row->add(new ClickLink('sbuy', $this, 'OnBuy'));

        /*        if ($item->getQuantity() > 0 || $this->_tvars["isfood"]==true) {

                    // $row->sbuy->setValue('Купити');
                } else {
                    //  $row->sbuy->setValue('Замовити');
                }


                $op = \App\System::getOptions("shop");

                if ($item->getQuantity($op['defstore']) > 0) {
                    //  $row->sbuy->setValue('Купити');
                } else {
                    //  $row->sbuy->setValue('Замовити');
                }
                */
    }

    public function oncartdel($sender) {
        $item = $sender->getOwner()->getDataItem();
        \App\Modules\Shop\Basket::getBasket()->deleteProduct($item->item_id);
    }

    public function oncompdel($sender) {
        $item = $sender->getOwner()->getDataItem();
        \App\Modules\Shop\CompareList::getCompareList()->deleteProduct($item->item_id);
    }

    public function rOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new BookmarkableLink("rimage", $item->getSEF()))->setValue(  $item->getImageUrl(true,true));
        $row->add(new BookmarkableLink("rname", $item->getSEF()))->setValue($item->itemname);
    }

    public function OnBuy($sender) {

        $product = $sender->getOwner()->getDataItem();
        $product->quantity = 1;
        \App\Modules\Shop\Basket::getBasket()->addProduct($product);

        $this->setSuccess("Товар доданий до кошика");

        $this->resetURL();
    }

    public function searchformOnSubmit($sender) {
        $this->_isfilter = true;
        $this->filterbtn->setAttribute('class', 'btn btn-danger');

        $this->UpdateList();
    }

    public function onSClear($sender) {
        $this->sfilter->clean();
        $this->_isfilter = false;

        $this->filterbtn->setAttribute('class', 'btn btn-success');
        $this->UpdateList();
    }

    public function onSort($sender) {

        $this->UpdateList();
    }

    //строка  атрибута
    public function attrlistOnRow($row) {
        $attr = $row->getDataItem();

        $row->add(new FilterAttributeComponent('attrdata', $attr));
    }

}

//компонент атрибута  товара для фильтра
//выводит  элементы  формы  ввода   в  зависимости  от  типа  атрибута
class FilterAttributeComponent extends \Zippy\Html\CustomComponent implements \Zippy\Interfaces\SubmitDataRequest
{
    public $productattribute = null;
    public $value            = array();

    public function __construct($id, $productattribute) {
        parent::__construct($id);
        $this->productattribute = $productattribute;
    }

    public function getContent($attributes) {
        $name = $this->productattribute->attributename;
        if ($this->productattribute->attributetype == 2) {
            $name = $name . ", " . $this->productattribute->valueslist;
        }

        //   $ret = "<a href=\"#a{$this->productattribute->attribute_id}\" data-toggle=\"collapse\"  class=\"filtertiem\" >{$name} <i style=\"font-size:smaller\" class=\"fa fa-angle-down\"></i></a>";
        //   $ret .= "<div id=\"a{$this->productattribute->attribute_id}\" class=\"collapse\" >";
        $ret = "<b>{$name}</b>";
        //'Есть/Нет'
        if ($this->productattribute->attributetype == 1) {

            $ret .= "<div class=\"checkbox\">";
            $checked = "";
            if (in_array("1", $this->value)) {
                $checked = ' checked="on"';
            }
            $ret .= "<input type=\"checkbox\"  name=\"{$this->id}[]\" value=\"1\" {$checked} /> Є<br>";
            $checked = "";
            if (in_array("0", $this->value)) {
                $checked = ' checked="on"';
            }

            $ret .= "<input type=\"checkbox\"  name=\"{$this->id}[]\" value=\"0\"  {$checked} /> Нема ";
            $ret .= "</div>";
        }
        //'Число'
        if ($this->productattribute->attributetype == 2) {
            $filter = Filter::getFilter("ProductCatalog");
            $list = Helper::getAttrValues($filter->cat_id, $this->productattribute->attribute_id);

            $ret .= "<div class=\"checkbox\">";

            foreach ($list as $value) {
                $ret .= "<label>";

                if (in_array($value, $this->value)) {
                    $checked = ' checked="on"';
                } else {
                    $checked = "";
                }

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
                if (in_array($value, $this->value)) {
                    $checked = ' checked="on"';
                } else {
                    $checked = "";
                }

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

                if (in_array($value, $this->value)) {
                    $checked = ' checked="on"';
                } else {
                    $checked = "";
                }

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

        // return $ret . "</div>";
        return $ret;
    }

    //Вынимаем данные формы  после  сабмита
    public function getRequestData() {
        $this->value = array();

        if(is_array(@$_POST[$this->id])) {
            $this->value = array_values($_POST[$this->id]);
        }

        if (!is_array($this->value)) {
            $this->value = array();
        }
    }

    public function clean() {
        $this->value = array();
    }

}

class ManufacturerList extends \Zippy\Html\Form\CheckBoxList
{
    public function RenderItem($name, $checked, $caption = "", $attr = "", $delimiter = "") {
        return " 
   
    <div class=\"form-check\"   >
        
        <input class=\"form-check-input\"   type=\"checkbox\" name=\"{$name}\" {$attr} {$checked}    >
        <label class=\"form-check-label mr-sm-2\"   >{$caption}</label>
    </div>     
     
     
     ";
    }

}
