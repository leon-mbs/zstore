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
        $firm = H::getFirmData($this->firm_id, $this->branch_id);

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
                        "firm_name"       => $firm['firm_name'],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                         "isval"           => strlen($this->headerdata['val']) > 1,
                       "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "notes"           => nl2br($this->notes),
                       "total"           => H::fa($this->amount),
                        "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
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
        $payed = $this->payed;
        $rate= doubleval($this->headerdata["rate"]);
        if ($rate != 0 && $rate != 1) {
            $payed = $payed * $rate; 
        }
        if ($this->headerdata['payment'] > 0 && $payed >0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $payed, $this->headerdata['payment'] );
            if ($payed > 0) {
                $this->payed = $payed;
            }
            \App\Entity\IOState::addIOState($this->document_id, 0 - $this->payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);

        }

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

}
