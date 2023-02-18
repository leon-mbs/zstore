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
        if (strlen($this->sdate) > 0) {
            $this->sdate = strtotime($this->sdate);
        }
    }

    /**
     * Метод  для   получения  имени  ТМЦ  с  ценой для выпадающих списков
     *
     * @param mixed $criteria
     * @return []
     * @static
     */
    public static function findArrayAC($store, $partname = "") {
        $partiontype = \App\System::getOption('common', 'partiontype');

        $criteria = "qty > 0 and itemdisabled <> 1 and store_id=" . $store;
        if (strlen($partname) > 0) {
            $like = self::qstr('%' . $partname . '%');
            $partname = self::qstr($partname);

            $criteria .= "  and  (itemname like {$like} or item_code = {$partname} or snumber = {$partname} or   bar_code = {$partname} )";
        }

        $entitylist = self::find($criteria, "sdate asc");

        $list = array();
        foreach ($entitylist as $key => $value) {
            if (strlen($value->snumber) > 0) {
                $value->itemname .= ' (' . $value->snumber . ',' . \App\Helper::fd($value->sdate) . ')';
            }

            if ($partiontype == "1") { //отдельно  по входным  ценам
                $list[$key] = $value->itemname . ', ' . \App\Helper::fqty($value->partion);
            } else {
                $list[$key] = $value->itemname;
            }
        }

        return $list;
    }

    /**
     * Возвращает запись  со  склада по  цене (партии  для  оптового)  товара.
     *
     * @param mixed $store_id Склад
     * @param mixed $tovar_id Товар
     * @param mixed $price Цена
     * @param mixed $create Создать  если  не   существует
     */
    public static function getStock($store_id, $item_id, $price, $snumber = "", $sdate = 0, $create = false) {

        $partiontype = \App\System::getOption('common', 'partiontype');

        $conn = \ZDB\DB::getConnect();

        $where = "store_id = {$store_id} and item_id = {$item_id}   ";


        if (strlen($snumber) > 0) {
            $where .= "  and  snumber =  " . $conn->qstr($snumber);
        }

        //     if ($partiontype == '2') {    //учет  отдельно  по  каждой цене
        //     $where .= " and partion = {$price}   ";
        //     }


        $stock = self::getFirst($where . " and partion = {$price}   ", 'stock_id desc');
        if ($partiontype == '1' && $stock == null) {  //если  не  нашли  такую  партию  то  берем  последнюю
            $stock = self::getFirst($where, 'stock_id desc');
        }


        if ($stock == null && $create == true) {
            $stock = new Stock();
            $stock->store_id = $store_id;
            $stock->item_id = $item_id;
            $stock->partion = $price;
            $stock->snumber = $snumber;
            $stock->sdate = $sdate;

            
            $stock->save();
        }
        if ($partiontype == '1' && $price > 0) {    //учет  по  последней цене
            $stock->partion = $price;
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
                $where = "   stock_id = {$stock_id} and date(document_date) <= " . $conn->DBDate($date);
                $sql = " select coalesce(sum(quantity),0) AS quantity  from entrylist_view  where " . $where;
                return $conn->GetOne($sql);
            } else {
                return $stock->qty;
            }
        }
    }

    // Поиск партий
    public static function pickup($store_id, $item) {
        $res = array();
        $where = "store_id = {$store_id} and item_id = {$item->item_id} and qty > 0   ";
        if (strlen($item->snumber) > 0) {
            $where .= " and snumber=" . Stock::qstr($item->snumber);
        }

        $stlist = self::find($where, ' stock_id  ');


        $qty = $item->quantity;
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
                $last = self::getFirst($where, ' stock_id desc ');
                if ($last == null) {
                    $conn = \ZDB\DB::getConnect();
                    $limit =" limit 0,1";
                    if($conn->dataProvider=="postgres") {
                        $limit =" limit 1";
                    }         
                    $lastpartion = $conn->GetOne("select coalesce(partion,0) from  store_stock  where  qty > 0 and  item_id={$item->item_id} order  by  stock_id desc ".$limit);
                    if ($lastpartion == 0) {
                        $lastpartion = $price;
                    }

                    $last = new Stock();
                    $last->store_id = $store_id;
                    $last->item_id = $item->item_id;
                    $last->partion = $lastpartion;
                    $last->snumber = $item->snumber;
                    $last->sdate = $item->sdate;
                } else {
                    // $last->partion = $item->price;
                }
                $last->save();
                $last->quantity = $qty;
                return array($last);
            }
        }
        return $res;
    }

}
