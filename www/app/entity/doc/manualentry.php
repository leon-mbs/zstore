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
         
        return true;
    }
    public   function DoAcc() {
       parent::DoAcc()  ;
       
       foreach ($this->unpackDetails('detaildata') as $item) {
           AccEntry::addEntry($item->accdt,$item->accct,H::fa($item->amount),$this->document_id);
         
       }
      
       
          
    } 
    public function generateReport() {
        $detail = array();
        $list = Account::getList( true,true);
        foreach ($this->unpackDetails('detaildata') as $item) {
                 $detail[] = array(
                "amount"          => H::fa($item->amount),
                "notes"           => $item->notes,
                "dt"              => $list[$item->accdt] ??'',
                "ct"              => $list[$item->accct] ??''  
                
                );  
             

                   
                 
       }
        $header = array(
            "_detail"         => $detail,
                            'amount'          => H::fa($this->amount),
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
