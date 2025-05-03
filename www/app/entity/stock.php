<?php

namespace App\Entity;

/**
 * Клас-сущность  записи  о  движении товара на  складе.
 *
 * @table=store_stock
 * @view=store_stock_view
 * @keyfield=stock_id
 */
class Stock extends \ZCL\DB\Entity
{
    protected function init() {
        $this->stock_id = 0;
    }

    protected function afterLoad() {
        if (strlen($this->sdate ?? '') > 0) {
            $this->sdate = strtotime($this->sdate);
        }
    }

    /**
     * Метод  для   получения  имени  ТМЦ  с  ценой для выпадающих списков
     *
     * @param mixed $store
 
     * @static
     */
    public static function findArrayAC($store, $partname = "") {

         
        $criteria = "qty > 0 and itemdisabled <> 1 and store_id=" . $store;
        if (strlen($partname) > 0) {
            $like = self::qstr('%' . $partname . '%');
            $partname = self::qstr($partname);

            $criteria .= "  and  (itemname like {$like} or item_code = {$partname} or snumber = {$partname} or   bar_code = {$partname} )";
        }

        $entitylist = self::find($criteria, " sdate asc");

        $list = array();
        foreach ($entitylist as $key => $value) {
            $name = $value->itemname;
            
            if (strlen($value->item_code) > 0) {
                $name .= ', ' . $value->item_code ;
            }
            if (strlen($value->snumber) > 0) {
                $name .= ', С/Н ' . $value->snumber . ' ' . \App\Helper::fd($value->sdate) ;
            }
             
            $name .= ', ц. ' . \App\Helper::fa($value->partion) ;
            $name .= ', к. ' . \App\Helper::fqty($value->qty) ;
            $list[$key] = $name;
        }

        return $list;
    }

    /**
     * Возвращает запись  со  склада по  цене (партии  для  оптового)  товара.
     *
     * @param mixed $store_id Склад
     * @param mixed $item_id Товар
     * @param mixed $price Цена
     * @param mixed $snumber 
     * @param mixed $sdate  
     * @param mixed $create  Создать  если  не   существует
     * @param mixed $customer_id Поставщик
     * @param mixed $emp_id Сотрудник (МОЛ)
     */
    public static function getStock($store_id, $item_id, $price, $snumber = "", $sdate = 0, $create = true,$customer_id=0,$emp_id=0) {

    
        $conn = \ZDB\DB::getConnect();

        $where = "store_id = {$store_id} and item_id = {$item_id}   ";


        if (strlen($snumber) > 0) {
            $where .= "  and  snumber =  " . $conn->qstr($snumber);
        }
        if ($customer_id > 0) {
            $where .= "  and  customer_id =  " . $customer_id;
        }

        if ($emp_id > 0) {
            $where .= "  and  emp_id =  " . $emp_id;
        }


        $stock = self::getFirst($where . " and partion = {$price}   ", 'stock_id desc');
  

        if ($stock == null && $create == true) {
            $stock = new Stock();
            $stock->store_id = $store_id;
            $stock->item_id = $item_id;
            $stock->partion = $price;
            $stock->snumber = $snumber;
            $stock->sdate = $sdate;
            $stock->customer_id = $customer_id >0 ? $customer_id :null;
            $stock->emp_id = $emp_id >0 ? $emp_id :null;


            $stock->save();
        }
      
        return $stock;
    }

    /**
     * Количество в  партии на складе на  дату
     *
     * @param mixed $stock_id
     * @param mixed $date
     *
     */
    public static function getQuantity($stock_id, $date = null) {
        if ($stock_id > 0) {
            $stock = Stock::load($stock_id);
            if ($date > 0) {
                $conn = \ZDB\DB::getConnect();
                $where = "   stock_id = {$stock_id} and  document_date  <= " . $conn->DBDate($date);
                $sql = " select coalesce(sum(quantity),0) AS quantity  from entrylist_view  where " . $where;
                return $conn->GetOne($sql);
            } else {
                return $stock->qty;
            }
        }
    }

    // Подбор партий
    public static function pickup($store_id, $item,$emp_id=0) {
        $res = array();
        $where = "store_id = {$store_id} and item_id = {$item->item_id} and qty > 0   ";
        if (strlen($item->snumber) > 0 && $item->useserial == 1 ) {
            $where .= " and snumber=" . Stock::qstr($item->snumber);
        }
        if ($emp_id > 0) {
            $where .= "  and  emp_id =  " . $emp_id;
        }

        $stlist = self::find($where, ' stock_id   ');


        $qty = $item->quantity;//необходимое  количество
        $last = null;
        foreach ($stlist as $st) {
            $last = $st;
            if ($st->qty >= $qty) {
                $st->quantity = $qty;
                $res[] = $st;
                $qty = 0;
                break;    //сразу нашлась партия
            } else {
                $st->quantity = $st->qty;
                $res[] = $st;
                $qty = $qty - $st->qty; //остаток
            }
        }
        if ($qty > 0) {  // если не  достаточно
            if ($last != null) {
                $last->quantity += $qty; //остаток  пишем  к  последней партии
            } else {

                $where = "store_id = {$store_id} and item_id = {$item->item_id}   ";
                if (strlen($item->snumber) > 0) {
                    $where .= " and snumber = " . Stock::qstr($item->snumber);
                }
                if ($emp_id > 0) {
                    $where .= "  and  emp_id =  " . $emp_id;
                }
                
                $last = self::getFirst($where, ' stock_id desc ');
                if ($last == null) {
                    $conn = \ZDB\DB::getConnect();
                    $limit =" limit 0,1";
                
                    $lastpartion = $conn->GetOne("select coalesce(partion,0) from  store_stock  where  qty > 0 and  item_id={$item->item_id} order  by  stock_id desc ".$limit);
                    if ($lastpartion == 0) {
                        $lastpartion = $item->price/2;  //типа  учетная  цена
                    }

                    $last = new Stock();
                    $last->store_id = $store_id;
                    $last->item_id = $item->item_id;
                    $last->partion = $lastpartion;
                    $last->snumber = $item->snumber;
                    $last->sdate = $item->sdate;
                    $last->save();
                } else {
                    // $last->partion = $item->price;
                }
                
                $last->quantity = $qty;
                return array($last);
            }
        }
        return $res;
    }

}
