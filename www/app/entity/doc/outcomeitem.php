<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  документ списание товаров
 *
 */
class OutcomeItem extends Document
{

    public function Execute() {


        $conn = \ZDB\DB::getConnect();

        foreach ($this->unpackDetails('detaildata') as $item) {

            //списываем  со склада

            $listst = Stock::pickup($this->headerdata['store'], $item);
            if (count($listst) == 0) {
                \App\System::setErrorMsg('Недостаточно товара ' . $item->itemname);
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
        foreach ($this->unpackDetails('detaildata') as $item) {
            $name = $item->itemname;


            $detail[] = array("no" => $i++,
                "item_name" => $name,
                "snumber" => $item->snumber,
                "msr" => $item->msr,
                "quantity" => H::fqty($item->quantity));
        }

        $header = array(
            "_detail" => $detail,
            'date' => date('d.m.Y', $this->document_date),
            "from" => $this->headerdata["storename"],
            "notes" => $this->notes,
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/outcomeitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОТ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['IncomeItem'] = 'Оприходование товаров';
        return $list;
    }

}
