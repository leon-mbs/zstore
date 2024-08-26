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
                \App\System::setErrorMsg("Недостатньо товару"  . $item->itemname);
                return false;
            }
            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->save();

                if ($this->headerdata['mtype'] > 0) {
                    $io = new \App\Entity\IOState();
                    $io->document_id = $this->document_id;
                    $io->amount = 0 - $st->quantity * $st->partion;
                    $io->iotype = $this->headerdata['mtype'];

                    $io->save();
                }


            }
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
                              "quantity"  => H::fqty($item->quantity));
        }

        $header = array(
            "_detail"         => $detail,
            'date'            => H::fd($this->document_date),
            "from"            => $this->headerdata["storename"],
            "notes"           => nl2br($this->notes),
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/outcomeitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'CТ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['IncomeItem'] = self::getDesc('IncomeItem');
        return $list;
    }

}
