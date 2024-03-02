<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность офисный документ
 *
 */
class OfficeDoc extends Document
{
    public function Execute() {
     
    }

    public function generateReport() {


        $header = array(
  
     //       'date'            => H::fd($this->document_date),
//            "notes"           => nl2br($this->notes),
            "data" => @base64_decode( $this->headerdata['detail'] );
        );
        $report = new \App\Report('doc/officedoc.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОФ-000000';
    }

}
