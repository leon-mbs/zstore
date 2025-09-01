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
                        "pareaname"       => $this->headerdata["pareaname"] ??'',
                        "storename"       => $this->headerdata["storename"]??'',
                        "notes"           => nl2br($this->notes),
                        "emp"             => false,
                      "total"           => H::fa($this->amount)
        );
        if ($this->headerdata["emp"] > 0  ) {
            $header['emp'] = $this->headerdata["empname"];
        }
        $report = new \App\Report('doc/prodreceipt.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $types = array();
        $common = \App\System::getOptions("common");
        $lost = 0;
        $cost = 0;
        
        foreach ($this->unpackDetails('detaildata') as $item) {
            if($item->zarp > 0) {
                $cost += doubleval($item->zarp * $item->quantity) ;
            }
            if ($item->autooutcome == 1) {  //списание  комплектующих
                $set = \App\Entity\ItemSet::find("pitem_id=" . $item->item_id);
                foreach ($set as $part) {
                    $lost = 0;

                    $itemp = \App\Entity\Item::load($part->item_id);
                    if($itemp==null) {
                        continue;
                    }
                    
                       //учитываем  отходы
                        $kl=0;
                        if ($itemp->lost > 0) {
                            $kl = 1 / (1 - $itemp->lost / 100);
                            $itemp->quantity = $itemp->quantity * $kl;
                                              
                        }
                    $itemp->quantity = $item->quantity * $part->qty;
                    if (false == $itemp->checkMinus($itemp->quantity, $this->headerdata['store'])) {
                        throw new \Exception("На складі всього ".H::fqty($itemp->getQuantity($this->headerdata['store']))." ТМЦ {$itemp->itemname}. Списання у мінус заборонено");
                    }                    
                    $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $itemp);

                    foreach ($listst as $st) {
                        $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                        $sc->setStock($st->stock_id);
                        $sc->setOutPrice($st->partion);
                        $sc->tag=Entry::TAG_TOPROD;

                        $sc->save();
                        
                        if ($kl > 0) {
                             $lost += abs($st->quantity * $st->partion  ) * ($itemp->lost / 100);
                        }     
                        
                    }
                }
            }


            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $item->price, $item->snumber, $item->sdate, true);

            $sc = new Entry($this->document_id, $item->quantity * $item->price, $item->quantity);
            $sc->setStock($stock->stock_id);
            $sc->setOutPrice($stock->partion);
            $sc->tag=Entry::TAG_FROMPROD;

            $sc->save();
        }

       if ($lost > 0) {
            $io = new \App\Entity\IOState();
            $io->document_id = $this->document_id;
            $io->amount =  0 - abs($lost);
            $io->iotype = \App\Entity\IOState::TYPE_TRASH;

            $io->save();
       }
       if ($this->getHD('emp') > 0 && $cost > 0 ) {
    
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::PRICE;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $this->getHD('emp');
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
        if ($this->getHD('st_id') > 0 && $cost > 0 ) {
          //  $st = \App\Entity\ProdStage::load($this->getHD('st_id') ) ;
            
           
        } 
        return true;
    }

    protected function getNumberTemplate() {
        return 'ОВ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['MoveItem'] = self::getDesc('MoveItem');
        $list['ProdReceipt'] = self::getDesc('ProdReceipt');
        $list['ProdIssue'] = self::getDesc('ProdIssue'); //для  формирования  комплектубщих  по  готовой продукции


        return $list;
    }

}
