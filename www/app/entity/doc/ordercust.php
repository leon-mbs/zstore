<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;

/**
 * Класс-сущность  документ приходная  накладая
 *
 */
class OrderCust extends Document {

    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "itemname" => $value['itemname'],
                "itemcode" => $value['item_code'],
                "quantity" => H::fqty($value['quantity']),
                "price" => H::fa($value['price']),
                "msr" => $value['msr'],
                "amount" => H::fa($value['quantity'] * $value['price'])
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->headerdata["customer_name"],
            "document_number" => $this->document_number,
            "total" => H::fa($this->amount)
        );


        $report = new \App\Report('ordercust.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {

        return true;
    }

    protected function getNumberTemplate() {
        return 'ЗКП-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsReceipt'] = 'Приходная накладная';
        $list['InvoiceCust']  = 'Счет от поставщика';

        return $list;
    }      
}
