<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ  списание в  производство
 *
 */
class ProdIssue extends Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {
                $name = $item->itemname;
                if (strlen($item->snumber) > 0) {
                    $name .= ' (' . $item->snumber . ',' . H::fd($item->sdate) . ')';
                }


                $detail[] = array("no"         => $i++,
                                  "tovar_name" => $name,
                                  "tovar_code" => $item->item_code,
                                  "msr"        => $item->msr,
                                  "quantity"   => H::fqty($item->quantity)
                );
            }
        }


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "pareaname"       => $this->headerdata["pareaname"],
                        "storename"       => $this->headerdata["storename"],
                        "document_number" => $this->document_number,
                        "notes"           => nl2br($this->notes)
        );

        $report = new \App\Report('doc/prodissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();

        foreach ($this->unpackDetails('detaildata') as $item) {
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->setOutPrice($st->partion);
                $sc->tag=Entry::TAG_TOPROD;

                $sc->save();
            }
        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'СВ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['ProdIssue'] = self::getDesc('ProdIssue');

        return $list;
    }

}
