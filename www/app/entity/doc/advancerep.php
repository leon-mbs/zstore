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
            $sc->tag=Entry::TAG_BAY;
            
            $sc->save();
            $amount = $amount + $item->quantity * $item->price;

  
        }
 
        
        $examount=doubleval($this->headerdata['examount']);  //возврат
        $spentamount=doubleval($this->headerdata['spentamount']); //потрачено
        
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
            $ua->optype = \App\Entity\EmpAcc::ADVANCE_ACC;
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
         if(\App\System::getOption("common",'useacc')!=1 ) return;
        
         $conn = \ZDB\DB::getConnect();
         $conn->Execute("delete from acc_entry where document_id=" . $this->document_id);
 
         $ia = \App\Entity\Item::getAccCode();
 
         $sql="select coalesce(sum(e.quantity * e.partion ),0) as am, item_type from entrylist_view e join items i on e.item_id=i.item_id  where document_id=".$this->document_id." group by item_type ";
         foreach($conn->Execute($sql) as $row) {
            
              \App\Entity\AccEntry::addEntry( $ia[$row['item_type']] ?? '28','371', $row['am'],$this->document_id)  ; 
            
         }
         
         foreach(\App\Entity\IOState::find("document_id=".$this->document_id) as $is){
             if($is->iotype==\App\Entity\IOState::TYPE_BASE_OUTCOME)  {
                 //пойдет в  себестоимость
             }
 
             if($is->iotype==\App\Entity\IOState::TYPE_COMMON_OUTCOME)  {
                 \App\Entity\AccEntry::addEntry( '91' ,'371',$is->amount,$this->document_id)  ; 
             }
             if($is->iotype==\App\Entity\IOState::TYPE_ADMIN_OUTCOME)  {
                  \App\Entity\AccEntry::addEntry( '92' ,'371',$is->amount,$this->document_id)  ; 
             }
             if($is->iotype==\App\Entity\IOState::TYPE_SALE_OUTCOME)  {
                   \App\Entity\AccEntry::addEntry( '93' ,'371',$is->amount,$this->document_id)  ; 
             }
             if($is->iotype==\App\Entity\IOState::TYPE_OTHER_OUTCOME)  {
               \App\Entity\AccEntry::addEntry( '94' ,'371',$is->amount,$this->document_id)  ; 
             }
          
         }

        $pa = doubleval($conn->GetOne("select sum(amount) from paylist where document_id=".$this->document_id)) ;
        $mf = \App\Entity\MoneyFund::load($this->headerdata['exmf']);
        if($mf != null  && $pa > 0) {
           \App\Entity\AccEntry::addEntry($mf->beznal==1 ? '31':'30' ,'371',$pa,$this->document_id)  ; 
        }
        
                         
    } 
 
}
