<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Helper as H;

/**
 * Класс-сущность  документ перемещения товаров
 *
 */
class MoveItem extends Document {

    public function Execute() {



        $conn = \ZDB\DB::getConnect();

        foreach ($this->detaildata as $item) {

            if($item['st1']>0 && $item['st1']>0 ){   //перемещение партий
                $st1 = Stock::load($item['st1']);
                $st2 = Stock::load($item['st2']);
                
                $sc = new Entry($this->document_id, 0 - $item['quantity'] * $st1->partion, 0 - $item['quantity']);
                $sc->setStock($st1->stock_id);
                $sc->save();

                $sc = new Entry($this->document_id, $item['quantity'] * $st2->partion, $item['quantity']);
                $sc->setStock($st2->stock_id);
                $sc->save();           
             
                 return true;
            }
            
            //списываем  со склада

            $listst = Stock::pickup($this->headerdata['storefrom'], $item['item_id'], $item['quantity'], $item['snumber']);
            if (count($listst) == 0) {
                \App\System::setErrorMsg('Недостаточно товара ' . $item['itemname']);
                return false;
            }
            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->save();

                $stockto = Stock::getStock($this->headerdata['storeto'], $item['item_id'], $st->partion, $item['snumber'], 0, true);
                $sc = new Entry($this->document_id, $st->quantity * $st->partion, $st->quantity);
                $sc->setStock($stockto->stock_id);
                $sc->save();
            }
        }



        return true;
    }

    public function generateReport() {





        $i = 1;
        $detail = array();
        foreach ($this->detaildata as $value) {
            $name = $value['itemname'];


            $detail[] = array("no" => $i++,
                "item_name" => $name,
                "snumber" => $value['snumber'],
                "msr" => $value['msr'],
                "quantity" => H::fqty($value['quantity']));
        }

        $header = array(
            "_detail" => $detail,
            'date' => date('d.m.Y', $this->document_date),
            "from" => $this->headerdata["storefromname"],
            "to" => $this->headerdata["storetoname"],
            "notes" => $this->notes,
            "document_number" => $this->document_number
        );
        $report = new \App\Report('moveitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПТ-000000';
    }

}
