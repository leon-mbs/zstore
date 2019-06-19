<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\Util;

/**
 * Класс-сущность  документ возврат  поставщику
 *
 */
class RetCustIssue extends Document {

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
                    "tovar_code" => $value['item_code'],
                    "quantity" => H::fqty($value['quantity']),
                    "msr" => $value['msr'],
                    "price" => $value['price'],
                    "amount" => round($value['quantity'] * $value['price'])
                );
            }
        }


        $customer = \App\Entity\Customer::load($this->customer_id);

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "firmname" => $firm['firmname'],
            "customername" => $this->customer_name . ', тел. ' . $customer->phone,
            "document_number" => $this->document_number,
            "total" => $this->amount
        );


        $report = new \App\Report('retcustissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->detaildata as $row) {

            $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
            $sc->setStock($row['stock_id']);
            // $sc->setExtCode(0 - $row['amount'])); //Для АВС 

            $sc->setCustomer($this->customer_id);
            $sc->save();
        }


        return true;
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = 'Гарантийный талон';
        $list['ReturnIssue'] = 'Возвратная накладная';

        return $list;
    }

}
