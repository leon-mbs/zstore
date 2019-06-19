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

        foreach ($this->detaildata as $value) {

            if (isset($detail[$value['item_id']])) {
                $detail[$value['item_id']]['quantity'] += $value['quantity'];
            } else {
                $name = $value['itemname'];
                if (strlen($value['snumber']) > 0) {
                    $name .= ' (' . $value['snumber'] . ',' . date('d.m.Y', $value['sdate']) . ')';
                }

                $detail[] = array("no" => $i++,
                    "tovar_name" => $name,
                    "quantity" => H::fqty($value['quantity']),
                    "price" => $value['price'],
                    "msr" => $value['msr'],
                    "amount" => round($value['quantity'] * $value['price'])
                );
            }
        }

        $firm = \App\System::getOptions("common");


        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "firmname" => $firm['firmname'],
            "customername" => $this->customer_name,
            "document_number" => $this->document_number,
            "total" => $this->amount,
            "summa" => Util::ucfirst(Util::money2str($this->amount))
        );

        $report = new \App\Report('returnissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->detaildata as $row) {


            $sc = new Entry($this->document_id, $row['amount'], $row['quantity']);
            $sc->setStock($row['stock_id']);


            $sc->setCustomer($this->customer_id);
            $sc->save();
        }
        if ($this->headerdata['payment'] > 0) {
            \App\Entity\Pay::addPayment($this->document_id, 0 - $this->amount, $this->headerdata['payment'], $this->headerdata['paynotes']);
            $this->payamount = $this->amount;
        }

        return true;
    }

    public function getRelationBased() {
        $list = array();

        return $list;
    }

}
