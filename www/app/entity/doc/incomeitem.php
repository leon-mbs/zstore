<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Helper as H;

/**
 * Класс-сущность  документ оприходование товаров
 *
 */
class IncomeItem extends Document {

    public function Execute() {



        $conn = \ZDB\DB::getConnect();

        foreach ($this->detaildata as $item) {

            $stockto = Stock::getStock($this->headerdata['store'], $item['item_id'], $item['price'], $item['snumber'], 0, true);
            $sc = new Entry($this->document_id, $item['quantity'] * $item['price'], $item['price']);
            $sc->setStock($stockto->stock_id);
            $sc->save();
        }


        return true;
    }

    public function generateReport() {





        $i = 1;
        $detail = array();
        foreach ($this->detaildata as $value) {
            $name = $value['itemname'];


            $detail[] = array("no" => $i++,
                "item_name" => $name,
                "snumber" => $value['snumber'],
                "msr" => $value['msr'],
                "quantity" => H::fqty($value['quantity']),
                "price" => H::fa($value['price']),
                "amount" => H::fa($value['quantity'] * $value['price'])
            );
        }

        $header = array(
            "_detail" => $detail,
            'date' => date('d.m.Y', $this->document_date),
            "total" => H::fa($this->amount),
            "to" => $this->headerdata["storename"],
            "notes" => $this->notes,
            "document_number" => $this->document_number
        );
        $report = new \App\Report('incomeitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПТ-000000';
    }

    
}
