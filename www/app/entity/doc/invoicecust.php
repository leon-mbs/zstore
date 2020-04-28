<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ счет от поставщика
 *
 */
class InvoiceCust extends Document
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
            "total" => H::fa($this->amount),
            "payed" => H::fa($this->payed),
            "payamount" => H::fa($this->payamount)
        );

        $header['isdisc'] = $this->headerdata["disc"] > 0;
        $header['isnds'] = $this->headerdata["nds"] > 0;
        $header['israte'] = ($this->headerdata["rate"] != 0) && ($this->headerdata["rate"] != 1);
        $header['disc'] = H::fa($this->headerdata["disc"]);
        $header['nds'] = H::fa($this->headerdata["nds"]);
        $header['rate'] = $this->headerdata["rate"];

        $report = new \App\Report('doc/invoicecust.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {

        if ($this->headerdata['payment'] > 0 && $this->payed) {
            \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME);
        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'СВ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsReceipt'] = 'Приходная накладная';

        return $list;
    }

}
