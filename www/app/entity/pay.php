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


    const PAY_CUSTOMER = 1;   //расчеты  с  контрагентм
    const PAY_BANK     = 1000;   //эквайринг
    const PAY_BONUS    = 1001;   //бонусы


    protected function init() {
        $this->pl_id = 0;
        $this->paytype = 0;
        $this->paydate = time();
    }


    protected function afterLoad() {
        $this->paydate = strtotime($this->paydate);

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

        if ($mf_id == 0) {
            return;
        }


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


            if ($mf->beznal == 1) {
                if (($mf->btran > 0 && $amount < 0) || ($mf->btranin > 0 && $amount > 0)) {
                    $amount = abs($amount);
                    $payb = new \App\Entity\Pay();
                    $payb->mf_id = $mf_id;
                    $payb->document_id = $document_id;
                    if ($mf->btran > 0) {
                        $payb->amount = 0 - ($amount * $mf->btran / 100);
                    }
                    if ($mf->btranin > 0) {
                        $payb->amount = 0 - ($amount * $mf->btranin / 100);
                    }
                    $payb->paytype = Pay::PAY_BANK;
                    $payb->paydate = $paydate;
                    $payb->notes = \App\Helper::l('bankproc');
                    $payb->user_id = \App\System::getUser()->user_id;
                    $payb->save();

                    \App\Entity\IOState::addIOState($document_id, $payb->amount, \App\Entity\IOState::TYPE_BANK);


                }
            }


        }

        if ($amount > 0) {
            self::addBonus($document_id, $amount);
        }

        $conn = \ZDB\DB::getConnect();

        $sql = "select coalesce(abs(sum(amount)),0) from paylist where paytype < 1000  and  document_id=" . $document_id;
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

    //начисление  (списание)  бонусов
    public static function addBonus($document_id, $amount) {
        if (0 == (int)$amount) {
            return;
        }
        $conn = \Zdb\DB::getConnect();

        $customer_id = (int)$conn->GetOne("select  customer_id  from  documents where  document_id=" . $document_id);

        $c = \App\Entity\Customer::load($customer_id);
        if ($c == null) {
            return;
        }

        $cnt = (int)$conn->GetOne("select  count(*)  from paylist_view where  customer_id=" . $customer_id);


        if (doubleval($c->discount) > 0) { //если  постоянная скидка бонусы  не  начисляем
            return;
        }

        if (0 > (int)$amount) { //списание
            $pay = new \App\Entity\Pay();

            $pay->document_id = $document_id;
            $pay->bonus = (int)$amount;
            $pay->paytype = self::PAY_BONUS;
            $pay->paydate = time();

            $pay->save();

            return;
        }


        $disc = \App\System::getOptions("discount" );


        $cnt = (int)$conn->GetOne("select  count(*)  from paylist_view where  customer_id=" . $customer_id);

        if ($cnt == 0 && doubleval($disc["firstbay"]) > 0) {   //первая  покупка
            $bonus = round($amount * doubleval($disc["firstbay"] / 100));
        } else {


            if ($disc["summa1"] > 0 && $disc["bonus1"] > 0 && $disc["summa1"] < $amount) {
                $bonus = round($amount * $disc["bonus1"] / 100);
            }
            if ($disc["summa2"] > 0 && $disc["bonus2"] > 0 && $disc["summa2"] < $amount) {
                $bonus = round($amount * $disc["bonus2"] / 100);
            }
        }

        if ($bonus > 0) {


            $pay = new \App\Entity\Pay();

            $pay->document_id = $document_id;
     
            $pay->user_id = 0;
            $pay->amount = 0;
            $pay->bonus = (int)$bonus;
            $pay->paytype = self::PAY_BONUS;
            $pay->paydate = time();

            $pay->save();
        }
    }

}
