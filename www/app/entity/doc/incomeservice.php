<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность документ  Оказаные услуги
 *
 *
 */
class IncomeService extends Document
{
    public function generateReport() {
        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $i = 1;
        $itemset =    $this->unpackDetails('setdata');
        $hasitems = false;

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $ser) {

            $detail[] = array("no"           => $i++,
                              "service_name" => $ser->service_name,
                              "desc"         => $ser->desc,
                              "items"         => $items,
                              "qty"          => H::fqty($ser->quantity),
                              "price"        => H::fa($ser->price),
                              "amount"       => H::fa($ser->price * $ser->quantity)
            );

        }
      
        $i = 1;        
        $samount =0;
        $detail2 = array();        
        foreach ($this->unpackDetails('detaildata2') as $item) {

            $detail2[] = array("no"           => $i++,
                              "itemname"     => $item->itemname,
                              "item_code"         => $item->item_code,
                            
                              "qty"          => H::fqty($item->quantity),
                              "price"        => H::fa($item->price),
                              "amount"       => H::fa($item->price * $item->quantity)
            );
            
            $samount += ($item->price * $item->quantity);

        }
        
        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "_detail2"        => $detail2,
                        "hasitems"        => count($detail2) >0,
                        "notes"           => $this->notes,
                        "customer_name"   => $this->customer_name,
                        "store_name"      => $this->headerdata['store_name'],
                        "firm_name"       => $firm['firm_name'],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "document_number" => $this->document_number,
                        "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false,
                        "stotal"           => H::fa($samount) ,
                        "total"           => H::fa($this->amount)
        );
        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }


        $report = new \App\Report('doc/incomeservice.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


     
        foreach ($this->unpackDetails('detaildata') as  $ser) {

            $sc = new Entry($this->document_id, 0 - ($ser->price * $ser->quantity), 0 - $ser->quantity);
            $sc->setService($ser->service_id);


            $sc->setOutPrice($ser->price);
            $sc->save();

           
        }
        $amount=0;
        $store= intval($this->headerdata['store'] ) ;
        if($store > 0) {
           foreach ($this->unpackDetails('detaildata2') as $item) {
                $stockto = Stock::getStock($store, $item->item_id, $item->price, $item->snumber, $item->sdate, true);
                $sc = new Entry($this->document_id, $item->quantity * $item->price, $item->quantity);
                $sc->setStock($stockto->stock_id);
                $sc->save();
                $amount = $amount + $item->quantity * $item->price;
                          
           }
           
           \App\Entity\IOState::addIOState($this->document_id, $amount, \App\Entity\IOState::TYPE_INSERVICE);
           
           
        }

      
        
        $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0-$this->payed, $this->headerdata['payment']);
        if ($payed > 0) {
            $this->payed = $payed;
        }
        \App\Entity\IOState::addIOState($this->document_id, 0-$this->payed, \App\Entity\IOState::TYPE_OUTSERVICE);




    }



    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF );
    }

    protected function getNumberTemplate() {
        return 'ВП-000000';
    }


    public function getRelationBased() {
        $list = array();
        $list['IncomeService'] = self::getDesc('IncomeService');

        return $list;
    }
    public function DoBalans() {

    }
}
