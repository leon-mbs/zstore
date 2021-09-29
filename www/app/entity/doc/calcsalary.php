<?php

namespace App\Entity\Doc;

use App\Entity\SalType;
use App\Entity\Employee;
use App\Entity\EmpAcc;
use App\Helper as H;
use App\System;

/**
 * Класс-сущность  документ   начисление  зарплаты
 *
 */
class CalcSalary extends Document
{

    public function Execute() {
        $opt = System::getOptions("salary");

        $code = "_c" . $opt['coderesult'];


        foreach ($this->unpackDetails('detaildata') as $emp) {
            $am = $emp->{$code};
            $eacc = new  EmpAcc();

            $eacc->emp_id = $emp->employee_id;
            $eacc->document_id = $this->document_id;
            $eacc->optype = EmpAcc::SALARY;
            $eacc->amount = $am;
            $eacc->save();
        }

        return true;
    }

    public function generateReport() {
        $stlist = SalType::find("disabled<>1", "salcode");

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $emp) {


            $det = array(
                "emp_name" => $emp->emp_name,
                "amounts"  => array()
            );
            foreach ($stlist as $c => $n) {
                $code = "_c" . $n->salcode;
                $det['amounts'][] = array('am' => H::fa($emp->{$code}));
            }


            $detail[] = $det;
        }
        $header = array(
            "_detail" => $detail,
            'total'   => H::fa($this->amount),
            'date'    => H::fd($this->document_date),
            "notes"   => nl2br($this->notes),
            "month"   => $this->headerdata["monthname"],
            "year"    => $this->headerdata["year"],
            "stnames" => array(),
            "colspan" => count($stlist) + 1,

            "document_number" => $this->document_number
        );


        foreach ($stlist as $c => $n) {
            $header['stnames'][] = array('name' => $n->salshortname);
        }


        $report = new \App\Report('doc/calcsalary.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'НЗ-000000';
    }

}
