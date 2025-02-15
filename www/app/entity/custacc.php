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
    public const  BUYER = 2; // покупатель
    public const  SELLER = 3; // поставщик
 

    protected function init() {
        $this->ca_id = 0;
    }

    protected function afterLoad() {
        $this->createdon = strtotime($this->createdon);
    }
 
       //начисление  (списание)  бонусов  
   public static function addBonus($doc, $amount =0) {

        $conn = \ZDB\DB::getConnect();

        $customer_id = (int)$conn->GetOne("select  customer_id  from  documents where  document_id=" . $doc->document_id);
        if($customer_id ==0) {
            return;
        }
        $conn->Execute(" delete from  custacc where optype= ".CustAcc::BONUS." and  document_id=" . $doc->document_id);

        $c = \App\Entity\Customer::load($customer_id);

        if(($doc->headerdata['pricetype']??'price1') != 'price1') {
           // return;
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
            $parentbonus = intval($parent->getBonus(true)); //начислено

            if($parentbonus==0) {
                return;
            }
            if($parent->headerdata['exch2b'] > 0) {
                $parentbonus = $parentbonus - $parent->headerdata['exch2b'];
            }
            $k = 1 - ($parent->amount - $doc->amount) / $parent->amount;

            $retbonus = intval($parentbonus * $k) ;// доля

            if($retbonus > 0) {
                $b = new CustAcc();

                $b->customer_id = $customer_id;
                $b->document_id = $doc->document_id;
                $b->amount = 0 -  $retbonus;
                $b->optype = CustAcc::BONUS;
                $b->createdon = time();
              
                $b->save();

            }

            return;
        }

        if(in_array($doc->meta_name, ['Invoice','POSCheck','Order','OrderFood']) == false) {
            return;
        }

        $bonus = 0;

        if ($doc->headerdata['bonus'] > 0) { //списание


            $b = new CustAcc();

            $b->customer_id = $customer_id;
            $b->document_id = $doc->document_id;
            $b->amount = 0 -  $doc->headerdata['bonus'];
            $b->optype = CustAcc::BONUS;
            $b->createdon = time();

            $b->save();

            // return;
        }



        //сдачу в  бонусы
        if($doc->getHD('exch2b') > 0 && $doc->getHD('exchange')  ) {
            if($doc->headerdata['exch2b'] > $doc->headerdata['exchange']) {
                $doc->headerdata['exch2b'] = $doc->headerdata['exchange']  ;
            }


            $b = new CustAcc();
            $b->customer_id = $customer_id;
            $b->optype = CustAcc::BONUS;
            $b->createdon = time();
            $b->document_id = $doc->document_id;
 
   
            $b->amount = (int)$doc->headerdata['exch2b'];
            if($doc->headerdata['exch2b'] > $doc->headerdata['exchange']) {
                $b->amount = (int)$doc->headerdata['exchange'];
            }
 
            $b->save();
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

            $b = new CustAcc();
            $b->customer_id = $customer_id;
            $b->optype = CustAcc::BONUS;
            $b->createdon = time();
            $b->document_id = $doc->document_id;
            $b->amount = (int)$bonus;
 
            $b->save();

        }
    }
    
    
    //вместо  промотра  в  бд
    public  static function  get_acc_view($dt=0){
        $brdoc = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $brdoc = " and   document_id in(select  document_id from  documents dd where dd.branch_id in ({$brids}) )";
        }
        $createdon = "";
        if($dt >0) {
          $conn= \ZDB\DB::getConnect() ;
          $createdon = " and date(createdon) < " . $conn->DBDate($dt);
             
        }                   
            $cust_acc_view =" 
                SELECT
                  SUM(CASE WHEN amount > 0 AND       optype = 3 THEN amount ELSE 0 END) AS s_active,
                  SUM(CASE WHEN amount < 0 AND       optype = 3 THEN 0 - amount ELSE 0 END) AS s_passive,
                  SUM(CASE WHEN amount > 0 AND       optype = 2 THEN amount ELSE 0 END) AS b_active,
                  SUM(CASE WHEN amount < 0 AND       optype = 2 THEN 0 - amount ELSE 0 END) AS b_passive,

                  customer_id
                FROM custacc_view
                WHERE optype IN (2, 3)  {$brdoc}
                AND customer_id IN (SELECT    c.customer_id   FROM customers c    WHERE status = 0)
                {$createdon} 
                GROUP BY customer_id

                 ";
                
        return $cust_acc_view;
        
    }
        
}
