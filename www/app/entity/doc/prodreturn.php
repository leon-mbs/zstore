<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  документ возврат с производства
 *
 */
class ProdReturn extends Document
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

        $report = new \App\Report('doc/prodreturn.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();

        foreach ($this->unpackDetails('detaildata') as $item) {
            
    
            $where = " item_id =    ". $item->item_id;
            if($this->headerdata['store']>0) {
                $where .= " and store_id = ".$this->headerdata['store'];
            }


            if (strlen($item->snumber) > 0) {
                $where .= "  and  snumber =  " . $conn->qstr($item->snumber);
            }


            $st = Stock::getFirst($where , 'qty desc,stock_id desc');
            

            $sc = new Entry($this->document_id, $item->quantity * $st->partion, $item->quantity);
            $sc->setStock($st->stock_id);
            $sc->setOutPrice($st->partion);
            $sc->tag=Entry::TAG_FROMPROD;
            $sc->save();
        
        }
          $this->DoAcc();  
  
        return true;
    }

    protected function getNumberTemplate() {
        return 'ПВ-000000';
    }

    public function getRelationBased() {
        $list = array();
  
        return $list;
    }
    public   function DoAcc() {
             if(\App\System::getOption("common",'useacc')!=1 ) return;
             parent::DoAcc()  ;
      
             $ia=\App\Entity\AccEntry::getItemsEntry($this->document_id,Entry::TAG_FROMPROD) ;
             foreach($ia as $a=>$am){
                 \App\Entity\AccEntry::addEntry( $a,'23', $am,$this->document_id)  ; 
             }            
  
    }
}
