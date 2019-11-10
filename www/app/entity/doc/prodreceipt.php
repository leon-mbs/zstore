<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;

/**
 * Класс-сущность  документ   оприходование  с  производства
 *
 */
class ProdReceipt extends Document {

    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {

            $name = $value['itemname'];
            if (strlen($value['snumber']) > 0) {
                $name .= ' (' . $value['snumber'] . ',' . date('d.m.Y', $value['sdate']) . ')';
            }


            $detail[] = array("no" => $i++,
                "itemname" => $name,
                "itemcode" => $value['item_code'],
                "quantity" => H::fqty($value['quantity']),
                "price" => H::fa($value['price']),
                "msr" => $value['msr'],
                "amount" => H::fa($value['quantity'] * $value['price'])
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "document_number" => $this->document_number,
            "pareaname" => $this->headerdata["pareaname"],
            "notes" => $this->notes,
            "total" => H::fa($this->amount )
        );


        $report = new \App\Report('prodreceipt.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $types = array();
        $common = \App\System::getOptions("common");

        //аналитика
        foreach ($this->detaildata as $row) {
            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $row['item_id'], $row['price'], $row['snumber'], $row['sdate'], true);

            $sc = new Entry($this->document_id, $row['amount'], $row['quantity']);
            $sc->setStock($stock->stock_id);

            $sc->save();
        }



        return true;
    }

    public function getRelationBased() {
        $list = array();

        return $list;
    }

    protected function getNumberTemplate() {
        return 'ОП-000000';
    }

}
