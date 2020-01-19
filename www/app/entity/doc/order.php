<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\Util;

/**
 * Класс-сущность  документ расходная  накладая
 *
 */
class Order extends \App\Entity\Doc\Document {

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->detaildata as $value) {

            if (isset($detail[$value['item_id']])) {
                $detail[$value['item_id']]['quantity'] += $value['quantity'];
            } else {

                $ocstoreopt = @unserialize($value['octoreoptions']);  //опции с  опенкарта
                if (is_array($ocstoreopt)) {
                    $t = "<table cellspacing='0' cellpadding='1' style='font-size:smaller'><tr><td style='padding: 1px;'>Опции:</td><td style='padding: 1px;'></td></tr>";
                    foreach ($ocstoreopt as $k => $v) {
                        $t .= "<tr><td style='padding: 1px;'>{$k}</td><td style='padding: 1px;'>{$v}</td></tr>";
                    }
                    $t .= "</table>";
                    $value['itemname'] = $value['itemname'] . $t;
                }
                $detail[] = array("no" => $i++,
                    "tovar_name" => $value['itemname'],
                    "tovar_code" => $value['item_code'],
                    "quantity" => H::fqty($value['quantity']),
                    "price" => H::fa($value['price']),
                    "msr" => $value['msr'],
                    "amount" => H::fa($value['quantity'] * $value['price'])
                );
            }
        }



        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->headerdata["customer_name"],
            "phone" => $this->headerdata["phone"],
            "email" => $this->headerdata["email"],
            "delivery" => $this->headerdata["delivery_name"],
            "notes" => $this->notes,
            "document_number" => $this->document_number,
            "total" => H::fa($this->amount)
        );
        if ($this->headerdata["delivery"] == 2 || $this->headerdata["delivery"] == 3) {
            $header['delivery'] = $header['delivery'] . '. по адресу: ' . $this->headerdata["address"];
        }

        $list = $this->getChildren('GoodsIssue');
        foreach ($list as $d) {

            $header['ttn'] = $d->document_number;
        }


        $report = new \App\Report('order.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {

        return true;
    }

    protected function getNumberTemplate() {
        return 'ЗК-000000';
    }

}
