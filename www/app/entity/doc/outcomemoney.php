<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность  документ расходный
 *
 */
class OutcomeMoney extends Document
{
    public function Execute() {


        $payed = Pay::addPayment($this->document_id, $this->document_date, 0 - $this->amount, $this->headerdata['payment'], $this->notes);
        if ($payed > 0) {
            $this->payed = $payed;
        }
        \App\Entity\IOState::addIOState($this->document_id, 0 - $this->amount, $this->headerdata['type']);

      $this->DoBalans() ;

        if ($this->headerdata['detail'] == 3) {  //перечисление  сотруднику
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::INCOME_FROM_MF;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $this->headerdata["emp"];
            $ua->amount = 0 - $this->amount;
            $ua->save();

        }

        return true;
    }

    public function generateReport() {


        $pt = \App\Entity\IOState::getTypeList(2);
        $header = array(
            'amount'          => H::fa($this->amount),
            'date'            => H::fd($this->document_date),
            "notes"           => nl2br($this->notes),
            "customer"        => $this->customer_id > 0 ? $this->customer_name : false,
            "contract"        => $this->headerdata["contract_id"] > 0 ? $this->headerdata["contract_number"] : false,
            "emp"             => strlen($this->headerdata["emp_name"]) > 0 ? $this->headerdata["emp_name"] : false,
            "from"            => $this->headerdata["paymentname"],
            "type"            => $pt[$this->headerdata["type"]],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/outcomemoney.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ВКО-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsReceipt'] = self::getDesc('GoodsReceipt');

        return $list;
    }
    public function DoBalans() {
             if($this->payed >0 && $this->headerdata['detail'] ==1 ) {
                $b = new \App\Entity\CustAcc();
                $b->customer_id = $this->customer_id;
                $b->document_id = $this->document_id;
                $b->amount = 0-$this->payed;
                $b->optype = \App\Entity\CustAcc::BUYER;
                $b->save();
            }
            if($this->payed >0 && $this->headerdata['detail'] ==2 ) {
                $b = new \App\Entity\CustAcc();
                $b->customer_id = $this->customer_id;
                $b->document_id = $this->document_id;
                $b->amount = 0-$this->payed;
                $b->optype = \App\Entity\CustAcc::SELLER;
                $b->save();
            }

    }    
}
