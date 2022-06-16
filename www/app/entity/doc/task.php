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

    protected function init() {
        parent::init();
        // $this->tasktype = 0;//0 - услуги,1- производство
    }

    public function generateReport() {

        $i = 1;

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $ser) {
            if ($ser->cost == "") {
                $ser->cost = 0;
            }
            if ($ser->hours == "") {
                $ser->hours = 0;
            }
            $detail[] = array("no"           => $i++,
                              "service_name" => $ser->service_name,
                              "desc"         => $ser->desc,
                              "quantity"     => H::fqty($ser->quantity),
                              "cost"         => H::fa($ser->cost * $ser->quantity),
                              "hours"        => $ser->hours * $ser->quantity
            );
        }

        $detail2 = array();
        foreach ($this->unpackDetails('eqlist') as $eq) {


            $detail2[] = array(
                "eq_name" => $eq->eq_name,
                "code"    => $eq->code
            );
        }
        $detail3 = array();
        foreach ($this->unpackDetails('emplist') as $emp) {
            $detail3[] = array(
                "emp_name" => $emp->emp_name,
                "emp_ktu"  => $emp->ktu
            );
        }


        $detailprod = array();

        foreach ($this->unpackDetails('prodlist') as $item) {

            $detailprod[] = array("no"       => $i++,
                                  "itemname" => $item->itemname,
                                  "desc"     => $item->desc,
                                  "quantity" => H::fqty($item->quantity));
        }


        $header = array('date'            => H::fd($this->document_date),
                        "pareaname"       => strlen($this->headerdata["pareaname"]) > 0 ? $this->headerdata["pareaname"] : false,
                        "document_date"   => H::fd($this->document_date),
                        "document_number" => $this->document_number,
                        "notes"           => nl2br($this->notes),
                        "baseddoc"        => strlen($this->headerdata["parent_number"]) > 0 ? $this->headerdata["parent_number"] : false,
                        "cust"            => strlen($this->customer_name) > 0 ? $this->customer_name : false,
                        "_detail"         => $detail,
                        "_detailprod"     => $detailprod,
                        "_detail2"        => $detail2,
                        "iseq"            => count($detail2) > 0,
                        "_detail3"        => $detail3
        );
        $report = new \App\Report('doc/task.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();

        foreach ($this->unpackDetails('detaildata') as $ser) {

            $sc = new Entry($this->document_id, 0 - $ser->cost, $ser->qty);
            $sc->setService($ser->service_id);
            // $sc->save();
        }


        return true;
    }

    protected function getNumberTemplate() {
        return 'НР-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['ProdIssue'] = self::getDesc('ProdIssue');
        $list['ProdReceipt'] = self::getDesc('ProdReceipt');
        $list['ServiceAct'] = self::getDesc('ServiceAct');
        $list['POSCheck'] = self::getDesc('POSCheck');

        return $list;
    }

}
