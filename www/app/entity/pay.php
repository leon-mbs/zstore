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

    //типы платежей - затраты и доходы
    const PAY_BASE_INCOME = 1;     //операционные доходы  

    const PAY_OTHER_INCOME = 2;   //прочие доходы
    const PAY_FIN          = 3;   //доходы от  фин.  деятельности


    const PAY_BASE_OUTCOME     = 50;    //операционные расходы  
    const PAY_COMMON_OUTCOME   = 51;    //общепроизводственные  расходы
    const PAY_ADMIN_OUTCOME    = 52;    //административные  расходы
    const PAY_SALE_OUTCOME     = 53;    //расходы на сбыт
    const PAY_SALARY_OUTCOME   = 54;    //выплата зарплат
    const PAY_TAX_OUTCOME      = 55;    //уплата  налогов  и сборов
    const PAY_BILL_OUTCOME     = 56;    //расходы на  аренду и комуналку  
    const PAY_OTHER_OUTCOME    = 57;   //прочие расходы
    const PAY_DIVIDEND_OUTCOME = 58;   //распределение прибыли

    protected function init() {
        $this->pl_id = 0;
        $this->paytype = 0;
        $this->paydate = time();
    }


    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        $this->paydate = strtotime($this->paydate);
        //распаковываем  данные из detail
        if (strlen($this->detail) == 0) {
            return;
        }

        $xml = simplexml_load_string($this->detail);

        parent::afterLoad();
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
    public static function addPayment($document_id, $paydate, $amount, $mf_id, $type, $comment = '') {
        if (0 == (float)$amount || 0 == (int)$document_id || 0 == $mf_id) {
            return;
        }

        if ($mf_id == MoneyFund::CREDIT) {
            return;
        } //в  долг
        if ($mf_id == MoneyFund::PREPAID) {
            return;
        } //предоплата


        $pay = new \App\Entity\Pay();
        $pay->mf_id = $mf_id;
        $pay->document_id = $document_id;
        $pay->amount = $amount;
        $pay->paytype = $type;
        $pay->paydate = $paydate;
        $pay->notes = $comment;
        $pay->user_id = \App\System::getUser()->user_id;
        $pay->save();

        $mf = \App\Entity\MoneyFund::load($mf_id);
        if ($mf instanceof \App\Entity\MoneyFund) {
            //банковский процент
            if ($mf->beznal == 1 and $mf->btran > 0) {
                $pay = new \App\Entity\Pay();
                $pay->mf_id = $mf_id;
                $pay->document_id = $document_id;
                $pay->amount = 0 - ($amount * $mf->btran / 100);
                $pay->paytype = Pay::PAY_BASE_OUTCOME;
                $pay->paydate = $paydate;
                $pay->notes = \App\Helper::l('bankproc');
                $pay->user_id = \App\System::getUser()->user_id;
                $pay->save();
            }
        }


        $conn = \ZDB\DB::getConnect();

        $sql = "select coalesce(abs(sum(amount)),0) from paylist where document_id=" . $document_id;
        $payed = $conn->GetOne($sql);
        $conn->Execute("update documents set payed={$payed} where   document_id =" . $document_id);
    }

    public static function cancelPayment($id, $comment) {
        $pl = Pay::load($id);
        if ($pl == null) {
            return;
        }

        $pay = new \App\Entity\Pay();
        $pay->mf_id = $pay->mf;

        $pay->amount = 0 - $pay->amount;

        $pay->paydate = time();
        $pay->notes = $comment;

        $pay->user_id = \App\System::getUser()->user_id;
        $pay->save();

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

            $list[PAY::PAY_OTHER_INCOME] = \App\Helper::l('pt_inother');
            $list[PAY::PAY_FIN] = \App\Helper::l('pt_fin');

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

        }

        return $list;
    }

}
