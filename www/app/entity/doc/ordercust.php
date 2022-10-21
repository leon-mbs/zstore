<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ  заявка  поставщику
 *
 */
class OrderCust extends Document
{

    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {
            $detail[] = array("no"       => $i++,
                              "itemname" => $item->itemname,
                              "itemcode" => $item->item_code,
                              "custcode" => $item->custcode,
                              "quantity" => H::fqty($item->quantity),
                              "price"    => H::fa($item->price),
                              "msr"      => $item->msr,
                              "desc"     => $item->desc,
                              "amount"   => H::fa($item->quantity * $item->price)
            );
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "notes"           => nl2br($this->notes),
                       "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount)
        );

        $report = new \App\Report('doc/ordercust.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ЗП-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsReceipt'] = self::getDesc('GoodsReceipt');
        $list['InvoiceCust'] = self::getDesc('InvoiceCust');

        return $list;
    }

}
