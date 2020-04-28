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


        return true;
    }

    public function generateReport() {

        $pt = Pay::getPayTypeList(1);

        $header = array(
            'amount' => H::fa($this->amount),
            'date' => date('d.m.Y', $this->document_date),
            "notes" => $this->notes,
            "type" => $pt[$this->headerdata["type"]],
            "to" => $this->headerdata["paymentname"],
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
