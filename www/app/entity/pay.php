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

    
    const PAY_CUSTOMER         = 1;   //расчеты  с  контрагентм
    const PAY_BANK             = 2;   //эквайринг
    const PAY_BONUS            = 3;   //бонусы  клиенту
    
    
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

        $io = new \App\Entity\IOState();
        $io->document_id = $document_id;
        $io->amount = $amount;
        $io->iotype = $type;
  
        $io->save();
        
        
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

    
}
