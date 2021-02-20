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

        foreach ($this->unpackDetails('detaildata') as $item) {

            $stockto = Stock::getStock($this->headerdata['store'], $item->item_id, $item->price, $item->snumber, $item->sdate, true);
            $sc = new Entry($this->document_id, $item->quantity * $item->price, $item->quantity);
            $sc->setStock($stockto->stock_id);
            $sc->save();
        }

        if ($this->headerdata['emp'] > 0 && $this->headerdata['exmf'] > 0 && $this->headerdata['examount'] > 0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->headerdata['examount'], $this->headerdata['exmf'], \App\Entity\Pay::PAY_BASE_INCOME, $this->notes);
            if ($payed > 0) {
                $this->payed = $payed;
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
            "emp"             => false,
            "notes"           => $this->notes,
            "document_number" => $this->document_number
        );
        if ($this->headerdata["emp"] > 0 && $this->headerdata['examount']) {
            $header['emp'] = $this->headerdata["empname"];
            $header['examount'] = H::fa($this->headerdata["examount"]);

        }

        $report = new \App\Report('doc/incomeitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПТ-000000';
    }

}
