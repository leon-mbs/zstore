<?php

namespace App\Entity\Doc;

use App\Entity\Entry;

/**
 * Класс-сущность  локумент акт  о  выполненных работах
 *
 *
 */
class ServiceAct extends Document
{

    public function generateReport() {

        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "servicename" => $value['service_name'],
                "quantity" => $value['quantity'],
                "price" => $value['price'],
                "amount" => $value['quantity'] * $value['price']
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "customer" => $this->customer_name,
            "document_number" => $this->document_number,
            "total" => $this->amount
        );
        $report = new \App\Report('serviceact.tpl');

        $html = $report->generate($header, $detail);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();

        foreach ($this->detaildata as $row) {

            $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
            $sc->setService($row['service_id']);

             
                $sc->setCustomer($this->customer_id);
            $sc->save();
        }
        $conn->CompleteTrans();

        return true;
    }

}
