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
        $emp= intval($this->headerdata['employee'] ??0);
        if($emp==0) {
            return;
        }
        $bonus= $this->headerdata['bonus'];
        $fine= $this->headerdata['fine'];
        
        if($bonus  >0) {
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::BONUS;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $emp;
            $ua->amount = $bonus;
            $ua->save();
        }
        if($fine  >0) {
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::FINE;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $emp;
            $ua->amount = 0-$fine;
            $ua->save();
        }
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
        return array(self::EX_EXCEL,  self::EX_PDF);
    }

    public function checkShow() {
        return true;
    }
    public function checkExe() {
        return true;
    }
    public function checkApprove() {
        return true;
    }
    protected function onState($state, $oldstate) {

        if($state == Document::STATE_FINISHED) {
            $this->Execute();
        }
        
    }
}
