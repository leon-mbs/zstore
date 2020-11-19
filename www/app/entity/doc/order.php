<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ расходная  накладая
 *
 */
class Order extends \App\Entity\Doc\Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {

                $ocstoreopt = @unserialize($item->octoreoptions);  //опции с  опенкарта
                if (is_array($ocstoreopt)) {
                    $t = "<table cellspacing='0' cellpadding='1' style='font-size:smaller'><tr><td style='padding: 1px;'>Опции:</td><td style='padding: 1px;'></td></tr>";
                    foreach ($ocstoreopt as $k => $v) {
                        $t .= "<tr><td style='padding: 1px;'>{$k}</td><td style='padding: 1px;'>{$v}</td></tr>";
                    }
                    $t .= "</table>";
                    $item->itemname = $item->itemname . $t;
                }
                $detail[] = array("no"         => $i++,
                                  "tovar_name" => $item->itemname,
                                  "tovar_code" => $item->item_code,
                                  "quantity"   => H::fqty($item->quantity),
                                  "price"      => H::fa($item->price),
                                  "msr"        => $item->msr,
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
                        "notes"           => $this->notes,
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount)
        );
        if ($this->headerdata["delivery"] == 2 || $this->headerdata["delivery"] == 3) {
            $header['delivery'] = $header['delivery'] . '. по адресу: ' . $this->headerdata["address"];
        }

        $list = $this->getChildren('GoodsIssue');
        foreach ($list as $d) {

            $header['ttn'] = $d->document_number;
        }


        $report = new \App\Report('doc/order.tpl');

        $html = $report->generate($header);

        return $html;
    }


    protected function getNumberTemplate() {
        return 'ЗК-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');
        $list['Invoice'] = self::getDesc('Invoice');
        $list['POSCheck'] = self::getDesc('POSCheck');


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

        $firm = H::getFirmData($this->headerdata["firm_id"], $this->branch_id);

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm["firm_name"],
                        "phone"           => $firm["phone"],
                        "customer_name"   => strlen($this->headerdata["customer_name"]) > 0 ? $this->headerdata["customer_name"] : false,
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount)

        );


        $report = new \App\Report('doc/order_bill.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
