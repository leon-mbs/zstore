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
    //  const PAY_CUSTOMER = 1;   //расчеты  с  контрагентм
    public const PAY_BANK       = 1000;   //эквайринг
 //   public const PAY_BONUS      = 1001;   //бонусы
    public const PAY_DELIVERY   = 1002;   //доставка
    public const PAY_COMISSION  = 1003;   //комиссия


    protected function init() {
        $this->pl_id = 0;
        $this->paytype = 0;
        $this->amount = 0;
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
     * @param mixed $nobank  без банковского  процента 
     */
    public static function addPayment($document_id, $paydate, $amount, $mf_id, $comment = '', $nobank=false) {
        $doc = \App\Entity\Doc\Document::load($document_id);
 
        \App\Entity\CustAcc::addBonus($doc, $amount);

        if (0 == (float)$amount || 0 == (int)$document_id || 0 == $mf_id) {
            return 0;
        }

        if ($mf_id == 0) {
            return 0;
        }

        $mf = \App\Entity\MoneyFund::load($mf_id);

        $options=\App\System::getOptions('common')  ;
        if($options['allowminusmf'] !=1 && $amount < 0) {
            $b = \App\Entity\MoneyFund::Balance() ;

            if($b[$mf_id] < abs($amount)) {
                throw new \Exception('Сума  на рахунку недостатня  для  оплати')  ;
            }
        }


        $pay = new Pay();
        $pay->mf_id = $mf_id;
        $pay->document_id = $document_id;
        $pay->amount = $amount;
        $pay->paytype = 0;
        $pay->paydate = $paydate;
        $pay->notes = $comment;
        $pay->user_id = \App\System::getUser()->user_id;
        $pay->save();


        if ($mf instanceof \App\Entity\MoneyFund) {
            //банковский процент


            if ($mf->beznal == 1 && $nobank==false) {
                if (($mf->btran > 0 && $amount < 0) || ($mf->btranin > 0 && $amount > 0)) {

                    $payb = new Pay();
                    $payb->mf_id = $mf_id;
                    $payb->document_id = $document_id;
                    $payb->paytype = Pay::PAY_BANK;
                    $payb->paydate = $paydate;
                    $payb->notes = 'Банківський процент за транзакцію';
                    $payb->user_id = \App\System::getUser()->user_id;

                    if ( $doc->meta_name=='ReturnIssue' && $mf->back == 1 ) {    //возврат
                        if (  $mf->btranin > 0  && $amount < 0) {    //возврат
                            $payb->amount =  (abs($amount) * $mf->btranin / 100);
                            \App\Entity\IOState::addIOState($document_id, $payb->amount, \App\Entity\IOState::TYPE_OTHER_INCOME);                        
                        }
                    } else {
                        
                        if ($mf->btran > 0  && $amount < 0) {    //со  счета
                            $payb->amount = 0- (abs($amount) * $mf->btran / 100);
                        }
                        if ($mf->btranin > 0 && $amount > 0) {  //на  счет
                            $payb->amount = 0- (abs($amount) * $mf->btranin / 100);
                        }
                        \App\Entity\IOState::addIOState($document_id, $payb->amount, \App\Entity\IOState::TYPE_BANK);                        
                    }
                    if($payb->amount != 0) {
                        $payb->save();          
                    }                                


                }
            }

            //комиссия
            if ($mf->com > 0  && $amount >0){
                    $payc = new Pay();
                    $payc->mf_id = $mf_id;
                    $payc->document_id = $document_id;
                    $payc->paytype = Pay::PAY_COMISSION;
                    $payc->paydate = $paydate;
                    $payc->notes = 'Комісія';
                    $payc->user_id = \App\System::getUser()->user_id;
                    $payc->amount = 0- ($amount * $mf->com / 100);                    
                    $payc->save(); 
 
                    \App\Entity\IOState::addIOState($document_id, $payc->amount, \App\Entity\IOState::TYPE_SALE_OUTCOME);                        
                    
            }

        }



        $conn = \ZDB\DB::getConnect();

        $sql = "select coalesce(abs(sum(amount)),0) from paylist_view where paytype < 1000  and  document_id=" . $document_id;
        $payed = $conn->GetOne($sql);
        $conn->Execute("update documents set payed={$payed} where   document_id =" . $document_id);
        return doubleval( $payed);
    }

    public static function cancelPayment($id, $comment) {
        $pl = Pay::load($id);
        if ($pl == null) {
            return;
        }

        $doc = \App\Entity\Doc\Document::load($pl->document_id);
        //сторно
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
