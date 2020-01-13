<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;

/**
 * Класс-сущность  наряд
 *
 *
 */
class Task extends Document {

    public function generateReport() {

        $i = 1;

        $detail = array();
        $parts = unserialize(base64_decode($this->headerdata['parts']));
        $items = unserialize(base64_decode($this->headerdata['items']));
        $eqlist = unserialize(base64_decode($this->headerdata['eq']));
        $emplist = unserialize(base64_decode($this->headerdata['emp']));

        foreach ($this->detaildata as $value) {

            $detail[] = array("no" => $i++,
                "servicename" => $value['service_id'] > 0 ? $value['service_name'] : $value['itemname'],
                "quantity" => H::fqty($value['quantity']),
                "price" => H::fa($value['price']),
                "hours" => $value['hours'],
                "amount" => H::fa($value['quantity'] * $value['price'])
            );
        }
        foreach ($parts as $value) {
            $value = $value->getData();
            $detail[] = array("no" => $i++,
                "servicename" => $value['itemname'],
                "quantity" => H::fqty($value['quantity']),
                "price" => H::fa($value['price']),
                "amount" => H::fa($value['quantity'] * $value['price'])
            );
        }
        $detail2 = array();
        foreach ($eqlist as $value) {
            $value = $value->getData();

            $detail2[] = array(
                "eq_name" => $value['eq_name'],
                "code" => $value['code']
            );
        }
        $detail3 = array();
        foreach ($emplist as $value) {
            $value = $value->getData();

            $detail3[] = array(
                "emp_name" => $value['emp_name']
            );
        }

        $i = 1;

        $detail5 = array();
        foreach ($items as $value) {
            $value = $value->getData();

            $detail5[] = array("no" => $i++,
                "itemname" => $value['itemname'],
                "quantity" => H::fqty($value['quantity']),
                "price" => $value['price'],
                "amount" => round($value['quantity'] * $value['price'])
            );
        }


        $header = array('date' => date('d.m.Y', $this->document_date),
            "pareaname" => $this->headerdata["pareaname"],
            "customer_name" => $this->headerdata["customer_name"],
            "startdate" => date('d.m.Y', $this->headerdata["start_date"]),
            "document_number" => $this->document_number,
            "total" => $this->amount,
            "payamount" => $this->payamount,
            "payed" => $this->payed,
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

        $parts = unserialize(base64_decode($this->headerdata['parts']));
        $items = unserialize(base64_decode($this->headerdata['items']));
        $eqlist = unserialize(base64_decode($this->headerdata['eq']));
        $emplist = unserialize(base64_decode($this->headerdata['emp']));

        foreach ($this->detaildata as $row) {
            $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
            $sc->setService($row['service_id']);
            $sc->save();
        }
        foreach ($parts as $items) {
            $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
            $sc->setStock($row['stock_id']);
            $sc->save();
        }
        foreach ($items as $row) {
            $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
            $sc->setStock($row['stock_id']);
            $sc->save();
        }




        return true;
    }

    public function updateStatus($state) {

        parent::updateStatus($state);

        if ($state != Document::STATE_EXECUTED && $state != Document::STATE_INPROCESS) {
            return;
        }

        $conn = \ZDB\DB::getConnect();

        //была  ли  оплата
        $cnt = $conn->GetOne("select coalesce(count(*),0) from paylist where document_id={$this->document_id} and indoc=1");

        if ($cnt > 0)
            return;

   
        if ($this->headerdata['payment'] > 0 && $this->payed>0) {
            \App\Entity\Pay::addPayment($this->document_id, 1, $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME );
         
        }
    }

    protected function getNumberTemplate() {
        return 'НР-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = 'Гарантийный талон';

        return $list;
    }

}
