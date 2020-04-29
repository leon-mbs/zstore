<?php

namespace App\Entity;

/**
 * сущность для хранения аналитического  учета
 *
 * @table=entrylist
 * @keyfield=entry_id
 */
class Entry extends \ZCL\DB\Entity
{

    /**
     *
     *
     * @param mixed $document Ссылка  на  документ
     * @param mixed $amount Сумма.
     * @param mixed $quantity количество
     */
    public function __construct($document_id = 0, $amount = 0, $quantity = 0) {
        parent::__construct();


        $this->document_id = $document_id;
        $this->amount = $amount;

        $this->quantity = $quantity;
    }

    protected function init() {
        $this->customer_id = 0;
        $this->employee_id = 0;
        $this->extcode = 0;
        $this->stock_id = 0;
        $this->service_id = 0;
    }

    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
    }

    public function setStock($stock_id) {
        $this->stock_id = $stock_id;
    }

    public function setEmployee($employee_id) {
        $this->employee_id = $employee_id;
    }

    public function setCustomer($customer_id) {
        $this->customer_id = $customer_id;
    }

    public function setService($service_id) {
        $this->service_id = $service_id;
    }

    //типы  налогов, начислений  удержаний, прочая вспомагтельная  аналитика
    public function setExtCode($code) {

        $this->extcode = $code;

    }

    /**
     * Получение  количества   по  комбинации измерений
     * неиспользуемые значения  заполняются  нулем
     *
     * @param mixed $date дата на  конец дня
     * @param mixed $acc синтетичкеский счет
     * @param mixed $stock товар (партия)
     * @param mixed $customer контрашент
     * @param mixed $emp сотрудник
     * @param mixed $mf денежный счет
     * @param mixed $asset необоротный актив
     * @param mixed $code универсальное поле
     */
    public static function getQuantity($date = 0, $stock = 0, $customer = 0, $emp = 0, $code = 0) {
        $conn = \ZDB\DB::getConnect();
        $where = "   1=1";
        if ($date > 0) {
            $where = $where . "   date(document_date) <= " . $conn->DBDate($date);
        }

        if ($emp > 0) {
            $where = $where . " and employee_id= " . $emp;
        }

        if ($code > 0) {
            $where = $where . " and extcode= " . $code;
        }

        if ($stock > 0) {
            $where = $where . " and stock_id= " . $stock;
        }
        if ($customer > 0) {
            $where = $where . " and customer_id= " . $customer;
        }
        $sql = " select coalesce(sum(quantity),0) AS quantity  from entrylist  where " . $where;
        return $conn->GetOne($sql);
    }

    /**
     * Получение  суммы   по  комбинации измерений
     * неиспользуемые значения  заполняются  нулем
     *
     * @param mixed $date дата на  конец дня
     * @param mixed $acc синтетичкеский счет
     * @param mixed $stock товар (партия)
     * @param mixed $customer контрашент
     * @param mixed $emp сотрудник
     * @param mixed $mf денежный счет
     * @param mixed $asset необоротный актив
     * @param mixed $code универсальное поле
     */
    public static function getAmount($date = 0, $stock = 0, $customer = 0, $emp = 0, $code = 0) {
        $conn = \ZDB\DB::getConnect();
        $where = "   1=1";
        if ($date > 0) {
            $where = $where . " and  date(document_date) <= " . $conn->DBDate($date);
        }

        if ($emp > 0) {
            $where = $where . " and employee_id= " . $emp;
        }

        if ($code > 0) {
            $where = $where . " and extcode= " . $code;
        }

        if ($stock > 0) {
            $where = $where . " and stock_id= " . $stock;
        }
        if ($customer > 0) {
            $where = $where . " and customer_id= " . $customer;
        }
        $sql = " select coalesce(sum(amount),0) AS quantity  from entrylist  where " . $where;
        return $conn->GetOne($sql);
    }

}
