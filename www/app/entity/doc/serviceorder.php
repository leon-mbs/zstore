<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;

/**
 * Класс-сущность  заказ  на  услуги
 *
 *
 */
class ServiceOrder extends Document {

    public function generateReport() {

        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "servicename" => $value['service_name'],
                "desc" => $value['desc'],
                "quantity" => $value['quantity'],
                "price" => H::fa($value['price']),
                "amount" => H::fa($value['quantity'] * $value['price'])
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer" => $this->headerdata["customer_name"],
            "document_number" => $this->document_number,
            "total" =>H::fa( $this->amount )
        );
        $report = new \App\Report('serviceorder.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {

        return true;
    }

  

    protected function getNumberTemplate() {
        return 'ЗУ-000000';
    }

}
