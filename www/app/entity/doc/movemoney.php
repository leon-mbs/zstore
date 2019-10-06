<?php

namespace App\Entity\Doc;

use \App\Entity\MoneyFund;
use \App\Entity\Pay;
use \App\Helper as H;

/**
 * Класс-сущность  документ перемещение денег
 *
 */
class MoveMoney extends Document {

    public function Execute() {


        Pay::addPayment($this->document_id, 0 - $this->amount, $this->headerdata['mffrom'],0, $this->notes);
        Pay::addPayment($this->document_id, 1,$this->amount, 1,$this->headerdata['mfto'],0, notes);


        return true;
    }

    public function generateReport() {


        $header = array(
            'amount' => $this->amount,
            'date' => date('d.m.Y', $this->document_date),
            "from" => $this->headerdata["mffromname"],
            "to" => $this->headerdata["mftoname"],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('movemoney.tpl');

        $html = $report->generate($header);

        return $html;
    }


    protected function getNumberTemplate(){
         return  'ДД-000000';
    }      
}
