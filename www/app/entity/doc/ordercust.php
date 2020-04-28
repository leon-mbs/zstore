<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ приходная  накладая
 *
 */
class OrderCust extends Document
{

    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {
            $detail[] = array("no" => $i++,
                "itemname" => $item->itemname,
                "itemcode" => $item->item_code,
                "quantity" => H::fqty($item->quantity),
                "price" => H::fa($item->price),
                "msr" => $item->msr,
                "amount" => H::fa($item->quantity * $item->price)
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->customer_name,
            "document_number" => $this->document_number,
            "total" => H::fa($this->amount)
        );


        $report = new \App\Report('doc/ordercust.tpl');

        $html = $report->generate($header);

        return $html;
    }


    protected function getNumberTemplate() {
        return 'ЗКП-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsReceipt'] = 'Приходная накладная';
        $list['InvoiceCust'] = 'Счет от поставщика';

        return $list;
    }

}
