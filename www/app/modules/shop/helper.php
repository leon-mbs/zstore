<?php

namespace App\Modules\Shop;

use App\Modules\Shop\Entity\Product;
use App\Modules\Shop\Entity\ProductAttribute;
use App\Entity\Category;
use ZCL\DB\DB;

//класс  вспомагательных функций
class Helper
{

    public static function getBreadScrumbs($id) {

        $bs = "<li class=\"breadcrumb-item\"><a href='/shop'>Каталог</a></li>";
        if ($id > 0) {
            $g = Category::load($id);
            $gl = $g->getParents();
            $gl = array_reverse($gl);
            $all = Category::find('');
            foreach ($gl as $cat_id) {
                $c = $all[$cat_id];
                $bs .= "<li class=\"breadcrumb-item\" ><a href='/scat/{$cat_id}'>{$c->cat_name}</a></li>";
            }
            $bs .= "<li class=\"breadcrumb-item active\">{$g->cat_name}</li>";
        }
        return $bs;
    }

    //список  производителей в  данной группе  товаров 
    public static function getManufacturers($cat_id = 0) {
        $cat = '';
        if ($cat_id > 0) {
            $cat = " cat_id={$cat_id} and ";
        }
        $list = array();
        $conn = DB::getConnect();
        $sql = " select manufacturer  from  items  where {$cat} disabled <> 1 order  by manufacturer ";
        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            if (strlen($row["manufacturer"]) > 0) {
                $list[] = $row["manufacturer"];
            }
        }

