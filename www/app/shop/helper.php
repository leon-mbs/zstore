<?php

namespace App\Shop;

use \ZCL\DB\DB;
use \App\Shop\Entity\Product;
use \App\Shop\Entity\ProductGroup;
use \App\Shop\Entity\ProductAttribute;

//класс  вспомагательных функций
class Helper
{

    public static function getBreadScrumbs($id) {

        $bs = "<li class=\"breadcrumb-item\"><a href='/'>Каталог</a></li>";
        if ($id > 0) {
            $g = ProductGroup::load($id);
            $gl = $g->getParents();

            foreach ($gl as $gi) {
                $bs .= "<li class=\"breadcrumb-item\" ><a href='/scat/{$gi->group_id}'>{$gi->groupname}</a></li>";
            }
            $bs .= "<li class=\"breadcrumb-item active\">{$g->groupname}</li>";
        }
        return $bs;
    }

    //список  производителей в  данной группе  товаров 
    public static function _getManufacturerNamesByGroup($group_id, $child = false) {
        $list = array();
        $conn = DB::getConnect();
        $in = " select manufacturer_id  from  shop_products p where p.group_id={$group_id}";
        if ($child === true) {
            $in = " select manufacturer_id  from  shop_products p where p.deleted <> 1 and p.group_id in( select g.group_id  from  shop_productgroups g where  treeorder like '%" . sprintf('%08s', $group_id) . "%' )";
        }

        $sql = "select manufacturer_id,manufacturername from  shop_manufacturers where manufacturer_id in({$in}) order by manufacturername ";
        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $list[$row["manufacturer_id"]] = $row["manufacturername"];
        }

        return $list;
    }

    //формирование  условий отбора   по  выбранным  критериям
    private static function _getWhere(ProductSearchFilter $filter) {
        $where = ' where deleted <> 1  ';
        if ($filter->group_id > 0) {
            // $where = $where . " and p.group_id in (select g.group_id  from  shop_productgroups g where  treeorder like '%" . sprintf('%08s', $filter->group_id) . "%') ";
            $where = $where . " and group_id ={$filter->group_id} ";
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
            $wherep = " and  product_id in(select product_id  from  shop_attributevalues   where   ";
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

        if ($filter->group_id == 0)
            return $list;
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
    public static function getProductCount(ProductSearchFilter $filter) {

        $filter = ProductSearchFilter::getFilter();
        if ($filter->group_id == 0)
            return 0;

        $list = array();
        $conn = DB::getConnect();
        $where = self::getWhere($filter);

        $sql = "select count(product_id) as  cnt from  shop_products p " . $where;
        return $conn->GetOne($sql);
    }

    /*
      public static function getProduct($product_id)
      {
      $conn = DB::getConnect();
      $sql = "select group_id,product_id,productname,p.manufacturer_id,price,image_id,description,fulldescription,m.manufacturername from  shop_products p left join shop_manufacturers m  on p.manufacturer_id = m.manufacturer_id where product_id=" . $product_id;
      $rs = $conn->Execute($sql);
      $product = new Product($rs->FetchRow());
      //$sql = "select av.attribute_id ,attributevalue,attributetype,valueslist  from  shop_attributevalues av join shop_attributes at on av.attribute_id = at.attribute_id where product_id=  ". $product_id;
      //  $product->attributes = self::getAttributesByProduct($product_id) ;

      return $product;
      }
     */

    //список  атрибутов  товара
    public static function _getAttributesByProduct($product) {
        $conn = DB::getConnect();
        $gr = $conn->GetOne('select treeorder  from  shop_productgroups g where g.group_id=' . $product->group_id);
        $grs = str_split($gr, 8);
        $grlist = implode(',', $grs);

        $sql = "select a.attribute_id, attributename,attributetype,valueslist,(select attributevalue from  shop_attributevalues v where a.attribute_id = v.attribute_id and   product_id={$product->product_id})  as  attributevalue from  shop_attributes a   where  group_id  in($grlist) ";

        $list = array();


        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $list[$row['attribute_id']] = new ProductAttribute($row);
        }

        return $list;
    }

    /**
     * список  значений атрибутов  товара
     * 
     * @param mixed $product
     */
    public static function getAttributeValuesByProduct($product) {
        $list = array();
        $conn = DB::getConnect();
        $sql = "select v.attribute_id ,a.attributename,a.attributetype,a.valueslist,a.valueslist,v.attributevalue  from  shop_attributes a  join shop_attributevalues v on a.attribute_id = v.attribute_id where v.product_id=  " . $product->product_id;


        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $list[$row['attribute_id']] = new ProductAttribute($row);
        }
        return $list;
    }

    /**
     * Возвращает  список  атрибутов  для  группы
     * 
     * @param mixed $group_id   группа
     */
    public static function getProductAttributeListByGroup($group_id) {
        $list = array();
        if ($group_id == 0)
            return $list;
        $conn = DB::getConnect();


        $gr = $conn->GetOne("select mpath  from  shop_productgroups  where group_id={$group_id}  ");
        $grs = str_split($gr, 8);
        $grlist = implode(',', $grs);
//                  
        $sql = "select attribute_id,showinlist, group_id,attributename,attributetype,valueslist,ordern from  shop_attributes_view   where group_id  in($grlist)  order  by ordern";

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
     * @param mixed $group_id   группа
     */
    public static function getProductSearchAttributeListByGroup($group_id) {
        $list = array();
        if ($group_id == 0)
            return $list;
        $conn = DB::getConnect();

        $gr = $conn->GetOne("select mpath  from  shop_productgroups  where group_id={$group_id}  ");
        $grs = str_split($gr, 8);   // получаем  все родительские  группы
        $grlist = implode(',', $grs);
        if (strlen($grlist) == 0) {
            return $list;
        }
        $sql = "select attribute_id,showinlist, group_id,attributename,attributetype,valueslist from  shop_attributes   
                    where showinlist = 1 and group_id  in($grlist) and attributetype in(1,2,3,4)  and attribute_id in(select distinct attribute_id from  shop_attributevalues)  order  by group_id";

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
        return array(1 => 'Есть/Нет', 2 => 'Число', 3 => 'Список', 4 => 'Набор', 5 => 'Строка');
    }

    //список значений  для  атрибута типа  число
    public static function getAttrValues($group_id, $attribute_id) {
        $conn = DB::getConnect();
        $sql = "select distinct  attributevalue  from  shop_attributevalues where  attribute_id = {$attribute_id} and product_id in(select product_id from shop_products where deleted <> 1 and  group_id={$group_id}) order by attributevalue";
        return $conn->GetCol($sql);
    }

    //возвращает наименьшую и наибольшую цену
    public static function getPriceRange($group_id) {
        $conn = DB::getConnect();
        $sql = "select coalesce(min(price),0) as minp,coalesce(max(price),0) as maxp  from  shop_products where  deleted <> 1 and   group_id={$group_id}   ";
        return $conn->GetRow($sql);
    }

}
