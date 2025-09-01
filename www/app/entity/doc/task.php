<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  наряд
 *
 *
 */
class Task extends Document
{
    protected function init() {
        parent::init();
        // $this->tasktype = 0;//0 - услуги,1- производство
    }

    public function generateReport() {

        $i = 1;

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $ser) {
            if ($ser->cost == "") {
                $ser->cost = 0;
            }
            if ($ser->hours == "") {
                $ser->hours = 0;
            }

            if (strlen( $ser->quantity ??'' )==0 ) {
                $ser->quantity = 1;
            }

            
            $detail[] = array("no"           => $i++,
                              "service_name" => $ser->service_name,
                              "desc"         => $ser->desc,
                              "quantity"     => H::fqty($ser->quantity),
                              "cost"         => H::fa(doubleval($ser->cost) * doubleval($ser->quantity) ),
                              "category"     => $ser->category,
                              "hours"        => doubleval($ser->hours) *doubleval( $ser->quantity)
            );
        }

        $detail2 = array();
        foreach ($this->unpackDetails('eqlist') as $eq) {


            $detail2[] = array(
                "eq_name" => $eq->eq_name,
                "invnumber"    => $eq->invnumber
            );
        }
        $detail3 = array();
        foreach ($this->unpackDetails('emplist') as $emp) {
            $detail3[] = array(
                "emp_name" => $emp->emp_name,
                "emp_ktu"  => $emp->ktu
            );
        }


        $detailprod = array();

        foreach ($this->unpackDetails('prodlist') as $item) {

            $detailprod[] = array("no"       => $i++,
                                  "itemname" => $item->itemname,
                                  "desc"     => $item->desc,
                                  "item_code"     => $item->item_code,
                                  "quantity" => H::fqty($item->quantity));
        }


        $header = array('date'            => H::fd($this->document_date),
                        "pareaname"       => strlen($this->headerdata["pa_name"]) > 0 ? $this->headerdata["pa_name"] : false,
                        "document_date"   => H::fd($this->document_date),
                        "document_number" => $this->document_number,
                        "notes"           => nl2br($this->notes),
                        "baseddoc"        => strlen($this->headerdata["parent_number"]??'') > 0 ? $this->headerdata["parent_number"] : false,
                        "cust"            => strlen($this->customer_name) > 0 ? $this->customer_name : false,
                        "_detail"         => $detail,
                        "_detailprod"     => $detailprod,
                        "_detail2"        => $detail2,
                        "iseq"            => count($detail2) > 0,
                        "_detail3"        => $detail3
        );
        $report = new \App\Report('doc/task.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
       
        foreach ($this->unpackDetails('detaildata') as $ser) {

            $sc = new Entry($this->document_id, 0 - $ser->cost, $ser->qty);
            $sc->setService($ser->service_id);                            
            // $sc->save();
            
           
        }
         

        return true;
    }

    protected function getNumberTemplate() {
        return 'НР-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['ProdIssue'] = self::getDesc('ProdIssue');
        $list['ProdReceipt'] = self::getDesc('ProdReceipt');
        $list['ServiceAct'] = self::getDesc('ServiceAct');
    //    $list['POSCheck'] = self::getDesc('POSCheck');

        return $list;
    }
    
    public function onState($state, $oldstate) {
         if ($state == Document::STATE_INPROCESS) {
             foreach ($this->unpackDetails('eqlist') as $eq) {

                $entry = new \App\Entity\EqEntry( );
                $entry->document_id = $this->document_id;
                $entry->eq_id = $eq->eq_id;
                $entry->optype= \App\Entity\EqEntry::OP_MOVE;
                $entry->amount = 0   ;
                $entry->save();    
               
            }

         }
         if ($state == Document::STATE_CLOSED ) {
              
            $total = 0;
            foreach ($this->unpackDetails('detaildata') as $ser) {
                $total += doubleval($ser->cost * $ser->quantity)  ;
            }
                 
             
            foreach ($this->unpackDetails('prodlist') as $item) {
                if($item->zarp > 0) {
                    $total += doubleval($item->zarp*$item->quantity) ;
                } 
            }          
          
            $emplist = $this->unpackDetails('emplist');
            foreach ($emplist as $emp) {

                 $cost =   doubleval($total * $emp->ktu) ;
                 if($cost > 0){
                     
                   
                     
                    $ua = new \App\Entity\EmpAcc();
                    $ua->optype = \App\Entity\EmpAcc::PRICE;
                    $ua->document_id = $this->document_id;
                    $ua->emp_id = $emp->employee_id;
                    $ua->amount = $cost;
                    $ua->save();     
         
                            
                    
                     
                 }  
            }          
         }           
    }

}
