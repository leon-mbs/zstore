<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ возврат  поставщику
 *
 */
class RetCustIssue extends Document
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
                                  "quantity"   => H::fqty($item->quantity),
                                  "msr"        => $item->msr,
                                  "price"      => H::fa($item->price),
                                  "amount"     => H::fa($item->quantity * $item->price)
                );
            }
        }


        $customer = \App\Entity\Customer::load($this->customer_id);

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $this->headerdata["firm_name"],
                        "customer_name"   => $this->customer_name,
                        "notes"           => nl2br($this->notes),
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "payed"           => H::fa($this->headerdata["payed"])
        );

        $report = new \App\Report('doc/retcustissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();

        foreach ($this->unpackDetails('detaildata') as $item) {


            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $item->price, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                //   $sc->setExtCode($item->price - $st->partion); //Для АВС
                $sc->setOutPrice($item->price);
                $sc->tag=Entry::TAG_RBAY;
                $sc->save();
            }
        }

        $this->payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->headerdata['payed'], $this->headerdata['payment']);
 
        \App\Entity\IOState::addIOState($this->document_id, $this->headerdata['payed'], \App\Entity\IOState::TYPE_BASE_OUTCOME,true);

       $this->DoBalans() ;



        return true;
    }

    protected function getNumberTemplate() {
        return 'ВП-000000';
    }
    /**
    * @override
    */
    public function DoBalans() {
       $conn = \ZDB\DB::getConnect();
       $conn->Execute("delete from custacc where optype in (2,3) and document_id =" . $this->document_id);

        if(($this->customer_id??0) == 0) {
            return;
        }

       foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where  paytype < 1000 and  coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount =  $p['amount'];
            $b->createdon = strtotime($p['paydate']);
            $b->optype = \App\Entity\CustAcc::SELLER;
            $b->save();
        }

        if($this->payamount >0) {
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = 0-$this->payamount;
            $b->optype = \App\Entity\CustAcc::SELLER;
            $b->save();
        }

    }
}
