<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность  документ приходный ордер
 *
 */
class IncomeMoney extends Document
{
    public function Execute() {

   

        $this->payed = Pay::addPayment($this->document_id, $this->document_date, $this->amount, $this->headerdata['payment'], $this->notes);
   

        if ($this->headerdata['detail'] == 1)  {    // оплата от покупателя
             \App\Entity\IOState::addIOState($this->document_id,    $this->amount,  \App\Entity\IOState::TYPE_BASE_INCOME);
        }  else   
        if ($this->headerdata['detail'] == 2)  {    // возврат от поставщика
             \App\Entity\IOState::addIOState($this->document_id,   $this->amount, \App\Entity\IOState::TYPE_BASE_OUTCOME, true);
        } else {
            \App\Entity\IOState::addIOState($this->document_id,   $this->amount, $this->headerdata['type']);
            
        }  
            


         $this->DoBalans() ;

        if ($this->headerdata['detail'] == 3) {  //Приход от сотрудника
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::OUTCOME_TO_MF;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $this->headerdata["emp"];
            $ua->amount = $this->amount;
         //   $ua->save();  

        }

        return true;
    }

    public function generateReport() {


        $pt = \App\Entity\IOState::getTypeListInM();
        $header = array(
            'amount'          => H::fa($this->amount),
            'totalstr'        => \App\Util::money2str_ua($this->amount),
            'date'            => H::fd($this->document_date),
            "notes"           => nl2br($this->notes),
            "customer"        => $this->customer_id > 0 ? $this->customer_name : false,
            "contract"        => $this->headerdata["contract_id"] > 0 ? $this->headerdata["contract_number"] : false,
            "emp"             => strlen($this->headerdata["emp_name"]) > 0 ? $this->headerdata["emp_name"] : false,
            "type"            => $pt[$this->headerdata["type"]],
            "to"              => $this->headerdata["paymentname"],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/incomemoney.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПКО-000000';
    }


    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');

        return $list;
    }

    /**
    * @override
    */
    public function DoBalans() {
          $conn = \ZDB\DB::getConnect();
          $conn->Execute("delete from custacc where optype in (2,3) and document_id =" . $this->document_id);
 
         if(($this->customer_id??0) == 0) {
            return;
         }

                 //платежи       
        foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where paytype < 1000 and coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){

          //оплата  от покупателя
          if($this->payed >0 && $this->headerdata['detail'] ==1 ) {
                $b = new \App\Entity\CustAcc();
                $b->customer_id = $this->customer_id;
                $b->document_id = $this->document_id;
                $b->amount = $p['amount'];
                $b->optype = \App\Entity\CustAcc::BUYER;
                $b->createdon = strtotime($p['paydate']);
                $b->save();
            }
            //возврат  от поставщика
            if($this->payed >0 && $this->headerdata['detail'] ==2 ) {
                $b = new \App\Entity\CustAcc();
                $b->customer_id = $this->customer_id;
                $b->document_id = $this->document_id;
                $b->amount = $p['amount'];
                $b->optype = \App\Entity\CustAcc::SELLER;
                $b->createdon = strtotime($p['paydate']);
                $b->save();
            }
        }
          $this->DoAcc();
 
    }
    
 public   function DoAcc() {
         if(\App\System::getOption("common",'useacc')!=1 ) return;
         parent::DoAcc()  ;
      
         $mf=  \App\Entity\MoneyFund::load($this->headerdata['payment']) ;
         $n=  $mf->beznal ?'31':'30' ;
         
       
         if ($this->headerdata['detail'] == 1)  {    // оплата от покупателя
              $this->DoAccPay('36');
         }  else   
         if ($this->headerdata['detail'] == 2)  {    // возврат от поставщика
              $this->DoAccPay('63',true);
         } else {
             if($this->headerdata['type']== \App\Entity\IOState::TYPE_FIN) {
                \App\Entity\AccEntry::addEntry( '71', $n,  $this->amount,$this->document_id )  ; 
             }
             if($this->headerdata['type']== \App\Entity\IOState::TYPE_OTHER_INCOME) {
                \App\Entity\AccEntry::addEntry( '71', $n,  $this->amount,$this->document_id )  ; 
             }
         } 
            
                         
    } 
       
}
