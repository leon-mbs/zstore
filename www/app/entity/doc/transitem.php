<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Entity\Item;
use \App\Helper as H;

/**
 * Класс-сущность  локумент перекомплектация товаров
 *
 */
class TransItem extends Document {

    public function Execute() {

        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();




        //списываем  со склада
        $fi = Stock::load($this->headerdata['fromitem']);

        $sc = new Entry($this->document_id, 0 - ($this->headerdata["fromquantity"] * $fi->partion), 0 - $this->headerdata["fromquantity"]);
        $sc->setStock($fi->stock_id);

        $sc->save();

        $ti = Item::load($this->headerdata['toitem']);
        $price = round(($this->amount ) / $this->headerdata["toquantity"]);
        $stockto = Stock::getStock($this->headerdata['storefrom'], $ti->item_id, $price,"","", true);
        $sc = new Entry($this->document_id, $this->headerdata["toquantity"] * $price, $this->headerdata["toquantity"]);
        $sc->setStock($stockto->stock_id);

        $sc->save();


        $conn->CompleteTrans();
        return true;
    }

    public function generateReport() {


        $fi = Stock::load($this->headerdata['fromitem']);
        $fi = Item::load($fi->item_id);
        $ti = Item::load($this->headerdata['toitem']);


        $header = array(
            'date' => date('d.m.Y', $this->document_date),
            "from" => Store::load($this->headerdata["storefrom"])->storename,
            "fromitemname" => $fi->itemname . ', ' . $this->headerdata["fromquantity"] . $fi->msr,
            "toitemname" => $fi->itemname . ', ' . $this->headerdata["toquantity"] . $fi->msr,
            "document_number" => $this->document_number,
            "amount" => $this->amount
        );
        $report = new \App\Report('transitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
