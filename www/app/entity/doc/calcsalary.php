<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Entity\SalType;
use App\Helper as H;

/**
 * Класс-сущность  документ   начисление  зарплаты
 *
 */
class CalcSalary extends Document
{

    public function Execute() {


    
        return true;
    }

    public function generateReport() {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $emp) {


            $detail[] = array(
                "emp_name" => $emp->emp_name,
                "amount"   => H::fa($emp->amount)
            );
        }
        $header = array(
            "_detail"         => $detail,
            'total'           => H::fa($this->amount),
            'date'            => H::fd($this->document_date),
            "notes"           => nl2br($this->notes),
            "month"           => $this->headerdata["monthname"],
            "year"            => $this->headerdata["year"],
            "paymentname"     => $this->headerdata["paymentname"],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/calcsalary.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'НЗ-000000';
    }

}
