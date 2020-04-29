<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность  документ   выплата  зарплаты
 *
 */
class OutSalary extends Document
{

    public function Execute() {


        Pay::addPayment($this->document_id, $this->document_date, 0 - $this->amount, $this->headerdata['payment'], \App\Entity\Pay::PAY_SALARY_OUTCOME);

        return true;
    }

    public function generateReport() {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $emp) {


            $detail[] = array(
                "emp_name" => $emp->emp_name,
                "amount" => H::fa($emp->amount)
            );
        }
        $header = array(
            "_detail" => $detail,
            'total' => H::fa($this->amount),
            'date' => date('d.m.Y', $this->document_date),
            "notes" => $this->notes,
            "month" => $this->headerdata["monthname"],
            "year" => $this->headerdata["year"],
            "paymentname" => $this->headerdata["paymentname"],

            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/outsalary.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ВЗ-000000';
    }

}
