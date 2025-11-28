<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность  документ расходный
 *
 */
class OutcomeMoney extends Document
{
    public function Execute() {


        $this->payed = Pay::addPayment($this->document_id, $this->document_date, 0 - $this->amount, $this->headerdata['payment'], $this->notes);
       
        if ($this->headerdata['detail'] == 1)  {    // возврат  покупателю
             \App\Entity\IOState::addIOState($this->document_id,   0-$this->amount,  \App\Entity\IOState::TYPE_BASE_INCOME, true);
        }  else   
        if ($this->headerdata['detail'] == 2)  {    // оплата  поставщику
             \App\Entity\IOState::addIOState($this->document_id, 0 - $this->amount, \App\Entity\IOState::TYPE_BASE_OUTCOME);
        } else  
        if ($this->headerdata['detail'] == 3)  {    // сотруднику в подотчет
      
        } else {
            \App\Entity\IOState::addIOState($this->document_id, 0 - $this->amount, $this->headerdata['type']);
            
        }  
      
        $this->DoBalans() ;

        if ($this->headerdata['detail'] == 3) {  //перечисление  сотруднику в  подотчет
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::ADVANCE_ACC;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $this->headerdata["emp"];
            $ua->amount = 0 - $this->amount;
            $ua->save();

        }

        return true;
    }

    public function generateReport() {

     
        $pt = \App\Entity\IOState::getTypeListOutM();
        $header = array(
            'amount'          => H::fa($this->amount),
            'totalstr'        => \App\Util::money2str_ua($this->amount),
            'date'            => H::fd($this->document_date),
            "notes"           => nl2br($this->notes),
            "customer"        => $this->customer_id > 0 ? $this->customer_name : false,
            "contract"        => $this->headerdata["contract_id"] > 0 ? $this->headerdata["contract_number"] : false,
            "emp"             => strlen($this->headerdata["emp_name"]) > 0 ? $this->headerdata["emp_name"] : false,
            "from"            => $this->headerdata["paymentname"],
            "type"            => $pt[$this->headerdata["type"]],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/outcomemoney.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ВКО-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsReceipt'] = self::getDesc('GoodsReceipt');

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
 
       foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where paytype < 1000 and coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
 
             // опдата поставщику
             if($this->payed >0 && $this->headerdata['detail'] ==1 ) {
                $b = new \App\Entity\CustAcc();
                $b->customer_id = $this->customer_id;
                $b->document_id = $this->document_id;
                $b->amount = 0-$p['amount'];
                $b->optype = \App\Entity\CustAcc::BUYER;
                $b->createdon = strtotime($p['paydate']);
                $b->save();
            }
            //возврат покупателя
            if($this->payed >0 && $this->headerdata['detail'] ==2 ) {
                $b = new \App\Entity\CustAcc();
                $b->customer_id = $this->customer_id;
                $b->document_id = $this->document_id;
                $b->amount = 0-$p['amount'];
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
         
        if ($this->headerdata['detail'] == 1)  {    // возврат  покупателю
             $this->DoAccPay('36',true);
        }  else   
        if ($this->headerdata['detail'] == 2)  {    // оплата  поставщику
             $this->DoAccPay('63' );
        } else  
        if ($this->headerdata['detail'] == 3)  {    // подотчет
            $this->DoAccPay('372',$n);
        } else {
             $acc='';
             
             switch($this->headerdata['type'])  {
                 case \App\Entity\IOState::TYPE_COMMON_OUTCOME:  
                   $acc='91';
                   break;
                 case \App\Entity\IOState::TYPE_ADMIN_OUTCOME:  
                   $acc='92';
                   break;
                 case \App\Entity\IOState::TYPE_SALE_OUTCOME:  
                   $acc='93';
                   break;
                 case \App\Entity\IOState::TYPE_TAX_NDS:  
                 case \App\Entity\IOState::TYPE_TAX_OUTCOME:  
                   $acc='641';
                   break;
                 case \App\Entity\IOState::TYPE_TAX_CARE:  
                   $acc='645';
                   break;
                 case \App\Entity\IOState::TYPE_BILL_OUTCOME:  
                   $acc='943';
                   break;
                 case \App\Entity\IOState::TYPE_BILL_RENT:  
                   $acc='944';
                   break;
                 case \App\Entity\IOState::TYPE_DIVIDEND_OUTCOME:  
                   $acc='44';
                   break;
                 case \App\Entity\IOState::TYPE_INV:  
                   $acc='15';
                   break;
                 case \App\Entity\IOState::TYPE_NAKL:  
                   $acc='941';
                   break;
               case \App\Entity\IOState::TYPE_ADS:  
                   $acc='93';
                   break;
               case \App\Entity\IOState::TYPE_BANK:  
                   $acc='949';
                   break;
               case \App\Entity\IOState::TYPE_SALARY_OUTCOME:  
                   $acc='66';
                   break;
               case \App\Entity\IOState::TYPE_OTHER_OUTCOME:  
                   $acc='97';
                   break;
//               case \App\Entity\IOState::TYPE_OTHER_OUTCOME:  
//                   $acc='99';
//                   break;
                 
             }
 
     
  
             if($acc !='') {
                 \App\Entity\AccEntry::addEntry( $acc, $n, $this->amount,$this->document_id )  ; 
             }
        }        
        
            
                         
    } 
     
      
}
