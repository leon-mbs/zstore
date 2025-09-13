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
       AccEntry::addEntry($this->headerdata["dt"],$this->headerdata["ct"],H::fa($this->amount),$this->document_id);
          
    } 
    public function generateReport() {

        $list = Account::getList(true);

        $header = array(
            'amount'          => H::fa($this->amount),
            'date'            => H::fd($this->document_date),
            "notes"           => nl2br($this->notes),
            "dt"              => $this->headerdata["dt"],
            "dtname"          => $list[$this->headerdata["dt"] ],
            "ct"              => $this->headerdata["ct"]  ,
            "ctname"          => $list[$this->headerdata["ct"] ],
            "document_number" => $this->document_number
        );
        
        if($header['dt']==0)  $header['dt'] ='-';
        if($header['ct']==0)  $header['ct'] ='-';
        
        $report = new \App\Report('doc/manualentry.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'РП-000000';
    }

}
