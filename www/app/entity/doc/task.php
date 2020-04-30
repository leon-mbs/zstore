<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

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


        foreach ($this->unpackDetails('detaildata') as $ser) {

            $detail[] = array("no" => $i++,
                "service_name" => $ser->service_name,
                "price" => H::fa($ser->price),
                "hours" => $ser->hours
            );
        }

        $detail2 = array();
        foreach ($this->unpackDetails('eqlist') as $eq) {


            $detail2[] = array(
                "eq_name" => $eq->eq_name,
                "code" => $eq->code
            );
        }
        $detail3 = array();
        foreach ($this->unpackDetails('emplist') as $emp) {
            $detail3[] = array(
                "emp_name" => $emp->emp_name
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "pareaname" => strlen($this->headerdata["pareaname"]) > 0 ? $this->headerdata["pareaname"] : false,
            "startdate" => date('d.m.Y', $this->headerdata["start_date"]),
            "document_number" => $this->document_number,
            "notes" => $this->notes,
            "baseddoc" => strlen($this->headerdata["parent_number"]) > 0 ? $this->headerdata["parent_number"] : false,
            "_detail" => $detail,
            "_detail2" => $detail2,
            "iseq" => count($detail2) > 0,
            "_detail3" => $detail3
        );
        $report = new \App\Report('doc/task.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->unpackDetails('detaildata') as $ser) {

            $sc = new Entry($this->document_id, 0 - $ser->price, $ser->quantity);
            $sc->setService($ser->service_id);
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
