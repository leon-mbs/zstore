<?php

namespace App\Entity;

/**
 * Класс-сущность  оплата
 *
 * @table=paylist
 * @keyfield=pl_id
 * @view=paylist_view
 */
class Pay extends \ZCL\DB\Entity
{

    //типы платеежей - затраты и доходы
    const PAY_BASE_INCOME      = 1;     //доход от основной  деятельности
    const PAY_INVEST_INCOME    = 2;     //инвестиции
    const PAY_OTHER_INCOME     = 100;   //прочие доходы
    const PAY_CANCEL_CUST      = 5;    //Возврат  поставщику
    const PAY_BASE_OUTCOME     = 50;      //расходы основной  деятельности
    const PAY_COMMON_OUTCOME   = 51;    //общепроизводственные  расходы
    const PAY_ADMIN_OUTCOME    = 52;    //административные  расходы
    const PAY_SALE_OUTCOME     = 53;     //расходы на сбыт
    const PAY_SALARY_OUTCOME   = 54;    //выплата зарплат
    const PAY_TAX_OUTCOME      = 55;    //уплата  налогов  и сборов
    const PAY_BILL_OUTCOME     = 56;    //расходы на  аренду и комуналку  
    const PAY_DIVIDEND_OUTCOME = 57;    //распределение прибыли 
    const PAY_CANCEL           = 58;    //Возврат  покупателю
    const PAY_OTHER_OUTCOME    = 101;   //прочие расходы

    protected function init() {
        $this->pl_id = 0;
        $this->paytype = 0;
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

    /**
     * Добавляет платеж
     *
     * @param mixed $document_id документ
     * @param mixed $isdoc оплата выполнена  документом нужно отличать при отмене документа
     * @param mixed $amount сумма
     * @param mixed $mf денежный счет
     * @param mixed $comment коментарий
     */
    public static function addPayment($document_id, $paydate, $amount, $mf, $type, $comment = '') {
        if (0 == (int)$amount || 0 == (int)$document_id || 0 == $mf) {
            return;
        }

        if ($mf == MoneyFund::CREDIT) {
            return;
        } //в  долг
        if ($mf == MoneyFund::PREPAID) {
            return;
        } //предоплата


        $pay = new \App\Entity\Pay();
        $pay->mf_id = $mf;
        $pay->document_id = $document_id;
        $pay->amount = $amount;
        $pay->paytype = $type;
        $pay->paydate = $paydate;
        $pay->notes = $comment;


        //   $admin = \App\Entity\User::getByLogin('admin');
        $pay->user_id = \App\System::getUser()->user_id;
        $pay->save();

        $conn = \ZDB\DB::getConnect();

        $sql = "select coalesce(abs(sum(amount)),0) from paylist where document_id=" . $document_id;
        $payed = $conn->GetOne($sql);
        $conn->Execute("update documents set payed={$payed} where   document_id =" . $document_id);
    }

    /**
     * список  расходов  и доходов
     *
     * @param mixed $type 0- все, 1- доходы, 2-расходы
     */
    public static function getPayTypeList($type = 0) {
        $list = array();
        if ($type != 2) {
            $list[PAY::PAY_BASE_INCOME] = \App\Helper::l('pt_inprod');
            $list[PAY::PAY_INVEST_INCOME] = \App\Helper::l('pt_ininv');
            $list[PAY::PAY_OTHER_INCOME] = \App\Helper::l('pt_inother');
            $list[PAY::PAY_CANCEL_CUST] = \App\Helper::l('pt_infromcust');
        }

        if ($type != 1) {
            $list[PAY::PAY_BASE_OUTCOME] = \App\Helper::l('pt_outprod');
            $list[PAY::PAY_COMMON_OUTCOME] = \App\Helper::l('pt_outcommon');
            $list[PAY::PAY_ADMIN_OUTCOME] = \App\Helper::l('pt_outadm');
            $list[PAY::PAY_SALE_OUTCOME] = \App\Helper::l('pt_outsell');
            $list[PAY::PAY_SALARY_OUTCOME] = \App\Helper::l('pt_outsalary');
            $list[PAY::PAY_TAX_OUTCOME] = \App\Helper::l('pt_outtax');
            $list[PAY::PAY_BILL_OUTCOME] = \App\Helper::l('pt_outrent');
            $list[PAY::PAY_DIVIDEND_OUTCOME] = \App\Helper::l('pt_outcap');
            $list[PAY::PAY_OTHER_OUTCOME] = \App\Helper::l('pt_outother');
            $list[PAY::PAY_CANCEL] = \App\Helper::l('pt_outbackcust');
        }

        return $list;
    }

}