        return $list;
    }

    /**
     * список  значений атрибутов  товара
     *
     * @param mixed $product
     */
    public static function getAttributeValuesByProduct($product, $all = true) {
        $list = array();
        $conn = DB::getConnect();
        $sql = "select v.attribute_id ,a.attributename,a.attributetype,a.valueslist,a.valueslist,v.attributevalue  from  shop_attributes a  join shop_attributevalues v on a.attribute_id = v.attribute_id where v.item_id=  " . $product->item_id;

        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $prod = new ProductAttribute($row);
            if ($all == false && $prod->hasData() == false) {
                continue;
            }

            $list[$row['attribute_id']] = $prod;
        }
        return $list;
    }

    /**
     * Возвращает  список  атрибутов  для  группы
     *
     * @param mixed $cat_id группа
     */
    public static function getProductAttributeListByGroup($cat_id) {
        $list = array();

        $conn = DB::getConnect();

        $group = \App\Entity\Category::load($cat_id);
        $grlist = $group->getParents();

        $grlist[] = $cat_id;
        $grlist[] = 0;

        $sql = "select attribute_id,  cat_id,attributename,attributetype,valueslist,ordern from  shop_attributes_view   where cat_id  in(" . implode(',', $grlist) . ")  order  by ordern";

        $attrtypes = self::getAttributeTypes();
        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $row['attributetypename'] = $attrtypes[$row['attributetype']];
            $list[$row["attribute_id"]] = new ProductAttribute($row);
        }
        return $list;
    }

    /**
     * Возвращает  список атрибутов группы для  отбора
     *
     * @param mixed $cat_id группа
     */
    public static function getProductSearchAttributeListByGroup($cat_id) {
        $list = array();
        if ($cat_id == 0) {
            return $list;
        }
        $conn = DB::getConnect();

        $cat = \App\Entity\Category::load($cat_id);
        $plist = $cat->getParents();
        $plist[] = $cat_id;
        $grlist = implode(',', $plist);
        $sql = "select attribute_id,  cat_id,attributename,attributetype,valueslist from  shop_attributes   
                    where   cat_id  in($grlist) and attributetype in(1,2,3,4)  and attribute_id in(select distinct attribute_id from  shop_attributevalues)  order  by cat_id";

        $attrtypes = self::getAttributeTypes();
        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $row['attributetypename'] = $attrtypes[$row['attributetype']];
            $list[$row["attribute_id"]] = new ProductAttribute($row);
        }
        return $list;
    }

    // список  типов  атрибутов товара
    public static function getAttributeTypes() {

        return array(1 => \App\Helper::l("shopattrynname"),
            //  2 => \App\Helper::l("shopattrnumname")  , 
                     3 => \App\Helper::l("shopattrlistname"),
                     4 => \App\Helper::l("shopattrsetname"),
                     5 => \App\Helper::l("shopattrstrname")
        );
    }

    //список значений  для  атрибута типа  число
    public static function getAttrValues($cat_id, $attribute_id) {
        $conn = DB::getConnect();
        $sql = "select distinct  attributevalue  from  shop_attributevalues where  attribute_id = {$attribute_id} and item_id in(select item_id from items where disabled <> 1 and  cat_id={$cat_id}) order by attributevalue";
        return $conn->GetCol($sql);
    }

    public  static function getPages(){
        $shop = \App\System::getOptions("shop");
        $pages = $shop['pages'] ;
        if(!is_array($pages)) $pages = array();
        
        return  array_keys($pages);
    }
    
  
    //список атрибутов для  вариации
    public static function getAttrVar($cat_id ) {
        $conn = DB::getConnect();
        $sql = "select attribute_id,   attributename   from  shop_attributes   
                    where   cat_id = {$cat_id} and attributetype = 3  order  by  attributename ";
        $rs =  $conn->Execute($sql);
        $attr  = array();
        foreach($rs as $row){
           $attr[ $row['attribute_id']]= $row['attributename'] ;
        }
        
        return $attr;
    }
  
    /**
    * подпись для wayforpay
    * 
    */
    public static function signWP( ) {
        
    }

    /**
    * подпись для liqpay
    * 
    */
    public static function signLQ( ) {
        
    }
  
    
    /*
      //формирование  условий отбора   по  выбранным  критериям
      private static function _getWhere($filter) {
      $where = ' where deleted <> 1  ';
      if ($filter->cat_id > 0) {
      // $where = $where . " and p.cat_id in (select g.cat_id  from  shop_productgroups g where  treeorder like '%" . sprintf('%08s', $filter->cat_id) . "%') ";
      $where = $where . " and cat_id ={$filter->cat_id} ";
      }
      if ($filter->minprice > 0) {
      $where = $where . " and price >= " . $filter->minprice;
      }
      if ($filter->maxprice > 0) {
      $where = $where . " and price <= " . $filter->maxprice;
      }
      if (count($filter->manufacturers) > 0) {
      $where = $where . " and (1=2 ";
      foreach ($filter->manufacturers as $manufacturer_id) {
      $where = $where . " or manufacturer_id = " . $manufacturer_id;
      }
      $where = $where . ") ";
      }
      if (strlen($filter->searchkey) > 0) {
      $where = $where . " and (productname like '%{$filter->searchkey}%' or description like '%{$filter->searchkey}%' or fulldescription like '%{$filter->searchkey}%')";
      }


      if (count($filter->attributes) > 0) {
      $wherep = " and  item_id in(select item_id  from  shop_attributevalues   where   ";
      foreach ($filter->attributes as $attr) {
      if ($attr->attributetype == 1 and $attr->searchvalue == 1) {
      $where = $where . $wherep . " attribute_id = " . $attr->attribute_id . " and attributevalue = '1')";
      }
      if ($attr->attributetype == 2 and $attr->searchvalue['min'] > 0) {
      $where = $where . $wherep . " attribute_id = " . $attr->attribute_id . " and attributevalue >= {$attr->searchvalue['min']})";
      }
      if ($attr->attributetype == 2 and $attr->searchvalue['max'] > 0) {
      $where = $where . $wherep . "  attribute_id = " . $attr->attribute_id . " and attributevalue <= {$attr->searchvalue['max']})";
      }
      if (($attr->attributetype == 3 or $attr->attributetype == 4) and count($attr->searchvalue) > 0) {
      $where = $where . $wherep . "  attribute_id = " . $attr->attribute_id . " and  ( 1=2 ";
      foreach ($attr->searchvalue as $val) {
      $val = trim($val);
      $where .= " or attributevalue LIKE '%{$val}%' ";
      }
      $where .= ') )';
      }
      }
      }

      return $where;
      }


      //список отфильтрованных товаров на  странице (используется  для  пагинатора)
      public static function getProductList($start, $count) {

      $filter = ProductSearchFilter::getFilter();
      $list = array();

      if ($filter->cat_id == 0) {
      return $list;
      }
      $conn = DB::getConnect();

      $where = self::getWhere($filter);

      $sql = "select * from shop_products_view {$where} order by {$filter->sortedfield} {$filter->desc} limit {$start},{$count}";


      $rs = $conn->Execute($sql);
      foreach ($rs as $row) {
      $product = new Product($row);
      // $product->attributes = self::getAttributesByProduct($product->product_id);
      $list[$row["product_id"]] = $product;
      }
      return $list;
      }

      //количество  отфильтрованных товаров (используется  для  пагинатора)
      public static function getProductCount($filter) {


      if ($filter->cat_id == 0) {
      return 0;
      }

      $list = array();
      $conn = DB::getConnect();
      $where = self::_getWhere($filter);

      $sql = "select count(item_id) as  cnt from  items p " . $where;
      return $conn->GetOne($sql);
      }

     */
}
