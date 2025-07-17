<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ счет от поставщика
 *
 */
class InvoiceCust extends Document
{
    public function generateReport() {
        $firm = H::getFirmData(  $this->branch_id);

        $i = 1;

        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {
            $detail[] = array("no"       => $i++,
                              "itemname" => $item->itemname,
                              "itemcode" => $item->item_code,
                              "custcode" => $item->custcode,
                              "quantity" => H::fqty($item->quantity),
                              "price"    => H::fa($item->price),
                              "msr"      => $item->msr,
                              "amount"   => H::fa($item->quantity * $item->price)
            );
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "document_number" => $this->document_number,
                        
                         "isval"           => strlen($this->headerdata['val']) > 1,
                       "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "notes"           => nl2br($this->notes),
                       "total"           => H::fa($this->amount),
                        "payed"           => $this->headerdata['payed'] > 0 ? H::fa($this->headerdata['payed']) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false
        );
        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }

        $header['isdisc'] = $this->headerdata["disc"] > 0;
        $header['isnds'] = $this->headerdata["nds"] > 0;

        $header['disc'] = H::fa($this->headerdata["disc"]);
        $header['nds'] = H::fa($this->headerdata["nds"]);

        $header['rate'] = $this->headerdata["rate"];
        if ($header['rate'] == 0 || $header['rate'] == 1) {
            $header['isval'] = false;
        }
        $val = H::getValList();
        $header['val'] = $val[$this->headerdata['val']];

        $report = new \App\Report('doc/invoicecust.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $payed = $this->headerdata['payed'];
  

        $this->payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $payed, $this->headerdata['payment']);
    
        \App\Entity\IOState::addIOState($this->document_id, 0 - $payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);

       $this->DoBalans() ;


        return true;
    }

    protected function getNumberTemplate() {
        return 'РВ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsReceipt'] = self::getDesc('GoodsReceipt');

        return $list;
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

        foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where paytype < 1000 and   coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = 0-$p['amount'];
            $b->createdon = strtotime($p['paydate']);
            $b->optype = \App\Entity\CustAcc::SELLER;
            $b->save();
        }


    }
}
