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
        foreach ($this->unpackDetails('detaildata') as $item) {
            $detail[] = array(
                "tovar_name" => $item->itemname,
                "quantity"   => H::fqty($item->quantity),
                "price"      => H::fa($item->price),
                "amount"     => H::fa($item->quantity * $item->price),
                "sn"         => $item->sn,
                "msr"        => $item->msr,
                "warranty"   => $item->warranty
            );
            $total += $item->quantity * $item->price;
        }


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "notes"           => nl2br($this->notes),
                        "firm_name"       => $this->headerdata["firm_name"],
                        "customer_name"   => $this->customer_name,
                        "document_number" => $this->document_number
        );

        $report = new \App\Report('doc/warranty.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ГТ-000000';
    }

}
