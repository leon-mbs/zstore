<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ торгово-транспортная  накладная
 *
 */
class TTN extends Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();
        $weight = 0;

        foreach ($this->unpackDetails('detaildata') as $item) {


            $name = $item->itemname;
            if (strlen($item->snumber) > 0) {
                $s = ' (' . $item->snumber . ' )';
                if (strlen($item->sdate) > 0) {
                    $s = ' (' . $item->snumber . ',' . H::fd($item->sdate) . ')';
                }
                $name .= $s;

            }
            if ($item->weight > 0) {
                $weight += $item->weight;
            }

            $detail[] = array("no"         => $i++,
                              "tovar_name" => $name,
                              "tovar_code" => $item->item_code,
                              "quantity"   => H::fqty($item->quantity),
                              "msr"        => $item->msr,

                              "price"  => H::fa($item->price),
                              "amount" => H::fa($item->quantity * $item->price)
            );

        }

     

        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm['firm_name'],
                        "customer_name"   => $this->customer_id ? $this->customer_name : $this->headerdata["customer_name"],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "store_name"      => $this->headerdata["store_name"],
                        "weight"          => $weight > 0 ? H::l("allweight", $weight) : '',
                        "ship_address"    => strlen($this->headerdata["ship_address"]) >0 ? $this->headerdata["ship_address"] : false ,
                        "ship_number"     => strlen($this->headerdata["ship_number"]) >0 ? $this->headerdata["ship_number"] : false ,
                        "delivery_name"     => $this->headerdata["delivery_name"],
                        "order"           => strlen($this->headerdata["order"]) > 0 ? $this->headerdata["order"] : false,
                        "emp_name"        => $this->headerdata["emp_name"],
                        "document_number" => $this->document_number,

                     
                        "total"     => H::fa($this->amount),
        );

 


        if ($this->headerdata["sent_date"] > 0) {
            $header['sent_date'] = H::fd($this->headerdata["sent_date"]);
        }
        if ($this->headerdata["delivery_date"] > 0) {
            $header['delivery_date'] = H::fd($this->headerdata["delivery_date"]);
        }
    
        $report = new \App\Report('doc/ttn.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //$conn = \ZDB\DB::getConnect();


        foreach ($this->unpackDetails('detaildata') as $item) {
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $item->price, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->setExtCode($item->price - $st->partion); //Для АВС 
                $sc->save();
            }
        }

   

        return true;
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = self::getDesc('Warranty');
        $list['ReturnIssue'] = self::getDesc('ReturnIssue');
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');

        return $list;
    }

    protected function getNumberTemplate() {
        return 'ТТН-000000';
    }

    
   
   
   public function supportedExport() {
        return array(self::EX_EXCEL,   self::EX_PDF);
    }
   
}
