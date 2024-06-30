<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ Оплата  комитенту
 *
 */
class PayComitent extends Document
{
    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {
                $name = $item->itemname;
                if (strlen($item->snumber) > 0) {
                    $name .= ' (' . $item->snumber . ',' . H::fd($item->sdate) . ')';
                }

                $detail[] = array("no"         => $i++,
                                  "tovar_name" => $name,
                                  "tovar_code" => $item->item_code,
                                  "quantity"   => H::fqty($item->quantity),
                                  "msr"        => $item->msr,
                                  "price"      => H::fa($item->price),
                                  "amount"     => H::fa($item->quantity * $item->price)
                );
            }
        }


        $customer = \App\Entity\Customer::load($this->customer_id);

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $this->headerdata["firm_name"],
                        "customer_name"   => $this->customer_name,
                        "notes"           => nl2br($this->notes),
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "payed"           => H::fa($this->payed)
        );

        $report = new \App\Report('doc/paycomitent.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();

    
        $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->payed, $this->headerdata['payment']);
        if ($payed > 0) {
            $this->payed = $payed;
        }
        \App\Entity\IOState::addIOState($this->document_id, 0-$this->payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);




        return true;
    }

    protected function getNumberTemplate() {
        return 'ВК-000000';
    }

}
