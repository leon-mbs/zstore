<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;

/**
 * Класс-сущность  документ счет от поставщика
 *
 */
class InvoiceCust extends Document {

    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "itemname" => $value['itemname'],
                "itemcode" => $value['item_code'],
                "quantity" => H::fqty($value['quantity']),
                "price" => $value['price'],
                "msr" => $value['msr'],
                "amount" => round($value['quantity'] * $value['price'])
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->customer_name,
            "document_number" => $this->document_number,
            "total" => $this->amount ,
            "payed" => $this->headerdata['payed'] ,
            "payamount" => $this->payamount
        );


        $report = new \App\Report('invoicecust.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $this->payed = 0;
        if (  $this->headerdata['payment'] >0 && $this->headerdata['payed']) {
            \App\Entity\Pay::addPayment($this->document_id,1,0-$this->headerdata['payed'], $this->headerdata['payment'],\App\Entity\Pay::PAY_BASE_OUTCOME, $this->headerdata['paynotes']);
            $this->payed = $this->headerdata['payed']; 
             
        }

        return true;
    }

    public function getRelationBased() {
        $list = array();

       // $list['GoodsReceipt'] = 'Приходная накладная';

        return $list;
    }


    protected function getNumberTemplate(){
         return  'СВ-000000';
    }      
    
}
