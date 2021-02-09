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
  
        Pay::addPayment($this->document_id, $this->document_date, $this->amount, $this->headerdata['payment'], $this->headerdata['type'], $this->notes);

        
        if( $this->headerdata['contract_id']>0) {
            $amount =  $this->amount;
            $c = \App\Entity\Contract::load($this->headerdata['contract_id']);    
            foreach($c->getDocs()  as $doc){
                 if($doc->payamount >0 && $doc->payamount > $doc->payed )  {
                     $p = $doc->payamount - $doc->payed;
                     if($amount  >$p) {
                         $doc->payed += $p;
                       //  $doc->save();
                         $amount  -= $p;
                     } else {
                        $doc->payed +=  $amount ;
                      //  $doc->save();
                        break;
                     
                     }
                     
                 }
                
            }
            
        }
        
        
        return true;
    }

    public function generateReport() {

        $pt = Pay::getPayTypeList(1);

        $header = array(
            'amount'          => H::fa($this->amount),
            'date'            => H::fd($this->document_date),
            "notes"           => $this->notes,
            "customer"        => $this->customer_id > 0 ? $this->customer_name : false,
            "contract"        => $this->headerdata["contract_id"] > 0 ? $this->headerdata["contract_number"]: false,
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

}
