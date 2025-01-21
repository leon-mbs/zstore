<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ  заявка  поставщику
 *
 */
class OrderCust extends Document
{
    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {
            $detail[] = array("no"       => $i++,
                              "itemname" => $item->itemname,
                              "itemcode" => $item->item_code,
                              "brand" =>    $item->manufacturer,
                              "barcode" =>  $item->bar_code,
                              "custcode" => $item->custcode,
                              "quantity" => H::fqty($item->quantity),
                              "price"    => H::fa($item->price),
                              "msr"      => $item->msr,
                              "desc"     => $item->desc,
                              "amount"   => H::fa($item->quantity * $item->price)
            );
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "notes"           => nl2br($this->notes),
                       "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount)
        );

        $report = new \App\Report('doc/ordercust.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ЗП-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsReceipt'] = self::getDesc('GoodsReceipt');
        $list['InvoiceCust'] = self::getDesc('InvoiceCust');
        $list['OrderCust'] = self::getDesc('OrderCust');

        return $list;
    }

    
    /**
    * список  неоприходованых позиций
    * 
    */
    public function getNotReceivedItems() :array{
         $notrecqty=[]; 
         $recqty=[]; 
         $notrec=0;
         $docs= Document::find("state >=5 and meta_name  in ('GoodsReceipt') and parent_id=". $this->document_id);   
         foreach($docs as $d)  {
             foreach($d->unpackDetails('detaildata') as $item){
                if(!isset($recqty[$item->item_id]) ) $recqty[$item->item_id]=0;
                
                $recqty[$item->item_id] += $item->quantity;
             }
         }
         foreach($this->unpackDetails('detaildata') as $item){
            if(($recqty[$item->item_id] ?? 0) ==0)  {
                $notrec=$item->quantity;
            }   else {
                $notrec=$item->quantity - $recqty[$item->item_id];  
            }
            if($notrec > 0) {
                $notrecqty[$item->item_id]= $notrec;
            }
            
         }        
     
         return $notrecqty;
    }
}
