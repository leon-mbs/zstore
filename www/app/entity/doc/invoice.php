<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\Util;

/**
 * Класс-сущность  документ счет фактура
 *
 */
class Invoice extends \App\Entity\Doc\Document {

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->detaildata as $value) {

            if (isset($detail[$value['item_id']])) {
                $detail[$value['item_id']]['quantity'] += $value['quantity'];
            } else {
                $detail[] = array("no" => $i++,
                    "tovar_name" => $value['itemname'],
                    "tovar_code" => $value['item_code'],
                    "quantity" => H::fqty($value['quantity']),
                    "price" => $value['price'],
                    "msr" => $value['msr'],
                    "amount" => round($value['quantity'] * $value['price'])
                );
            }
        }

        //$firm = \App\System::getOptions("common");


        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customername" => $this->customer_name,
            "phone" => $this->headerdata["phone"],
            "email" => $this->headerdata["email"],
            "notes" => $this->notes,
            "document_number" => $this->document_number,
            "total" => $this->amount,
            "payamount" => $this->payamount,
            "payed" => $this->headerdata['payed'],
            "paydisc" => $this->headerdata["paydisc"]
        );


        $report = new \App\Report('invoice.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //списываем бонусы
        if ($this->headerdata['paydisc'] > 0) {
            $customer = \App\Entity\Customer::load($this->customer_id);
            if ($customer->discount > 0) {
                return; //процент
            } else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0 );
                $customer->save();
            }
        }
        $this->payed = 0;
        if ($this->headerdata['payment'] > 0 && $this->headerdata['payed']) {
            \App\Entity\Pay::addPayment($this->document_id, 1, $this->headerdata['payed'], $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME, $this->headerdata['paynotes']);
            $this->payed = $this->headerdata['payed'];
        }
        return true;
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = 'Расходная накладная';

        return $list;
    }

    protected function getNumberTemplate() {
        return 'СФ-000000';
    }

}
