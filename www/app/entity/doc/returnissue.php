<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ возвратная накладная
 *
 */
class ReturnIssue extends Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {
                $name = $item->itemname;
                if (strlen($item->snumber) > 0) {
                    $name .= ' (' . $item->snumber . ',' . date('d.m.Y', $item->sdate) . ')';
                }

                $detail[] = array("no" => $i++,
                    "tovar_name" => $name,
                    "quantity" => H::fqty($item->quantity),
                    "price" => H::fa($item->price),
                    "msr" => $item->msr,
                    "amount" => H::fa($item->quantity * $item->price)
                );
            }
        }


        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "firmname" => $this->headerdata["firmname"],
            "customer_name" => $this->customer_name,
            "document_number" => $this->document_number,
            "total" => H::fa($this->amount),
            "payed" => H::fa($this->payed)
        );

        $report = new \App\Report('doc/returnissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->unpackDetails('detaildata') as $item) {
            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $item->price, $item->snumber, $item->sdate, true);


            $sc = new Entry($this->document_id, $item->amount, $item->quantity);
            $sc->setStock($stock->stock_id);

            $sc->setExtCode(0 - ($item->price - $stock->partion)); //Для АВС 
            //  $sc->setCustomer($this->customer_id);
            $sc->save();
        }
        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_CANCEL);
            $this->payamount = $this->amount;
        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'ВК-000000';
    }

}
