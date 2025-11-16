<?php

namespace App\Entity\Doc;

use App\Entity\Account;
use App\Entity\AccEntry;
use App\Helper as H;

/**
 * Класс-сущность  документ ручная проводка
 *
 */
class ManualEntry extends Document
{
    public function Execute() {
        $this->DoAcc();  
        
        return true;
    }
    public   function DoAcc() {
       
       $conn = \ZDB\DB::getConnect(); 
       if($this->headerdata['remove']==1) {
           $dt=$conn->DBDate($this->document_date);
           $sql="delete from acc_entry where  ( createdon is not null and createdon<={$dt} ) || document_id in(select document_id from documents where document_date <={$dt} )  ";
           $conn->Execute($sql);
       }   
           
       foreach ($this->unpackDetails('detaildata') as $item) {
           AccEntry::addEntry($item->accdt,$item->accct,H::fa($item->amount),$this->document_id);
         
       }

      
       if($this->headerdata['reload']==1) {
           foreach(Document::findYield("document_date > {$dt}  and state > 4 ") as $doc){
               $doc = $doc->cast();
               $doc->DoAcc();
               
               unset($doc);
           }
       }
      
       
          
    } 
    public function generateReport() {
        $detail = array();
        $list = Account::getList( true );
        foreach ($this->unpackDetails('detaildata') as $item) {
                 $detail[] = array(
                "amount"          => H::fa($item->amount),
             
                "dt"              => $list[$item->accdt] ??'',
                "ct"              => $list[$item->accct] ??''  
                
                );  
             

                   
                 
       }
        $header = array(
            "_detail"         => $detail,
             "notes"           => $this->notes,
            'date'            => H::fd($this->document_date),
            "document_number" => $this->document_number
        );
        
         
        $report = new \App\Report('doc/manualentry.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'РП-000000';
    }

}
