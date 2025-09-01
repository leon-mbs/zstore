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
                                  "cell"       => $item->cell,
                                  "quantity"   => H::fqty($item->quantity)
                );
            }
        }


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "pareaname"       => $this->headerdata["pareaname"],
                        "storename"       => $this->headerdata["storename"],
                        "document_number" => $this->document_number,
                        "emp"             => false,
                        "notes"           => nl2br($this->notes)
        );
        if ($this->headerdata["emp"] > 0  ) {
            $header['emp'] = $this->headerdata["empname"];
        }

        $report = new \App\Report('doc/prodissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
        $lost = 0;
      

        foreach ($this->unpackDetails('detaildata') as $item) {
            $kl=0;
            if ($item->lost > 0) {
                $kl = 1 / (1 - $item->lost / 100);
                $item->quantity = $item->quantity * $kl;
                                  
            }
      
      
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->setOutPrice($st->partion);
                $sc->tag=Entry::TAG_TOPROD;

                $sc->save();
                if ($kl > 0) {
                     $lost += abs($st->quantity * $st->partion  ) * ($item->lost / 100);
                }                   
            }
        }
       if ($lost > 0) {
            $io = new \App\Entity\IOState();
            $io->document_id = $this->document_id;
            $io->amount =  0 - abs($lost);
            $io->iotype = \App\Entity\IOState::TYPE_TRASH;

            $io->save();
       }
        return true;
    }

    protected function getNumberTemplate() {
        return 'СВ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['ProdIssue'] = self::getDesc('ProdIssue');
        $list['ProdReturn'] = self::getDesc('ProdReturn');

        return $list;
    }

}
