<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ расходная  накладная
 *
 */
class GoodsIssue extends Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();
        $weight = 0;
        foreach ($this->unpackDetails('detaildata') as $item) {


            $name = $item->itemname;
            if (strlen($item->snumber) > 0) {
                $s = ' (' . $item->snumber . ' )';
                if (strlen($item->sdate) > 0) {
                    $s = ' (' . $item->snumber . ',' . date('d.m.Y', $item->sdate) . ')';
                }
                $name .= $s;

            }
            if ($item->weight > 0) {
                $weight += $item->weight;
            }

            $detail[] = array("no" => $i++,
                "tovar_name" => $name,
                "tovar_code" => $item->item_code,
                "quantity" => H::fqty($item->quantity),
                "msr" => $item->msr,
                "price" => H::fa($item->price),
                "amount" => H::fa($item->quantity * $item->price)
            );
        }

        $totalstr = H::sumstr($this->amount);


        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "firmname" => $this->headerdata["firmname"],
            "customer_name" => $this->customer_name,
            "weight" => $weight > 0 ? H::l("allweight", $weight) : '',
            "ship_address" => $this->headerdata["ship_address"],
            "ship_number" => $this->headerdata["ship_number"],
            "order" => strlen($this->headerdata["order"]) > 0 ? $this->headerdata["order"] : false,
            "emp_name" => $this->headerdata["emp_name"],
            "document_number" => $this->document_number,
            "totalstr" => $totalstr,
            "total" => H::fa($this->amount),
            "payed" => H::fa($this->payed),
            "paydisc" => H::fa($this->headerdata["paydisc"]),
            "isdisc" => $this->headerdata["paydisc"] > 0,
            "prepaid" => $this->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID,
            "payamount" => H::fa($this->payamount)
        );
        if ($this->headerdata["sent_date"] > 0) {
            $header['sent_date'] = date('d.m.Y', $this->headerdata["sent_date"]);
        }
        if ($this->headerdata["delivery_date"] > 0) {
            $header['delivery_date'] = date('d.m.Y', $this->headerdata["delivery_date"]);
        }
        $header["isdelivery"] = $this->headerdata["delivery"] > 1;

        $report = new \App\Report('doc/goodsissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //$conn = \ZDB\DB::getConnect();


        foreach ($this->unpackDetails('detaildata') as $item) {
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $item->price, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->setExtCode($item->price - $st->partion); //Для АВС 
                $sc->save();
            }
        }

        //списываем бонусы
        if ($this->headerdata['paydisc'] > 0 && $this->customer_id > 0) {
            $customer = \App\Entity\Customer::load($this->customer_id);
            if ($customer->discount > 0) {
                return; //процент
            } else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0);
                $customer->save();
            }
        }


        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_INCOME);
        }

        return true;
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = 'Гарантийный талон';
        $list['ReturnIssue'] = 'Возвратная накладная';
        $list['GoodsIssue'] = 'Расходная накладная';

        return $list;
    }

    protected function getNumberTemplate() {
        return 'РН-000000';
    }

}
