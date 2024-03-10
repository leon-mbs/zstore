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
        $d = $this->unpackDetails('detaildata')  ;

        $header = array(
  
            "content"     => $d['data']??'' 

        );
        $report = new \App\Report('doc/officedoc.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОФ-000000';
    }
   public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_POS, self::EX_PDF);
    }

}
