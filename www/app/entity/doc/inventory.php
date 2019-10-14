<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Helper as H;

/**
 * Класс-сущность  Инвентаризация    склада
 *
 */
class Inventory extends Document {

    public function Execute() {



        $conn = \ZDB\DB::getConnect();
        
        
        foreach ($this->detaildata as $item) {
            if($item['quantity'] == $item['qfact'])
            {
                continue;
            }
            //списываем  со склада
            if($item['quantity'] > $item['qfact']){
                $qty=  $item['quantity'] - $item['qfact'];
                $listst = Stock::pickup($this->headerdata['store'],$item['item_id'],$qty,$item['snumber'])    ;
                foreach($listst as $st){
                      $sc = new Entry($this->document_id, 0-$st->quantity * $stock->partion, 0-$st->quantity );
                      $sc->setStock($st->stock_id);
                      $sc->save();                
                }
            }
            //оприходуем
            if($item['quantity'] < $item['qfact']){
                 $qty=  $item['qfact'] - $item['quantity'];
                 $where = "store_id=".$this->headerdata['store']." and item_id=".$item['item_id']; 
                
                 $stock = Stock::getFirst($where,"store_id desc") ;
              
                 $sc = new Entry($this->document_id, $qty * $stock->partion, $qty);
                 $sc->setStock($stock->stock_id);
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
                "qfact" => $value['qfact'],
                "snumber" => $value['snumber'],
                "quantity" => H::fqty($value['quantity']));
        }

        $header = array(
            "_detail" => $detail,
            'date' => date('d.m.Y', $this->document_date),
            "store" => $this->headerdata["storename"],
            
            "document_number" => $this->document_number
        );
        $report = new \App\Report('inventory.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate(){
         return  'ИН-000000';
    }      

}
