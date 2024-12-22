<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность  документ операции с ОС и НМА
 *
 */                                   
class EQ extends Document
{
    public function Execute() {

    
        return true;
    }

    public function generateReport() {


        $header = array(
            
            'opname'          => $this->headerdata['optypename'],
            'amount'          => H::fa($this->amount),
            "notes"           => nl2br($this->notes),
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/eq.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОС-000000';
    }

}
