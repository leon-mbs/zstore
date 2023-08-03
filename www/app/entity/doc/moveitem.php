<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  документ перемещение товаров
 *
 */
class MoveItem extends Document
{
    public function Execute() {


        $conn = \ZDB\DB::getConnect();

        foreach ($this->unpackDetails('detaildata') as $item) {

            //списываем  со склада

            $listst = Stock::pickup($this->headerdata['store'], $item);
            if (count($listst) == 0) {
                \App\System::setErrorMsg("Недостатньо товару " . $item->itemname);

                return false;
            }
            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->save();

                $sti = Stock::getStock($this->headerdata['tostore'], $st->item_id, $st->partion, $st->snumber, $st->sdate, true);
                $sc = new Entry($this->document_id, $st->quantity * $sti->partion, $st->quantity);
                $sc->setStock($sti->stock_id);
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

            $detail[] = array("no"        => $i++,
                              "item_name" => $name,
                              "snumber"   => $item->snumber,
                              "msr"       => $item->msr,
                              "quantity"  => H::fqty($item->quantity));
        }

        $header = array(
            "_detail"         => $detail,
            'date'            => H::fd($this->document_date),
            "from"            => $this->headerdata["storename"],
            "to"              => $this->headerdata["tostorename"],
            "notes"           => nl2br($this->notes),
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/moveitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПТ-000000';
    }

}
