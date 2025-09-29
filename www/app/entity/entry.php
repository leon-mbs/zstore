<?php

namespace App\Entity;

/**
 * сущность для хранения аналитического  учета
 *
 * @view=entrylist_view
 * @table=entrylist
 * @keyfield=entry_id
 */
class Entry extends \ZCL\DB\Entity
{
    public const TAG_SELL     = -1;    //продажа
    public const TAG_BAY      = -2;    //закупка
    public const TAG_RSELL    = -4;    //возврат  покупателя
    public const TAG_RBAY     = -8;    //возврат  поставщику
    public const TAG_TOPROD   = -16;   //списание в  производство
    public const TAG_FROMPROD = -32;   //оприходование  с  производства
    public const TAG_RESERV   = -64;   //резервирование
    public const TAG_MOVE     = -128;  //перемещение
    public const TAG_IN       = -256;  //оприходование
    public const TAG_OUT      = -512;  //списание



    /**
     *
     *
     * @param mixed $document_id Ссылка  на  документ
     * @param mixed $amount Сумма.
     * @param mixed $quantity количество
     */
    public function __construct($document_id = 0, $amount = 0, $quantity = 0) {
        parent::__construct();

        $this->document_id = $document_id;
        $this->amount = $amount;

        $this->quantity = $quantity;
        $this->tag = 0;
    }

    protected function init() {

    }

    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
    }

    public function setQuantity($quantity) {
        $this->quantity = $quantity;
    }

    public function setStock($stock_id) {
        $this->stock_id = $stock_id;
    }


    public function setService($service_id) {
        $this->service_id = $service_id;
    }


    public function setOutPrice($price) {

        $this->outprice = $price;
    }

    /**
     * Получение  количества   по  комбинации измерений
     * неиспользуемые значения  заполняются  нулем
     *
     * @param mixed $date дата на  конец дня
     * @param mixed $stock товар (партия)
     * @param mixed $customer контрашент
     * @param mixed $emp сотрудник
     */
    public static function getQuantity($date = 0, $stock = 0, $customer = 0, $emp = 0) {
        $conn = \ZDB\DB::getConnect();
        $where = "   1=1";
        if ($date > 0) {
            $where = $where . "   document_date  <= " . $conn->DBDate($date);
        }

        if ($emp > 0) {
            $where = $where . " and employee_id= " . $emp;
        }


        if ($stock > 0) {
            $where = $where . " and stock_id= " . $stock;
        }
        if ($customer > 0) {
            $where = $where . " and customer_id= " . $customer;
        }
        $sql = " select coalesce(sum(quantity),0)    from entrylist  where " . $where;
        return $conn->GetOne($sql);
    }

    /**
     * Получение  суммы   по  комбинации измерений
     * неиспользуемые значения  заполняются  нулем
     *
     * @param mixed $date дата на  конец дня
     * @param mixed $stock товар (партия)
     * @param mixed $customer контрашент
     * @param mixed $emp сотрудник
     */
    public static function getAmount($date = 0, $stock = 0, $customer = 0, $emp = 0) {
        $conn = \ZDB\DB::getConnect();
        $where = "   1=1";
        if ($date > 0) {
            $where = $where . " and   document_date <= " . $conn->DBDate($date);
        }

        if ($emp > 0) {
            $where = $where . " and employee_id= " . $emp;
        }


        if ($stock > 0) {
            $where = $where . " and stock_id= " . $stock;
        }
        if ($customer > 0) {
            $where = $where . " and customer_id= " . $customer;
        }
        $sql = " select coalesce(sum(quantity*outprice),0)    from entrylist  where " . $where;
        return $conn->GetOne($sql);
    }

}
