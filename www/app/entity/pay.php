<?php

namespace App\Entity;

/**
 * Класс-сущность  оплата
 *
 * @table=paylist
 * @keyfield=pl_id
 * @view=paylist_view
 */
class Pay extends \ZCL\DB\Entity {

    protected function init() {
        $this->pl_id = 0;
        $this->paydate = time();
    }

    protected function afterLoad() {
        $this->paydate = strtotime($this->paydate);
    }

    //возвращает список оплат
    public static function getPayments($document_id) {
        $list = Pay::find("document_id=" . $document_id, "pl_id");

        return $list;
    }

    //возвращает сумму  оплат по документу
    public static function getPaymentAmount($document_id) {
        $conn = \ZDB\DB::getConnect();

        $sql = "select coalesce(sum(amount),0) from paylist where document_id=" . $document_id;
        return $conn->GetOne($sql);
    }

    /**
     * Добавляет платеж
     * 
     * @param mixed $document_id  документ
     * @param mixed $amount  сумма
     * @param mixed $mf      денежный счет
     * @param mixed $comment коментарий
     */
    public static function addPayment($document_id, $amount, $mf, $comment = '') {
        if (0 == (int) $amount || 0 == (int) $document_id || 0 == $mf)
            return;
        $pay = new \App\Entity\Pay();
        $pay->mf_id = $mf;
        $pay->document_id = $document_id;
        $pay->amount = $amount;
        $pay->notes = $comment;


        $pay->user_id = \App\System::getUser()->user_id;
        $pay->save();
    }

}
