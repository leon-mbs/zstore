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
                $name .= ' (' . $item->snumber . ',' . H::fd($item->sdate) . ')';
            }


            $detail[] = array("no"       => $i++,
                              "itemname" => $name,
                              "itemcode" => $item->item_code,
                              "quantity" => H::fqty($item->quantity),
                              "price"    => H::fa($item->price),
                              "msr"      => $item->msr,
                              "amount"   => H::fa($item->quantity * $item->price)
            );
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "document_number" => $this->document_number,
                        "pareaname"       => $this->headerdata["pareaname"],
                        "notes"           => $this->notes,
                        "total"           => H::fa($this->amount)
        );


        $report = new \App\Report('doc/prodreceipt.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $types = array();
        $common = \App\System::getOptions("common");
        $parts = array();
        
      
        foreach ($this->unpackDetails('detaildata') as $item) {
         
            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $item->price, $item->snumber, $item->sdate, true);

            $sc = new Entry($this->document_id, $item->quantity * $item->price, $item->quantity);
            $sc->setStock($stock->stock_id);
           // $sc->setExtCode($item->price); //Для АВС 
           

            $sc->save();

    
            $set =  \App\Entity\ItemSet::find("pitem_id=" . $item->item_id);
            foreach($set  as $part) {
               if(  isset($parts[$part->item_id])==false) $parts[$part->item_id] =0;
               $parts[$part->item_id] += $part->qty;
            }
            
            
        }
        //  списываем  в  производство
        if($this->headerdata['storefrom'] >0) {
            foreach($parts  as $id=>$qty) {
                $item = \App\Entity\Item::load($id);
                $item->quantity = $qty;
                $listst = \App\Entity\Stock::pickup($this->headerdata['storefrom'], $item);

                foreach ($listst as $st) {
                    $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                    $sc->setStock($st->stock_id);

                    $sc->save();
                }
             
            }
        }
        return true;
    }

    protected function getNumberTemplate() {
        return 'ОП-000000';
    }


    public function getRelationBased() {
        $list = array();
        $list['ProdReceipt'] = self::getDesc('ProdReceipt');
        $list['ProdIssue'] = self::getDesc('ProdIssue'); //для  формирования  комплектубщих  по  готовой продукции


        return $list;
    }
}
