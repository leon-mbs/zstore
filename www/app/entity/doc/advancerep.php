<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  документ оприходование товаров
 *
 */
class AdvanceRep extends Document
{
    public function Execute() {


        $conn = \ZDB\DB::getConnect();
        $amount = 0;
        foreach ($this->unpackDetails('detaildata') as $item) {
            $item->quantity = doubleval($item->quantity)   ;
            $item->price = doubleval($item->price)   ;
            $stockto = Stock::getStock($this->headerdata['store'], $item->item_id, $item->price, $item->snumber, $item->sdate, true,0,$this->headerdata['storeemp']??0);
            $sc = new Entry($this->document_id, $item->quantity * $item->price, $item->quantity);
            $sc->setStock($stockto->stock_id);
            $sc->save();
            $amount = $amount + $item->quantity * $item->price;

  
        }
 
        
        $examount=doubleval($this->headerdata['examount']);
        $spentamount=doubleval($this->headerdata['spentamount']);
        
        if ($examount > 0) {
         
            $this->payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $examount, $this->headerdata['exmf'], $this->notes);
         
        //    \App\Entity\IOState::addIOState($this->document_id, $this->payed, \App\Entity\IOState::TYPE_COMMON_OUTCOME);  //возврат

        }

                                                                                                  
        if ($spentamount > 0) {
         
          //  \App\Entity\IOState::addIOState($this->document_id, $spentamount, \App\Entity\IOState::TYPE_ADVANCEREP);
            \App\Entity\IOState::addIOState($this->document_id, 0-$spentamount,  $this->headerdata['spenttype']);

        }


  
        if ($amount > 0) {
         
             \App\Entity\IOState::addIOState($this->document_id, 0-$amount, \App\Entity\IOState::TYPE_BASE_OUTCOME);
        //     \App\Entity\IOState::addIOState($this->document_id, $amount,  \App\Entity\IOState::TYPE_COMMON_OUTCOME);

        }


 
            //авансовый    отчет
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::OUTCOME_TO_MF;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $this->headerdata["emp"];
            $ua->amount = $amount;
            if ($examount > 0) {
                $ua->amount += $examount;
            }
            if ($spentamount > 0) {
                $ua->amount += $spentamount;
            }
            if ($ua->amount > 0) {
                $ua->save();
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
                              "quantity"  => H::fqty($item->quantity),
                              "price"     => H::fa($item->price),
                              "amount"    => H::fa($item->quantity * $item->price)
            );
        }

        $header = array(
            "_detail"         => $detail,
            "isdetail"        => count($detail)>0,
            'date'            => H::fd($this->document_date),
            "total"           => H::fa($this->headerdata["total"]),
            "to"              => $this->headerdata["storename"],
            "emp"             => $this->headerdata["empname"],
            "storeemp"             => false,
            "notes"           => nl2br($this->notes),
            "document_number" => $this->document_number
        );
        $header['spentamount'] = false;
        if ($this->headerdata["spentamount"] > 0  ) {
            $header['spentamount'] = H::fa($this->headerdata["spentamount"]);
            $header['spenttypename'] =  $this->headerdata["spenttypename"];
        }
        $header['examount'] = false;
        if ($this->headerdata["examount"] > 0  ) {
            $header['examount'] = H::fa($this->headerdata["examount"]);
        }
        if ($this->headerdata["storeemp"] > 0  ) {
            $header['storeemp'] = $this->headerdata["storeempname"];
        }

        $report = new \App\Report('doc/advancerep.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'АЗ-000000';
    }
    public   function DoAcc() {
        parent:: DoAcc() ;
      
    } 
 
}
