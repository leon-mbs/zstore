<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность  документ перемещение денег
 *
 */
class MoveMoney extends Document
{
    public function Execute() {

        Pay::addPayment($this->document_id, $this->document_date, 0 - $this->amount, $this->headerdata['paymentfrom'], $this->notes, true);
        Pay::addPayment($this->document_id, $this->document_date, $this->amount, $this->headerdata['paymentto'], $this->notes, true);
        $this->DoAcc();  
  
        return true;
    }

    public function generateReport() {


        $header = array(
            'amount'          => H::fa($this->amount),
            'date'            => H::fd($this->document_date),
            "notes"           => nl2br($this->notes),
            "from"            => $this->headerdata["paymentfromname"],
            "to"              => $this->headerdata["paymenttoname"],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/movemoney.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПК-000000';
    }
    public   function DoAcc() {
       if(\App\System::getOption("common",'useacc')!=1 ) return;
       parent::DoAcc()  ;
  
       $this->DoAccPay(null)  ;
  
    }
 
}
