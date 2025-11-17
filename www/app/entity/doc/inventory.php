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
            $qty=1;
            //оприходуем
            if ($item->quantity < $item->qfact && $this->headerdata['autoincome'] == 1) {
                $qty = $item->qfact - $item->quantity;
                $where = "store_id=" . $this->headerdata['store'] . " and item_id=" . $item->item_id;
                if($this->headerdata['storeemp'] ) {
                   $where .= " and emp_id=" . $this->headerdata['storeemp']  ;
                }
                $price = 0;
                $stp = Stock::getFirst($where, "stock_id desc");  //последняя цена
                if ($stp->partion > 0) {
                    $price = $stp->partion;
                }
                $stock = Stock::getStock($this->headerdata['store'], $item->item_id, $price, $item->snumber, time(), true,0,$this->headerdata['storeemp'] );  //последняя цена


                $sc = new Entry($this->document_id, $qty * $stock->partion, $qty);
                $sc->setStock($stock->stock_id);
                $sc->tag=Entry::TAG_IN;
          
                $sc->save();

                //записываем  в доход
                $io = new \App\Entity\IOState();
                $io->document_id = $this->document_id;
                $io->amount = $qty * $stock->partion;
                $io->iotype = \App\Entity\IOState::TYPE_OVER;

                $io->save();


            }

            //списываем  со склада
            if ($item->quantity > $item->qfact && $this->headerdata['autooutcome'] == 1) {
                $qty = $item->quantity - $item->qfact;
      
                $q= $item->quantity;
                $item->quantity = $qty;
                $listst = Stock::pickup($this->headerdata['store'], $item,$this->headerdata['storeemp']);
                $item->quantity = $q;                
                foreach ($listst as $st) {
                    $sc = new Entry($this->document_id, 0 - $qty * $st->partion, 0 - $st->quantity);
                    $sc->setStock($st->stock_id);
                    $sc->tag=Entry::TAG_OUT;
          
                    $sc->save();

                    //записываем  в потери
                    $io = new \App\Entity\IOState();
                    $io->document_id = $this->document_id;
                    $io->amount = 0 - $qty * $st->partion;
                    $io->iotype = \App\Entity\IOState::TYPE_LOST;

                    $io->save();


                }
            }
        }
        $this->DoAcc();
 
        return true;
    }

    public function generateReport() {
        $conn = \ZDB\DB::getConnect();

        $user = \App\System::getUser();

        $i = 1;
        $detaillost = array();
        $detailover = array();
        $detail = array();

        $sumplus = 0;
        $summinus = 0;
        foreach ($this->unpackDetails('detaildata') as $item) {
            
            $sql="select coalesce(abs(sum(quantity*partion)),0)   from entrylist_view where  document_id={$this->document_id} and item_id= {$item->item_id}" ;
            
            $b= $conn->GetOne($sql);
            
            $name = $item->itemname;
            $code = $item->item_code;
            $q = H::fqty($item->quantity);
            if (round($item->qfact) == round($q)) {
                $detail[] = array("no"        => $i++,
                                  "item_name" => $name,
                                  "item_code" => $code,
                                  "qfact"     => $item->qfact,
                                  "snumber"   => $item->snumber,
                                  "quantity"  => $user->rolename != 'admins' ? '-' :$q
                );
            }
            if (round($item->qfact) < round($q)) {
               
                $summinus += $b;
                $detaillost[] = array("no"        => $i++,
                                      "item_name" => $name,
                                      "item_code" => $code,
                                      "qfact"     => $item->qfact,
                                      "snumber"   => $item->snumber,
                                      "quantity"  => $user->rolename != 'admins' ? '-' :$q
                );
            }
            if (round($item->qfact) > round($q)) {
                $sumplus += $b;

                $detailover[] = array("no"        => $i++,
                                      "item_name" => $name,
                                      "item_code" => $code,
                                      "qfact"     => $item->qfact,
                                      "snumber"   => $item->snumber,
                                      "quantity"  => $user->rolename != 'admins' ? '-' :$q
                );
            }
        }

        $header = array(
            "_detaillost"     => $detaillost,
            "_detailover"     => $detailover,
            "_detail"         => $detail,
            'date'            => H::fd($this->document_date),
            "notes"           => nl2br($this->notes),
            "reserved"           => $this->headerdata["reserved"]==1,
            "store"           => $this->headerdata["storename"],
            "storeemp"             => false,
            "summinus"           => $summinus > 0 ? H::fa($summinus) : false,
            "sumplus"           => $sumplus > 0 ? H::fa($sumplus) : false ,
            "document_number" => $this->document_number
        );
        if ($this->headerdata["storeemp"] > 0  ) {
            $header['storeemp'] = $this->headerdata["storeempname"];
        }
        
        $report = new \App\Report('doc/inventory.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ІН-000000';
    }
    public   function DoAcc() {
         if(\App\System::getOption("common",'useacc')!=1 ) return;
         parent::DoAcc()  ;
         $conn->Execute("delete from acc_entry where document_id=" . $this->document_id);
      
         $ia=\App\Entity\AccEntry::getItemsEntry($this->document_id,Entry::TAG_OUT) ;
         foreach($ia as $a=>$am){
             \App\Entity\AccEntry::addEntry('97',$a, $am,$this->document_id)  ; 
         } 
         
         $ia=\App\Entity\AccEntry::getItemsEntry($this->document_id,Entry::TAG_IN) ;
         foreach($ia as $a=>$am){
             \App\Entity\AccEntry::addEntry($a,'71', $am,$this->document_id)  ; 
         } 
         
           
    }
}
