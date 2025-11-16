<?php

namespace App\Entity\Doc;

use App\Entity\Account;
use App\Entity\AccEntry;

/**
 * Класс-сущность  документ финансовые  результаты
 *
 */
class FinResult extends Document
{

    public function generateReport() {

      
        $header = array('date' => date('d.m.Y', $this->document_date),
            "document_number" => $this->document_number
        );
        $acc = Account::getSaldo('79',$this->document_date);
        $header['balans'] = \App\Helper::fa($acc['ct'] - $acc['dt'] ); 
        $header['isbalans'] = $this->state>4; 
        $report = new \App\Report('doc/finresult.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {

        
        $this->accIn('70');
     
        $this->accIn('71');
        
        $this->accOut('90');
        $this->accOut('90');
        $this->accOut('90');
        $this->accOut('90');
        $this->accOut('91');
        $this->accOut('92');
        $this->accOut('93');
        $this->accOut('941');
        $this->accOut('942');
        $this->accOut('943');
        $this->accOut('944');
        $this->accOut('947');
        $this->accOut('949');
        $this->accOut('97');
  
 
        return true;
    }
    protected function getNumberTemplate() {
        return 'ФР-000000';
    }
    //доходы
    protected function accIn($acc_code) {
        $acc = Account::getSaldo($acc_code,$this->document_date);
        if( ( $acc['ct'] ??0)>0)  {
            AccEntry::addEntry($acc_code,'79',$acc['ct'] ,$this->document_id);
        }
    }
    //себестоимость 
    protected function accOut($acc_code) {
        $acc = Account::getSaldo($acc_code,$this->document_date);
        if( ( $acc['dt'] ??0)>0)  {
          AccEntry::addEntry('79',$acc_code,$acc['dt'],$this->document_id);
        }
    }

}
