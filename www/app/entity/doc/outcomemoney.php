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

        $payed = Pay::addPayment($this->document_id, $this->document_date, 0 - $this->amount, $this->headerdata['payment'], $this->headerdata['type'], $this->notes);
        if ($payed > 0) {
            $this->payed = $payed;
        }

        return true;
    }

    public function generateReport() {

        $pt = Pay::getPayTypeList(2);

        $header = array(
            'amount'          => H::fa($this->amount),
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
        return 'РКО-000000';
    }

}
