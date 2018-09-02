<?php

namespace App\Entity\Doc;

use App\Entity\Entry;

/**
 * Класс-сущность  наряд
 *
 *
 */
class Task extends Document
{

    public function generateReport() {

        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            if($value['eq_id']>0)continue;
            $detail[] = array("no" => $i++,
                "servicename" => $value['service_id'] > 0 ? $value['service_name'] : $value['itemname'],
                "quantity" => $value['quantity'],
                "price" => $value['price'],
                "amount" => $value['quantity'] * $value['price']
            );
        }
       $detail2 = array();
        foreach ($this->detaildata as $value) {
            if($value['eq_id']>0){
                $detail2[] = array( 
                    "eq_name" => $value['eq_name'],
                    "code" => $value['code'] 
                );
            }
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "customer" => $this->headerdata["customer_name"],
            "startdate" => date('d.m.Y', $this->headerdata["start_date"]),
            "document_number" => $this->document_number,
            "totaldisc" => $this->headerdata["totaldisc"],
            "total" => $this->amount
        );
        $report = new \App\Report('task.tpl');

        $html = $report->generate($header, $detail,$detail2);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();

        foreach ($this->detaildata as $row) {


            $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
            if ($row['stock_id'] > 0) {
                $sc->setStock($row['stock_id']);
                if ($row['custpay'] == 1 && $this->headerdata["customer"] > 0)
                    $sc->setCustomer($this->headerdata["customer"]);
            }
            if ($row['service_id'] > 0) {
                $sc->setService($row['service_id']);
                if ($this->headerdata["customer"] > 0)
                    $sc->setCustomer($this->headerdata["customer"]);
            }

            if ($row['employee_id'] > 0) {
                $sc = new Entry($this->document_id, $row['pay']);

                $sc->setEmployee($row['employee_id']);
            }



            $sc->save();
        }

        $conn->CompleteTrans();

        return true;
    }

}
