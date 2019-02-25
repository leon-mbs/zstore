<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ гарантийного талон
 *
 */
class Warranty extends Document
{

    public function generateReport() {

        $detail = array();
        $total = 0;
        foreach ($this->detaildata as $value) {
            $detail[] = array(
                "tovar_name" => $value['itemname'],
                "quantity" => H::fqty($value['quantity']),
                "price" => $value['price'],
                "amount" => round($value['quantity'] * $value['price']),
                "sn" => $value['sn'],
                "msr" => $value['msr'],
                "warranty" => $value['warranty']
            );
            $total += $value['quantity'] * $value['price'];
        }

        $firm = \App\System::getOptions("firmdetail");


        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "firmname" => $firm['name'],
            "customer" => $this->customer_name,
            "document_number" => $this->document_number
        );

        $report = new \App\Report('warranty.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {

        return true;
    }

}
