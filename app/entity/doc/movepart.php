<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  документ перемещение партий
 *
 */
class MovePart extends Document
{

    public function Execute() {


        $conn = \ZDB\DB::getConnect();
        $qty = $this->headerdata['qty'];
        $st = Stock::load($this->headerdata['fromstock']);
        $sc = new Entry($this->document_id, 0 - $qty * $st->partion, 0 - $qty);
        $sc->setStock($st->stock_id);
        $sc->save();

        $st = Stock::load($this->headerdata['tostock']);
        $sc = new Entry($this->document_id, $qty * $st->partion, $qty);
        $sc->setStock($st->stock_id);
        $sc->save();


        return true;
    }

    public function generateReport() {


        $detail = array();


        $header = array(
            "_detail"         => $detail,
            'date'            => H::fd($this->document_date),
            "from"            => $this->headerdata["fromstockname"],
            "to"              => $this->headerdata["tostockname"],
            "qty"             => H::fqty($this->headerdata["qty"]),
            "notes"           => nl2br($this->notes),
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/movepart.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПП-000000';
    }

}
