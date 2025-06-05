<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  документ перемещение  между этапами
 *
 */
class ProdMove extends Document
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
                        "procname"       => $this->headerdata["ppname"],
                        "fromname"       => $this->headerdata["psfromname"],
                        "toname"       => $this->headerdata["pstoname"],
                        "emp"             => false,
                       "document_number" => $this->document_number,
                        "notes"           => nl2br($this->notes)
        );
        if ($this->headerdata["emp"] > 0  ) {
            $header['emp'] = $this->headerdata["empname"];
        }
 
        $report = new \App\Report('doc/prodmove.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
        //зарплата
        $cost=0;
        
        foreach ($this->unpackDetails('detaildata') as $item) {
            if($item->zarp > 0) {
                $cost += doubleval($item->zarp * $item->quantity) ;
            }
        }   
        
  
        $st = \App\Entity\ProdStage::load($this->headerdata['psfrom'])  ;
        foreach($st->emplist as $emp){
           $this->headerdata["emp"] = 0;
           
           if($cost > 0){
                $ua = new \App\Entity\EmpAcc();
                $ua->optype = \App\Entity\EmpAcc::PRICE;
                $ua->document_id = $this->document_id;
                $ua->emp_id = $emp->employee_id;
                $ua->amount = \App\Helper::fa($cost*$emp->ktu);
                $ua->save();   
                
                $emp  = \App\Entity\Employee::load($ua->emp_id)  ;
     
                $user = \App\Entity\User::getByLogin($emp->login) ;
                                 
                if($user != null){
                    $n = new \App\Entity\Notify();
                    $n->user_id = $user->user_id; 
                    $n->message = "Нараховано до сплати {$ua->amount } ({$this->document_number})"    ;
                    $n->sender_id =  \App\Entity\Notify::SYSTEM;
                    $n->save();   
                }                   
                   
           }                
        }   
        
        if ($this->headerdata["emp"] > 0  ) {
                
            if($cost > 0){
                $ua = new \App\Entity\EmpAcc();
                $ua->optype = \App\Entity\EmpAcc::PRICE;
                $ua->document_id = $this->document_id;
                $ua->emp_id = $this->headerdata["emp"];
                $ua->amount = $cost;
                $ua->save();   
                
                $emp  = \App\Entity\Employee::load($ua->emp_id)  ;
     
                $user = \App\Entity\User::getByLogin($emp->login) ;
                                 
                if($user != null){
                    $n = new \App\Entity\Notify();
                    $n->user_id = $user->user_id; 
                    $n->message = "Нараховано до сплати {$cost} ({$this->document_number})"    ;
                    $n->sender_id =  \App\Entity\Notify::SYSTEM;
                    $n->save();   
                }                
                   
            }    
        }          
        //проводки  по  складу
        
        $proc= \App\Entity\ProdProc::load($this->headerdata['pp'])  ;
                      
        foreach ($this->unpackDetails('detaildata') as $item) {
            
    
            $st = \App\Entity\Stock::getStock($proc->store, $item->item_id, ($item->price ??0) ? $item->price : 1 , null, null, true);

            $sc = new Entry($this->document_id, $item->quantity * $st->partion, $item->quantity);
            $sc->setStock($st->stock_id);
            $sc->setOutPrice($st->partion);
            $sc->tag=Entry::TAG_MOVE;
            $sc->save();
        
            $sc = new Entry($this->document_id, $item->quantity * $st->partion, 0-$item->quantity);
            $sc->setStock($st->stock_id);
            $sc->setOutPrice($st->partion);
            $sc->tag=Entry::TAG_MOVE;
            $sc->save();
        
        }
    
        return true;
    }

    protected function getNumberTemplate() {
        return 'ПЕ-000000';
    }

    public function getRelationBased() {
        $list = array();
  
        return $list;
    }

}
