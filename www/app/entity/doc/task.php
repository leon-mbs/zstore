<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  наряд
 *
 *
 */
class Task extends Document {

    public function generateReport() {

        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            if ($value['eq_id'] > 0)
                continue;
            if ($value['employee_id'] > 0)
                continue;
            if ($value['item5_id'] > 0)
                continue;
            $detail[] = array("no" => $i++,
                "servicename" => $value['service_id'] > 0 ? $value['service_name'] : $value['itemname'],
                "quantity" => H::fqty($value['quantity']),
                "price" => $value['price'],
                "amount" => round($value['quantity'] * $value['price'])
            );
        }
        $detail2 = array();
        foreach ($this->detaildata as $value) {
            if ($value['eq_id'] > 0) {
                $detail2[] = array(
                    "eq_name" => $value['eq_name'],
                    "code" => $value['code']
                );
            }
        }
        $detail3 = array();
        foreach ($this->detaildata as $value) {
            if ($value['employee_id'] > 0) {
                $detail3[] = array(
                    "emp_name" => $value['emp_name']
                );
            }
        }

        $i = 1;

        $detail5 = array();
        foreach ($this->detaildata as $value) {
            if ($value['eq_id'] > 0)
                continue;
            if ($value['employee_id'] > 0)
                continue;
            if (strlen($value['item5_id']) == 0)
                continue;
            $detail5[] = array("no" => $i++,
                "itemname" => $value['itemname'],
                "quantity" => H::fqty($value['quantity']),
                "price" => $value['price'],
                "amount" => round($value['quantity'] * $value['price'])
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "pareaname" => $this->headerdata["pareaname"],
            "customer" => $this->customer_name,
            "startdate" => date('d.m.Y', $this->headerdata["start_date"]),
            "document_number" => $this->document_number,
            "totaldisc" => $this->headerdata["totaldisc"],
            "total" => $this->amount,
            "_detail" => $detail,
            "_detail2" => $detail2,
            "_detail5" => $detail5,
            "_detail3" => $detail3
        );
        $report = new \App\Report('task.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();

        foreach ($this->detaildata as $row) {

            if (strlen($row['item5_id']) == 0) {
                $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
                if ($row['item_id'] > 0) {
                    $sc->setStock($row['stock_id']);
                    $sc->setCustomer($this->customer_id);
                }
                if ($row['service_id'] > 0) {
                    $sc->setService($row['service_id']);

                    $sc->setCustomer($this->customer_id);
                }

                if ($row['employee_id'] > 0) {
                    $sc = new Entry($this->document_id, $row['pay']);

                    $sc->setEmployee($row['employee_id']);
                }



                $sc->save();
            }
            //материалы 
            if ($row['item5_id'] > 0) {
                $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
                $sc->setStock($row['stock_id']);
                $sc->save();
            }
        }

        $conn->CompleteTrans();

        return true;
    }

}
