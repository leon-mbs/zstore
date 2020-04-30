<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  Инвентаризация    склада
 *
 */
class Inventory extends Document
{

    public function Execute() {
 
        $conn = \ZDB\DB::getConnect();
  
        foreach ($this->unpackDetails('detaildata') as $item) {
            if ($item->quantity == $item->qfact) {
                continue;
            }
            
            //оприходуем
            if ($item->quantity < $item->qfact) {
                $qty = $item->qfact - $item->quantity;
                $where = "store_id=" . $this->headerdata['store'] . " and item_id=" . $item->item_id;
                $price=0;
                $stp = Stock::getFirst($where, "stock_id desc");  //последняя цена
                if($stp->partion>0)  $price = $stp->partion;
                $stock = Stock::getStock($this->headerdata['store'],$item->item_id,$price,$item->snumber,time(),true);  //последняя цена

                
                $sc = new Entry($this->document_id, $qty * $stock->partion, $qty);
                $sc->setStock($stock->stock_id);
                $sc->save();
            }            
            
            //списываем  со склада
            if ($item->quantity > $item->qfact) {
                $item->quantity = $item->quantity - $item->qfact;
                $listst = Stock::pickup($this->headerdata['store'], $item);
                foreach ($listst as $st) {
                    $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                    $sc->setStock($st->stock_id);
                    $sc->save();
                }
            }  
          
        }
  
        return true;
    }

    public function generateReport() {

        $user = \App\System::getUser();

        $i = 1;
        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {
            $name = $item->itemname;
            $q = H::fqty($item->quantity);
            if ($user->userlogin != 'admin') {
                $q = '-';
            }
            $detail[] = array("no" => $i++,
                "item_name" => $name,
                "qfact" => $item->qfact,
                "snumber" => $item->snumber,
                "quantity" => $q
            );
        }

        $header = array(
            "_detail" => $detail,
            'date' => date('d.m.Y', $this->document_date),
            "store" => $this->headerdata["storename"],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/inventory.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ИН-000000';
    }

}
