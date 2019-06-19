<?php

namespace App\Entity\Doc;

use \App\Entity\MoneyFund;
use \App\Entity\Pay;
use \App\Helper as H;

/**
 * Класс-сущность  документ приходный ордер
 *
 */
class IncomeMoney extends Document {

    public function Execute() {


        Pay::addPayment($this->document_id, $this->amount, $this->headerdata['mfto'], notes);


        return true;
    }

    public function generateReport() {


        $header = array(
            'amount' => $this->amount,
            'date' => date('d.m.Y', $this->document_date),
            "notes" => $this->notes,
            "to" => $this->headerdata["mftoname"],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('incomemoney.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
