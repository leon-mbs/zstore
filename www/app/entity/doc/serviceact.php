<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;

/**
 * Класс-сущность  локумент акт  о  выполненных работах
 *
 *
 */
class ServiceAct extends Document {

    public function generateReport() {

        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "servicename" => $value['service_name'],
                "desc" => $value['desc'],
              
                "price" => H::fa($value['price']) 
                 
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->headerdata["customer_name"],
            "order" => $this->headerdata["order"],
            "gar" => $this->gar,
            "isdevice" => strlen($this->headerdata["device"])>0,
            "device" => $this->headerdata["device"],
            "devsn" => $this->headerdata["devsn"],
            "document_number" => $this->document_number,
            "payamount" => H::fa($this->payamount),
            "payed" => H::fa($this->payed),
            "total" => H::fa($this->amount)
        );
        $report = new \App\Report('serviceact.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->detaildata as $row) {

            $sc = new Entry($this->document_id, 0 - $row['price'], 0  );
            $sc->setService($row['service_id']);
            $sc->setExtCode($row['price']); //Для АВС 
            //$sc->setCustomer($this->customer_id);
            $sc->save();
        }



        return true;
    }

     public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF,self::EX_POS);
     }

    protected function getNumberTemplate() {
        return 'АКТ-000000';
    }
  
    public function generatePosReport() {
    
        
          $header = array('printw'=>'style="width:80mm"','date' => date('d.m.Y', $this->document_date), 
          "document_number" => $this->document_number);
        
    
        $report = new \App\Report('serviceact_bill.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
