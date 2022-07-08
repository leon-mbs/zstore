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

        if($this->headerdata['begval']==2) {
          $pay = new \App\Entity\Pay();
          $pay->mf_id = $this->headerdata['payment'];
          $pay->document_id = $this->document_id;
          $pay->amount = $this->amount;
          $pay->paytype = 0;
          $pay->paydate = $this->docunent_date;
          $pay->notes = $this->notes;
          $pay->user_id = \App\System::getUser()->user_id;
          $pay->save();
          
          return;
        }

        
        
        if( intval($this->headerdata['begval'])==0) {
    
            $payed = Pay::addPayment($this->document_id, $this->document_date, $this->amount, $this->headerdata['payment'],   $this->notes);
            if ($payed > 0) {
                $this->payed = $payed;
            }
            
           
            \App\Entity\IOState::addIOState($this->document_id, $this->amount, $this->headerdata['type']);
        }

        if ($this->headerdata['detail'] == 3) {  //Приход от сотрудника
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::OUTCOME_TO_MF;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $this->headerdata["emp"];
            $ua->amount = $this->amount;
            $ua->save();

        }

        return true;
    }

    public function generateReport() {


        $pt = \App\Entity\IOState::getTypeList(1);
        $header = array(
            'amount'          => H::fa($this->amount),
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

}
