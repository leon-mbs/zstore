<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;
use App\Util;

/**
 * Класс-сущность  документ возвратная накладная
 *
 */
class ReturnIssue extends Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->detaildata as $value) {

            if (isset($detail[$value['item_id']])) {
                $detail[$value['item_id']]['quantity'] += $value['quantity'];
            } else {
                $detail[] = array("no" => $i++,
                    "tovar_name" => $value['itemname'],
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
            "total" => $this->headerdata["total"],
            "summa" => Util::ucfirst(Util::money2str($this->headerdata["total"]))
        );

        $report = new \App\Report('returnissue.tpl');

        $html = $report->generate($header );

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();

        foreach ($this->detaildata as $row) {
             

            $sc = new Entry($this->document_id, $row['amount'], $row['quantity']);
            $sc->setStock($row['stock_id']);


            $sc->setCustomer($this->customer_id);
            $sc->save();
        }

        $conn->CompleteTrans();
        return true;
    }

    public function getRelationBased() {
        $list = array();

        return $list;
    }

}
