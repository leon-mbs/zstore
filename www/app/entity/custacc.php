<?php

namespace App\Entity;

/**
 * Клас-сущность  баланс  расчетов с контрагентами
 *
 * @table=custacc
 * @view=custacc_view
 * @keyfield=ca_id
 */
class CustAcc extends \ZCL\DB\Entity
{
 
    public const  BONUS = 1; // бонусы
 

    protected function init() {
        $this->ca_id = 0;
        $this->createdon = time();
    }

    protected function afterLoad() {
        $this->createdon = strtotime($this->createdon);
    }
 
     
   public static function addBonus($doc, $amount =0) {

        $conn = \ZDB\DB::getConnect();

        $customer_id = (int)$conn->GetOne("select  customer_id  from  documents where  document_id=" . $doc->document_id);
        if($customer_id ==0) {
            return;
        }
        $conn->Execute(" delete from  paylist where paytype= ".self::PAY_BONUS." and  document_id=" . $doc->document_id);

        $c = \App\Entity\Customer::load($customer_id);

        if(($doc->headerdata['pricetype']??'price1') != 'price1') {
            return;
        }


        $pastbonus = intval($doc->getBonus());
        if($pastbonus != 0) {
            return; //уже  начисленые
        }

        if($doc->meta_name == 'ReturnIssue') { //возврат

            if($doc->parent_id == 0) {
                return;
            }
            $parent = \App\Entity\Doc\Document::load($doc->parent_id);
            $parentbonus = intval($parent->getBonus(true)); //начислкно

            if($parentbonus==0) {
                return;
            }
            if($parent->headerdata['exch2b'] > 0) {
                $parentbonus = $parentbonus - $parent->headerdata['exch2b'];
            }
            $k = 1 - ($parent->amount - $doc->amount) / $parent->amount;

            $retbonus = intval($parentbonus * $k) ;// доля

            if($retbonus > 0) {
                $pay = new Pay();

                $pay->document_id = $doc->document_id;
                $pay->bonus = 0 -  $retbonus;
                $pay->paytype = Pay::PAY_BONUS;
                $pay->paydate = time();
                $pay->user_id = \App\System::getUser()->user_id;

                $pay->save();

            }

            return;
        }

        if(in_array($doc->meta_name, ['GoodsIssue','ServiceAct','Invoice','POSCheck','Order','OrderFood']) == false) {
            return;
        }

        $bonus = 0;

        if ($doc->headerdata['bonus'] > 0) { //списание


            $pay = new Pay();

            $pay->document_id = $doc->document_id;
            $pay->bonus = 0 -  $doc->headerdata['bonus'];
            $pay->paytype = Pay::PAY_BONUS;
            $pay->paydate = time();
            $pay->user_id = \App\System::getUser()->user_id;

            $pay->save();

            // return;
        }



        //сдачу в  бонусы
        if($doc->headerdata['exch2b'] > 0 && $doc->headerdata['exchange']>0) {
            if($doc->headerdata['exch2b'] > $doc->headerdata['exchange']) {
                $doc->headerdata['exch2b'] = $doc->headerdata['exchange']  ;
            }


            $pay = new Pay();

            $pay->document_id = $doc->document_id;

            $pay->amount = 0;
            $pay->bonus = (int)$doc->headerdata['exch2b'];
            if($doc->headerdata['exch2b'] > $doc->headerdata['exchange']) {
                $pay->bonus = (int)$doc->headerdata['exchange'];
            }
            $pay->paytype = Pay::PAY_BONUS;
            $pay->paydate = time();
            $pay->user_id = \App\System::getUser()->user_id;

            $pay->save();
        }



        if (doubleval($c->getDiscount()) > 0) { //если    скидка бонусы  не  начисляем
            return;
        }

        $bonus = 0;
        if($doc->payamount >0 && $amount > $doc->payamount) {
            $amount= $doc->payamount;
        }
        if($amount==0) {
            return;
        }

        $disc = \App\System::getOptions("discount");

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
            if ($disc["summa3"] > 0 && $disc["bonus3"] > 0 && $disc["summa3"] < $amount) {
                $bonus = round($amount * $disc["bonus3"] / 100);
            }
            if ($disc["summa4"] > 0 && $disc["bonus4"] > 0 && $disc["summa4"] < $amount) {
                $bonus = round($amount * $disc["bonus4"] / 100);
            }
        }

        $cust =   \App\Entity\Customer::load($customer_id);
        if(($cust->pbonus ?? 0) >0) {
            $bonus =   round($amount * $cust->pbonus/ 100); //постоянный  бонус
        }
        
        if ($bonus > 0) {


            $pay = new Pay();

            $pay->document_id = $doc->document_id;

       
            $pay->amount = 0;
            $pay->bonus = (int)$bonus;
            $pay->paytype = Pay::PAY_BONUS;
            $pay->paydate = time();
            $pay->user_id = \App\System::getUser()->user_id;

            $pay->save();

        }
    }
    
}
