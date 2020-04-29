<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ   оприходование  с  производства
 *
 */
class ProdReceipt extends Document
{

    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {

            $name = $item->itemname;
            if (strlen($item->snumber) > 0) {
                $name .= ' (' . $item->snumber . ',' . date('d.m.Y', $item->sdate) . ')';
            }


            $detail[] = array("no" => $i++,
                "itemname" => $name,
                "itemcode" => $item->item_code,
                "quantity" => H::fqty($item->quantity),
                "price" => H::fa($item->price),
                "msr" => $item->msr,
                "amount" => H::fa($item->quantity * $item->price)
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "document_number" => $this->document_number,
            "pareaname" => $this->headerdata["pareaname"],
            "notes" => $this->notes,
            "total" => H::fa($this->amount)
        );


        $report = new \App\Report('doc/prodreceipt.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $types = array();
        $common = \App\System::getOptions("common");

        //аналитика
        foreach ($this->unpackDetails('detaildata') as $item) {
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item );

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id,   $st->quantity * $item->price,   $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->save();
            }

        }


        return true;
    }

    protected function getNumberTemplate() {
        return 'ОП-000000';
    }


    public function getRelationBased() {
        $list = array();
        $list['ProdReceipt'] = 'Оприходование  с  производства';

        return $list;
    }
}
