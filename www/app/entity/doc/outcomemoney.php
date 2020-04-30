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


        Pay::addPayment($this->document_id, $this->document_date, 0 - $this->amount, $this->headerdata['payment'], $this->headerdata['type'], $this->notes);


        return true;
    }

    public function generateReport() {

        $pt = Pay::getPayTypeList(2);

        $header = array(
            'amount' => H::fa($this->amount),
            'date' => date('d.m.Y', $this->document_date),
            "notes" => $this->notes,
            "from" => $this->headerdata["paymentname"],
            "type" => $pt[$this->headerdata["type"]],
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
