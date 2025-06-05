<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ возвратная накладная
 *
 */
class ReturnIssue extends Document
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
                                  "quantity"   => H::fqty($item->quantity),
                                  "price"      => H::fa($item->price),
                                  "msr"        => $item->msr,
                                  "amount"     => H::fa($item->quantity * $item->price)
                );
            }
        }


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $this->headerdata["firm_name"],
                        "customer_name"   => $this->customer_name,
                        "notes"           => nl2br($this->notes),
                        "document_number" => $this->document_number,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"]??0) > 0 ? $this->headerdata["fiscalnumber"] ??'' : false,
                        "total"           => H::fa($this->amount),
                        "payamount"           => H::fa($this->payamount),
                        "payed"           => H::fa($this->headerdata['payed'])
        );

        $report = new \App\Report('doc/returnissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
        $dd =    doubleval($this->headerdata['discount']??0)  ; 
        $k = 1;   //учитываем  скидку
        if ($dd > 0 && $this->amount > 0) {
            $k = ($this->amount - $dd) / $this->amount;
        }
        foreach ($this->unpackDetails('detaildata') as $item) {

            $lp = $item->getLastPartion($this->headerdata['store'], $item->snumber, false);

            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $lp, $item->snumber, $item->sdate, true);

            $sc = new Entry($this->document_id, $item->amount, $item->quantity);
            $sc->setStock($stock->stock_id);

            //  $sc->setExtCode(($item->price - $stock->partion)); //Для АВС
            //  $sc->setCustomer($this->customer_id);
            $sc->setOutPrice($item->price* $k);
            $sc->tag=Entry::TAG_RSELL;
            $sc->save();
        }

        $this->payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $this->headerdata['payed'], $this->headerdata['payment']);
    
        \App\Entity\IOState::addIOState($this->document_id, 0 - $this->headerdata['payed'], \App\Entity\IOState::TYPE_BASE_INCOME,true);
        $this->DoBalans() ;

        if($this->headerdata["bonus"] > 0) {
                $ca = new \App\Entity\CustAcc();

                $ca->document_id = $this->document_id;
                $ca->amount = $this->headerdata["bonus"];
                $ca->optype = \App\Entity\CustAcc::BONUS;
               
                $ca->customer_id = $this->customer_id;

                $ca->save();       
        }

        //штраф  сотруднику
       if ($this->parent_id > 0) {
            $parent = Document::load($this->parent_id);
            $user = \App\Entity\User::load($parent->user_id);        
            $disc = \App\System::getOptions("discount");
            $emp_id = \App\System::getUser()->employee_id ;
            if($emp_id >0 && ($disc["fineret"]??0 )>0  && $parent->meta_name=='POSCheck') {
                $b = intval( $this->amount * $disc["fineret"] / 100);
                if($b>0) {
                    $ua = new \App\Entity\EmpAcc();
                    $ua->optype = \App\Entity\EmpAcc::FINE;
                    $ua->document_id = $this->document_id;
                    $ua->emp_id = $emp_id;
                    $ua->amount = 0-$b;
                    $ua->save();

                    $n = new \App\Entity\Notify();
                    $n->user_id = \App\System::getUser()->user_id;;;
                    $n->message = "Штраф { $b} ({$this->document_number})"    ;
                    $n->sender_id =  \App\Entity\Notify::SYSTEM;
                    $n->save();                  
                } 
                
            }
            
        }     
        
        return true;
    }

    protected function getNumberTemplate() {
        return 'BK-000000';
    }
    /**
    * @overrride
    */
    public function DoBalans() {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from custacc where optype in (2,3) and document_id =" . $this->document_id);
     
        if(($this->customer_id??0) == 0) {
            return;
        }

              
        //платежи       
        foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where  paytype < 1000 and  coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = 0-$p['amount'];
            $b->createdon = strtotime($p['paydate']);
            $b->optype = \App\Entity\CustAcc::BUYER;
            $b->save();
        }
        
        if($this->payamount >0) {
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = $this->payamount;
            $b->optype = \App\Entity\CustAcc::BUYER;
            $b->save();
        }
    }
}
