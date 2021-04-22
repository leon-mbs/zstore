<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ заказ (общепит)
 *
 */
class OrderFood extends \App\Entity\Doc\Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {

                /*
                  $ocstoreopt = @unserialize($item->octoreoptions);  //опции с  опенкарта
                  if (is_array($ocstoreopt)) {
                  $t = "<table cellspacing='0' cellpadding='1' style='font-size:smaller'><tr><td style='padding: 1px;'>Опции:</td><td style='padding: 1px;'></td></tr>";
                  foreach ($ocstoreopt as $k => $v) {
                  $t .= "<tr><td style='padding: 1px;'>{$k}</td><td style='padding: 1px;'>{$v}</td></tr>";
                  }
                  $t .= "</table>";
                  $item->itemname = $item->itemname . $t;
                  } */
                $detail[] = array("no"         => $i++,
                                  "tovar_name" => $item->itemname,
                                  "tovar_code" => $item->item_code,
                                  "quantity"   => H::fqty($item->quantity),
                                  "price"      => H::fa($item->price),
                                  "msr"        => $item->msr,
                                  "desc"       => $item->desc,
                                  "amount"     => H::fa($item->quantity * $item->price)
                );
            }
        }


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "phone"           => $this->headerdata["phone"],
                        "email"           => $this->headerdata["email"],
                        "delivery"        => $this->headerdata["delivery_name"],
                        "ship_address"    => strlen($this->headerdata["ship_address"]) > 0 ? $this->headerdata["ship_address"] : false,
                        "notes"           => nl2br($this->notes),
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "payed"           => H::fa($this->payed),
                        "paydisc"         => H::fa($this->headerdata["paydisc"]),
                        "isdisc"          => $this->headerdata["paydisc"] > 0,
            //"prepaid"   => $this->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID,
                        "payamount"       => H::fa($this->payamount)
        );

        $report = new \App\Report('doc/orderfood.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ЗО-000000';
    }

    public function getRelationBased() {
        $list = array();

        return $list;
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_POS);
    }

    public function generatePosReport() {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {


            $detail[] = array(
                "tovar_name" => $item->itemname,
                "quantity"   => H::fqty($item->quantity),
                "price"      => H::fa($item->price),
                "amount"     => H::fa($item->quantity * $item->price)
            );
        }

        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm["firm_name"],
                        "phone"           => $firm["phone"],
                        "customer_name"   => strlen($this->headerdata["customer_name"]) > 0 ? $this->headerdata["customer_name"] : false,
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount)
        );

        $report = new \App\Report('doc/orderfood_bill.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function onState($state) {

    }

}
