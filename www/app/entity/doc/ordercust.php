<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

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
                "price" => $value['price'],
                "msr" => $value['msr'],
                "amount" => $value['amount']
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->customer_name,
            "document_number" => $this->document_number,
            "total" => $this->headerdata["total"]
        );


        $report = new \App\Report('ordercust.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {

        return true;
    }

    public function getRelationBased() {
        $list = array();

        $list['GoodsReceipt'] = 'Приходная накладная';

        return $list;
    }

}
