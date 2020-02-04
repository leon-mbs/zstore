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
        $eqlist = unserialize(base64_decode($this->headerdata['eq']));
        $emplist = unserialize(base64_decode($this->headerdata['emp']));


        foreach ($this->detaildata as $value) {

            $detail[] = array("no" => $i++,
                "servicename" => $value['service_name'],
                "price" => H::fa($value['price']),
                "hours" => $value['hours']
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



        $header = array('date' => date('d.m.Y', $this->document_date),
            "pareaname" => $this->headerdata["pareaname"],
            "startdate" => date('d.m.Y', $this->headerdata["start_date"]),
            "document_number" => $this->document_number,
            "notes" => $this->notes,
            "baseddoc" => strlen($this->headerdata["parent_number"])>0 ? $this->headerdata["parent_number"] : false,
            "_detail" => $detail,
            "_detail2" => $detail2,
            "_detail3" => $detail3
        );
        $report = new \App\Report('task.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();

        $eqlist = unserialize(base64_decode($this->headerdata['eq']));
        $emplist = unserialize(base64_decode($this->headerdata['emp']));

        foreach ($this->detaildata as $row) {
            $sc = new Entry($this->document_id, 0 - $row['price'], 0);
            $sc->setService($row['service_id']);
            $sc->save();
        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'НР-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['ProdIssue'] = 'Cписание в  производство ';
    
        return $list;
    }    
}
