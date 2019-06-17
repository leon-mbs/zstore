<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;

/**
 * Класс-сущность  локумент акт  о  выполненных работах
 *
 *
 */
class ServiceAct extends Document {

    public function generateReport() {

        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "servicename" => $value['service_name'],
                "desc" => $value['desc'],
                "quantity" => $value['quantity'],
                "price" => $value['price'],
                "amount" => $value['quantity'] * $value['price']
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer" => $this->customer_name,
            "order" => $this->headerdata["order"],
            "gar" => $this->gar,
            "document_number" => $this->document_number,
            "total" => $this->amount
        );
        $report = new \App\Report('serviceact.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->detaildata as $row) {

            $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
            $sc->setService($row['service_id']);
            $sc->setExtCode($row['amount']); //Для АВС 


            $sc->setCustomer($this->customer_id);
            $sc->save();
        }
        if ($this->headerdata['payment'] > 0) {
            \App\Entity\Pay::addPayment($this->document_id, $this->amount, $this->headerdata['payment'], $this->headerdata['paynotes']);
            $this->payamount = $this->amount;
        }



        return true;
    }

}
