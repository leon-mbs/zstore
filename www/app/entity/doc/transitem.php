<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;

/**
 * Класс-сущность  документ перекомплектация (расфасовка) ТМЦ
 *
 */
class TransItem extends Document
{
    public function Execute() {

 
        //списываем  со склада
        foreach ($this->unpackDetails('detaildata') as $st) {
     
            $sc = new Entry($this->document_id, 0 - ($st->qty * $st->partion), 0 - $st->qty);
            $sc->setStock($st->stock_id);

            $sc->save();
        }
        
         //оприходуем на  склад
        foreach ($this->unpackDetails('detaildata2') as $item) {
   
            $stockto = Stock::getStock($this->headerdata['tostore'], $item->item_id, $item->price, "", "", true);
            $sc = new Entry($this->document_id, $item->qty * $item->price, $item->qty);
            $sc->setStock($stockto->stock_id);
            $sc->save();
        }
  
  
        $diff = doubleval($this->_doc->headerdata['fromamount'] ) - doubleval($this->_doc->headerdata['toamount'] ); 
       
        if($diff < 0)  {
            \App\Entity\IOState::addIOState($this->document_id, $diff, \App\Entity\IOState::TYPE_OTHER_INCOME);
        }
        if($diff > 0)  {
            \App\Entity\IOState::addIOState($this->document_id, $diff, \App\Entity\IOState::TYPE_OTHER_OUTCOME);
        }
       
        return true;
    }

    public function generateReport() {
        $from=[];
        $to=[];

        foreach ($this->unpackDetails('detaildata') as $item) {
            $from[] = array('fromname'=>$item->itemname,
                            'fromcode'=>$item->item_code,            
                            'fromqty'=> H::fqty($item->qty),            
                            'fromprice'=> H::fa($item->partion)             
            );
        }
    
        foreach ($this->unpackDetails('detaildata2') as $item) {
            $to[] = array('toname'=>$item->itemname,
                            'tocode'=>$item->item_code,            
                            'toqty'=> H::fqty($item->qty),            
                            'toprice'=> H::fa($item->price)             
            );
        }
    

        $header = array(
            'date'            => H::fd($this->document_date),
            "fromlist"        => $from,
            "tolist"          => $to,
            "from"            => Store::load($this->headerdata["store"])->storename,
            "to"              => Store::load($this->headerdata["tostore"])->storename,
            "notes"           => nl2br($this->notes),
            "document_number" => $this->document_number,
            "amount"          => H::fa($this->amount)
        );

        $report = new \App\Report('doc/transitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПФ-000000';
    }

}
