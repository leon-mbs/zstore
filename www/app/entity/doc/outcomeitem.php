<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Helper as H;

/**
 * Класс-сущность  документ с аписание товаров
 *
 */
class OutcomeItem extends Document {

    public function Execute() {



        $conn = \ZDB\DB::getConnect();

        foreach ($this->detaildata as $item) {

            //списываем  со склада

            $listst = Stock::pickup($this->headerdata['storefrom'], $item['item_id'], $item['quantity'], $item['snumber']);
            if (count($listst) == 0) {
                \App\System::setErrorMsg('Недостаточно товара ' . $item['itemname']);
                return false;
            }
            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->save();
            }
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
                "quantity" => H::fqty($value['quantity']));
        }

        $header = array(
            "_detail" => $detail,
            'date' => date('d.m.Y', $this->document_date),
            "from" => $this->headerdata["storefromname"],
            "notes" => $this->notes,
            "document_number" => $this->document_number
        );
        $report = new \App\Report('outcomeitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОТ-000000';
    }

}
