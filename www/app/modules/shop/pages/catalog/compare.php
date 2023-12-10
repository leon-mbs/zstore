<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Modules\Shop\CompareList;
use App\Modules\Shop\Helper;
use App\Application as App;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Modules\Shop\Entity\ProductAttribute  ;

//страница  сравнения  товаров
class Compare extends Base
{
    public $_comparelist = [];

    public function __construct() {
        parent::__construct();
        $this->add(new  ClickLink('backtolist', $this, 'OnBack'));




        $this->add(new \Zippy\Html\DataList\DataView('plist', new \Zippy\Html\DataList\ArrayDataSource($this, '_comparelist'), $this, 'plistOnRow'));

        $this->update();

    }

    public function plistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new \Zippy\html\Image('pim'))->setUrl('/loadshopimage.php?id='.$item->image_id);
        $row->add(new Label("pname"))->setText($item->itemname) ;
        $row->add(new Label("pprice"))->setText(\App\Helper::fa($item->getPriceFinal()));
        $row->add(new ClickLink("pdel", $this, "onDel")) ;

    }

    public function update() {
        $this->_comparelist = CompareList::getCompareList()->list;

        $this->plist->Reload() ;

        $options = \App\System::getOptions('shop');
        $attrvalues=[];
        $nodata = "Н/Д";
        $yes = "Є";
        $no = "Немає";
        $cat_id = \App\Filter::getFilter("ProductCatalog")->cat_id;
        $attributes = Helper::getProductAttributeListByGroup($cat_id);
        foreach ($attributes as $attr) {
            $values = array();
            $diff = array();

            foreach ($this->_comparelist as $product) {
                $pattr = Helper::getAttributeValuesByProduct($product);
                $value="";

                if($pattr[$attr->attribute_id] instanceof ProductAttribute) {

                    $value = $pattr[$attr->attribute_id]->attributevalue ;

                    if ($attr->attributetype == 1) {
                        if ($attr->attributevalue == 0) {
                            $value = $no;
                        }
                        if ($attr->attributevalue == 1) {
                            $value = $yes;
                        }
                    }
                    if ($pattr[$attr->attribute_id]->hasData() == false) {
                        $value = $nodata;
                    }
                }
                if(strlen($value)==0) {
                    $value='Н/Д';
                }
                $values[]  = array('value'=>$value) ;
                $diff["-".$value]= "-".$value;

            }
            if(count(array_keys($diff))>1) { //есть различные
                $attrvalues[] = array('name'=>$attr->attributename,'values'=>$values)  ;
            }


        }
        //     sort($attrlist, SORT_NUMERIC);

        $this->_tvars['cattr']  = $attrvalues;

    }


    public function onDel($sender) {
        $item = $sender->getOwner()->getDataItem();

        $comparelist = CompareList::getCompareList();
        $comparelist->deleteProduct($item->item_id);
        if ($comparelist->isEmpty()) {
            $filter = \App\Filter::getFilter("ProductCatalog");

            App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Catalog", $filter->cat_id);
            return;
        }
        $this->update()  ;


    }
    public function OnBack($sender) {

        $filter = \App\Filter::getFilter("ProductCatalog");

        App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Catalog", $filter->cat_id);
    }

}
