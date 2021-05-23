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

        $payed = Pay::addPayment($this->document_id, $this->document_date, $this->amount, $this->headerdata['payment'], $this->headerdata['type'], $this->notes);
        if ($payed > 0) {
            $this->payed = $payed;
        }


        return true;
    }

    public function generateReport() {

     
        $pt = \App\Entity\IOState::getTypeList(1) ;
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
