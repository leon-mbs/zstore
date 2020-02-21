<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\Util;

/**
 * Класс-сущность  документ возвратная накладная
 *
 */
class ReturnIssue extends Document {

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
            "customer_name" => $this->headerdata["customer_name"],
            "document_number" => $this->document_number,
            "total" => H::fa($this->amount)
        );

        $report = new \App\Report('returnissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->unpackDetails('detaildata') as $item) {


            $sc = new Entry($this->document_id, $item->amount , $item->quantity );
            $sc->setStock($item->stock_id );

            $sc->setExtCode(0 - ($item->price - $st->partion)); //Для АВС 
            //  $sc->setCustomer($this->customer_id);
            $sc->save();
        }
        if ($this->headerdata['payment'] > 0) {
            \App\Entity\Pay::addPayment($this->document_id, 0 - $this->amount, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME);
            $this->payamount = $this->amount;
        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'ВК-000000';
    }

}
