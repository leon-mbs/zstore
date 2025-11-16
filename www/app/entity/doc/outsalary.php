<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Entity\EmpAcc;
use App\Helper as H;

/**
 * Класс-сущность  документ   выплата  зарплаты
 *
 */
class OutSalary extends Document
{
    public function Execute() {

         $dt = new \App\DateTime(strtotime($this->headerdata["year"] . '-' . $this->headerdata["month"] . '-01'));
         $to = $dt->endOfMonth()->getTimestamp();
        
        
        foreach ($this->unpackDetails('detaildata') as $emp) {
            if ($emp->amount > 0) {
                $eacc = new \App\Entity\EmpAcc();
                $eacc->emp_id = $emp->employee_id;
                $eacc->document_id = $this->document_id;
                $eacc->createdon = $to;

                $eacc->optype = $this->headerdata['advance'] == 1 ? EmpAcc::ADVANCE : EmpAcc::SALARY_PAY;
                $eacc->amount = 0 - $emp->amount;
                $eacc->save();
            }
        }
        $this->payed = Pay::addPayment($this->document_id, $this->document_date, 0 - $this->amount, $this->headerdata['payment'], $this->notes);
    
        \App\Entity\IOState::addIOState($this->document_id, 0 - $this->amount, \App\Entity\IOState::TYPE_SALARY_OUTCOME);
        $this->DoAcc() ;
 
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
            "advance"         => $this->headerdata["advance"],
            "month"           => $this->headerdata["monthname"],
            "year"            => $this->headerdata["year"],
            "paymentname"     => $this->headerdata["paymentname"],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/outsalary.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ВЗ-000000';
    }
    public   function DoAcc() {
             if(\App\System::getOption("common",'useacc')!=1 ) return;
             parent::DoAcc()  ;

             $this->DoAccPay('66');
               
    }
}
