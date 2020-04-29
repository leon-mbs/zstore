<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ счет фактура
 *
 */
class Invoice extends \App\Entity\Doc\Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {
                $detail[] = array("no" => $i++,
                    "tovar_name" => $item->itemname,
                    "tovar_code" => $item->item_code,
                    "quantity" => H::fqty($item->quantity),
                    "price" => H::fa($item->price),
                    "msr" => $item->msr,
                    "amount" => H::fa($item->quantity * $item->price)
                );
            }
        }

        $totalstr = H::sumstr($this->amount);

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->customer_name,
            "phone" => $this->headerdata["phone"],
            "email" => $this->headerdata["email"],
            "notes" => $this->notes,
            "document_number" => $this->document_number,
            "totalstr" => $totalstr,
            "total" => $this->amount,
            "payamount" => H::fa($this->payamount),
            "payed" => H::fa($this->payed),
            "paydisc" => H::fa($this->headerdata["paydisc"])
        );


        $report = new \App\Report('doc/invoice.tpl');

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
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0);
                $customer->save();
            }
        }

        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME);
        }
        return true;
    }

    protected function getNumberTemplate() {
        return 'СФ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = 'Расходная накладная';
        $list['Invoice'] = 'Кассовый чек';

        return $list;
    }

}
