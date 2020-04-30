<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ  кассовый ек
 *
 */
class POSCheck extends Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {


            $name = $item->itemname;
            if (strlen($item->snumber) > 0) {
                $name .= ' (' . $item->snumber . ',' . date('d.m.Y', $item->sdate) . ')';
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
        foreach ($this->unpackDetails('services') as $ser) {
            $detail[] = array("no" => $i++,
                "tovar_name" => $ser->service_name,
                "tovar_code" => '',
                "quantity" => H::fqty($ser->quantity),
                "msr" => '',
                "price" => H::fa($ser->price),
                "amount" => H::fa($ser->quantity * $ser->price)
            );
        }


        $firm = H::getFirmData($this->branch_id);

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "firmname" => $firm["firmname"],
            "shopname" => $firm["shopname"],
            "address" => $firm["address"],
            "phone" => $firm["phone"],
            "customer_name" => strlen($this->customer_name) > 0 ? $this->customer_name : false,
            "exchange" => $this->headerdata["exchange"],
            "pos_name" => $this->headerdata["pos_name"],
            "time" => date('d.m.Y H:i', $this->headerdata["time"]),
            "document_number" => $this->document_number,
            "total" => H::fa($this->amount),
            "payed" => H::fa($this->payed),
            "paydisc" => H::fa($this->headerdata["paydisc"]),
            "isdisc" => $this->headerdata["paydisc"] > 0,
            "prepaid" => $this->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID,
            "payamount" => H::fa($this->payamount)
        );


        $report = new \App\Report('doc/poscheck.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function generatePosReport() {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            $name = $item->itemname;

            $detail[] = array(
                "tovar_name" => $name,
                "quantity" => H::fqty($item->quantity),
                "amount" => H::fa($item->quantity * $item->price)
            );
        }
        $i=1;
        foreach ($this->unpackDetails('services') as $ser) {
            $detail[] = array("no" => $i++,
                "tovar_name" => $ser->service_name,
                "quantity" => H::fqty($ser->quantity),
                "amount" => H::fa($ser->quantity * $ser->price)
            );
        }

        $firm = H::getFirmData($this->branch_id);

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "firmname" => $firm["firmname"],
            "shopname" => strlen($firm["shopname"]) > 0 ? $firm["shopname"] : false,
            "address" => $firm["address"],
            "phone" => $firm["phone"],
            "inn" => $firm["inn"],
            "customer_name" => strlen($this->headerdata["customer_name"]) > 0 ? $this->headerdata["customer_name"] : false,
            "exchange" => $this->headerdata["exchange"],
            "pos_name" => $this->headerdata["pos_name"],
            "time" => date('d.m.Y H:i', $this->headerdata["time"]),
            "document_number" => $this->document_number,
            "total" => H::fa($this->amount),
            "payed" => H::fa($this->payed),
            "paydisc" => H::fa($this->headerdata["paydisc"]),
            "isdisc" => $this->headerdata["paydisc"] > 0,
            "prepaid" => $this->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID,
            "payamount" => H::fa($this->payamount)
        );


        $report = new \App\Report('doc/poscheck_bill.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //$conn = \ZDB\DB::getConnect();


        foreach ($this->unpackDetails('detaildata') as $item) {

            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->setExtCode($item->price - $st->partion); //Для АВС 
                $sc->save();
            }
        }

        //списываем бонусы
        if ($this->headerdata['paydisc'] > 0 && $this->customer_id > 0) {
            $customer = \App\Entity\Customer::load($this->customer_id);
            if ($customer->discount > 0) {
                //процент
            } else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0);
                $customer->save();
            }
        }
        if ($this->headerdata['exchange'] > 0 && $this->payed > $this->headerdata['exchange']) {

            //  $this->payed = $this->payed - $this->headerdata['exchange']; //без здачи
        }
        foreach ($this->unpackDetails('services') as $ser) {

            $sc = new Entry($this->document_id, 0 - ($ser->price * $ser->quantity), 0);
            $sc->setService($ser->service_id);
            $sc->setExtCode(0 - ($ser->price * $ser->quantity)); //Для АВС 

            $sc->save();
        }
        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_INCOME);
        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'К-000000';
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_POS);
    }

}
