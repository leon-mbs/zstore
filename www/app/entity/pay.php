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
    const PAY_BASE_INCOME      = 1;     //операционные доходы
    const PAY_OTHER_INCOME     = 2;   //прочие доходы
    const PAY_FIN              = 3;   //доходы от  фин.  деятельности
    const PAY_CANCEL_CUST      = 5;    //отмена  платежа  покупки
    const PAY_BASE_OUTCOME     = 50;    //операционные расходы
    const PAY_COMMON_OUTCOME   = 51;    //общепроизводственные  расходы
    const PAY_ADMIN_OUTCOME    = 52;    //административные  расходы
    const PAY_SALE_OUTCOME     = 53;    //расходы на сбыт
    const PAY_SALARY_OUTCOME   = 54;    //выплата зарплат
    const PAY_TAX_OUTCOME      = 55;    //уплата  налогов  и сборов
    const PAY_BILL_OUTCOME     = 56;    //расходы на  аренду и комуналку
    const PAY_OTHER_OUTCOME    = 57;   //прочие расходы
    const PAY_DIVIDEND_OUTCOME = 58;   //распределение прибыли
    const PAY_INV              = 59;   //Инвестиции
    const PAY_BANK             = 60;   //Банковское  обслуживание
    const PAY_CANCEL           = 58;    //отмена  платежа  продажи

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
        $list = Pay::find("mf_id >0  and document_id=" . $document_id, "pl_id");

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
          
          
            if ($mf->beznal == 1 && $mf->btran > 0  && $amount < 0) {
                $payb = new \App\Entity\Pay();
                $payb->mf_id = $mf_id;
                $payb->document_id = $document_id;
                $payb->amount =   ($amount * $mf->btran / 100);
                $payb->paytype = Pay::PAY_BANK;
                $payb->paydate = $paydate;
                $payb->notes = \App\Helper::l('bankproc');
                $payb->user_id = \App\System::getUser()->user_id;
                $payb->save();
            }
            
            if ($mf->beznal == 1 && $mf->btranin > 0  && $amount > 0) {
                $payb = new \App\Entity\Pay();
                $payb->mf_id = $mf_id;
                $payb->document_id = $document_id;
                $payb->amount =  0- ($amount * $mf->btranin / 100);
                $payb->paytype = Pay::PAY_BANK;
                $payb->paydate = $paydate;
                $payb->notes = \App\Helper::l('bankproc');
                $payb->user_id = \App\System::getUser()->user_id;
                $payb->save();
            }
            
        }


        $conn = \ZDB\DB::getConnect();

        $sql = "select coalesce(abs(sum(amount)),0) from paylist where paytype <> ".Pay::PAY_BANK." and  document_id=" . $document_id;
        $payed = $conn->GetOne($sql);
        $conn->Execute("update documents set payed={$payed} where   document_id =" . $document_id);
        return $payed;
    }

    public static function cancelPayment($id, $comment) {
        $pl = Pay::load($id);
        if ($pl == null) {
            return;
        }

        $doc = \App\Entity\Doc\Document::load($pl->document_id);

        $pay = new \App\Entity\Pay();
        $pay->mf_id = $pl->mf_id;

        $pay->amount = 0 - $pl->amount;
        $pay->document_id = $pl->document_id;

        $pay->paytype = $pl->paytype;

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
            $list[self::PAY_BASE_INCOME] = \App\Helper::l('pt_inprod');

            $list[self::PAY_OTHER_INCOME] = \App\Helper::l('pt_inother');
            $list[self::PAY_FIN] = \App\Helper::l('pt_fin');
            $list[self::PAY_CANCEL_CUST] = \App\Helper::l('pt_cancelcust');
        }

        if ($type != 1) {
            $list[self::PAY_BASE_OUTCOME] = \App\Helper::l('pt_outprod');
            $list[self::PAY_COMMON_OUTCOME] = \App\Helper::l('pt_outcommon');
            $list[self::PAY_ADMIN_OUTCOME] = \App\Helper::l('pt_outadm');
            $list[self::PAY_SALE_OUTCOME] = \App\Helper::l('pt_outsell');
            $list[self::PAY_SALARY_OUTCOME] = \App\Helper::l('pt_outsalary');
            $list[self::PAY_TAX_OUTCOME] = \App\Helper::l('pt_outtax');
            $list[self::PAY_BILL_OUTCOME] = \App\Helper::l('pt_outrent');
            $list[self::PAY_DIVIDEND_OUTCOME] = \App\Helper::l('pt_outcap');
            $list[self::PAY_OTHER_OUTCOME] = \App\Helper::l('pt_outother');
            $list[self::PAY_INV] = \App\Helper::l('pt_inv');
            $list[self::PAY_BANK] = \App\Helper::l('pt_bank');
            $list[self::PAY_CANCEL] = \App\Helper::l('pt_cancel');
        }

        return $list;
    }

}
