<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность  документ договор
 *
 */
class Contract extends Document
{
 
    public function generateReport() {

         
        $header = array(
         
            'date'            => H::fd($this->document_date),
            'dateend'            => H::fd($this->headerdata["enddate"]),
            "notes"           => $this->notes,
            "customer"        =>  $this->customer_name  ,
            "emp"             => strlen($this->headerdata["emp_name"]) > 0 ? $this->headerdata["emp_name"] : false,
            "comp"            => $this->firm_name,
        
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/contract.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'Д-000000';
    }

}
