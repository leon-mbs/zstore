<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;

/**
 * Класс-сущность  документ перекомплектация (расфасовка) ТМЦ
 *
 */
class TransItem extends Document
{
    public function Execute() {


        //списываем  со склада
        $fi = Stock::load($this->headerdata['fromitem']);

        $sc = new Entry($this->document_id, 0 - ($this->headerdata["fromquantity"] * $fi->partion), 0 - $this->headerdata["fromquantity"]);
        $sc->setStock($fi->stock_id);

        $sc->save();
        $sum = doubleval($this->headerdata["fromquantity"] * $fi->partion) ;
        if ($this->headerdata['toitem'] > 0) {
            $ti = Item::load($this->headerdata['toitem']);
            $price = H::fa($sum / $this->headerdata["toquantity"]);
            $stockto = Stock::getStock($this->headerdata['tostore'], $ti->item_id, $price, "", "", true);
            $sc = new Entry($this->document_id, $this->headerdata["toquantity"] * $price, $this->headerdata["toquantity"]);
            $sc->setStock($stockto->stock_id);
            $sc->save();
        }
  

        return true;
    }

    public function generateReport() {


        $si = Stock::load($this->headerdata['fromitem']);
        $fi = Item::load($si->item_id);
        $ti = Item::load($this->headerdata['toitem']);

        if (($this->headerdata['tostock'] ??0)> 0) {    // перемещение партии
            $ts = Stock::load($this->headerdata['tostock']);
            $ti = Item::load($ts->item_id);
        }

        $header = array(
            'date'            => H::fd($this->document_date),
            "from"            => Store::load($this->headerdata["store"])->storename,
            "to"            => Store::load($this->headerdata["tostore"])->storename,
            "fromitemname"    => $fi->itemname . ', ' . $this->headerdata["fromquantity"] . $fi->msr,
            "notes"           => nl2br($this->notes),
            "toitemname"      => $ti->itemname . ', ' . $this->headerdata["toquantity"] . $ti->msr,
            "document_number" => $this->document_number,
            "amount"          => H::fa($this->amount)
        );

        $report = new \App\Report('doc/transitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПФ-000000';
    }

}
