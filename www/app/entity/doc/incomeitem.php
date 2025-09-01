<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  документ оприходование товаров
 *
 */
class IncomeItem extends Document
{
    public function Execute() {


        $conn = \ZDB\DB::getConnect();
        $amount = 0;
        foreach ($this->unpackDetails('detaildata') as $item) {
            $item->quantity = doubleval($item->quantity)   ;
            $item->price = doubleval($item->price)   ;
            $stockto = Stock::getStock($this->headerdata['store'], $item->item_id, $item->price, $item->snumber, $item->sdate, true,0,$this->headerdata['storeemp']??0);
            $sc = new Entry($this->document_id, $item->quantity * $item->price, $item->quantity);
            $sc->setStock($stockto->stock_id);
            $sc->save();
            $amount = $amount + $item->quantity * $item->price;



        }
 
     
        return true;
    }

    public function generateReport() {


        $i = 1;
        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {


            $detail[] = array("no"        => $i++,
                              "item_name" => $item->itemname,
                              "item_code" => $item->item_code,
                              "snumber"   => $item->snumber,
                              "msr"       => $item->msr,
                              "quantity"  => H::fqty($item->quantity),
                              "price"     => H::fa($item->price),
                              "amount"    => H::fa($item->quantity * $item->price)
            );
        }

        $header = array(
            "_detail"         => $detail,
            'date'            => H::fd($this->document_date),
            "total"           => H::fa($this->amount),
            "to"              => $this->headerdata["storename"],
         
            "storeemp"             => false,
            "notes"           => nl2br($this->notes),
            "document_number" => $this->document_number
        );
     
        if ($this->headerdata["storeemp"] > 0  ) {
            $header['storeemp'] = $this->headerdata["storeempname"];
        }

        $report = new \App\Report('doc/incomeitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОТ-000000';
    }

}
