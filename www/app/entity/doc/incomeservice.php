<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
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
        foreach ($this->unpackDetails('detaildata') as $r=>$ser) {
           
           
            $items = array();
            if( @is_array($itemset[$r]) )  {
                $hasitems = true;
                foreach($itemset[$r] as $it) {
                   $items[]=array('itemname'=>$it->itemname,'qty'=> H::fqty(  $it->qty),'price'=> H::fa( $it->price) );  
                }
            }             
           
           
           
            $detail[] = array("no"           => $i++,
                              "service_name" => $ser->service_name,
                              "desc"         => $ser->desc,
                              "items"         => $items,
                              "qty"          => H::fqty($ser->quantity),
                              "price"        => H::fa($ser->price),
                              "amount"       => H::fa($ser->price * $ser->quantity)
            );
          
            
            
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "hasitems"        => $hasitems,
                        "customer_name"   => $this->customer_name,
                        "firm_name"       => $firm['firm_name'],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "document_number" => $this->document_number,
                        "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false,
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


        $itemset =    $this->unpackDetails('setdata');

                 
        foreach ($this->unpackDetails('detaildata') as $r=>$ser) {

            $sc = new Entry($this->document_id, 0 - ($ser->price * $ser->quantity), 0 - $ser->quantity);
            $sc->setService($ser->service_id);

    
            $sc->setOutPrice($ser->price);
            $sc->save();
            
            if( @is_array($itemset[$r]) )  {
                 
                foreach($itemset[$r] as $it) {
                    $ss = \App\Entity\ItemSet::getFirst("service_id={$ser->service_id}  and  pitem_id={$it->item_id}")  ;
                    if($ss instanceof \App\Entity\ItemSet ) {
                       $ss->cost = $it->price; 
                       $ss->save(); 
                    }
                }
            }
            
        }
        
        
        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0-$this->payed, $this->headerdata['payment']);
            if ($payed > 0) {
                $this->payed = $payed;
            }
            \App\Entity\IOState::addIOState($this->document_id, 0-$this->payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);

        }        
        
        
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

}
