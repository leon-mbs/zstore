<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Stock;
use App\Helper as H;

/**
 * Класс-сущность  документ оприходование товаров
 *
 */
class IncomeItem extends Document
{
    public function Execute() {


        $conn = \ZDB\DB::getConnect();
        $amount = 0;
        foreach ($this->unpackDetails('detaildata') as $item) {

            $stockto = Stock::getStock($this->headerdata['store'], $item->item_id, $item->price, $item->snumber, $item->sdate, true);
            $sc = new Entry($this->document_id, $item->quantity * $item->price, $item->quantity);
            $sc->setStock($stockto->stock_id);
            $sc->save();
            $amount = $amount + $item->quantity * $item->price;



        }
        if ($this->headerdata['mtype'] > 0) {
            $io = new \App\Entity\IOState();
            $io->document_id = $this->document_id;
            $io->amount = $amount;
            $io->iotype = $this->headerdata['mtype'];

            $io->save();
        }
        
        
        if ($this->headerdata['examount'] > 0) {
         
            $this->payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->headerdata['examount'], $this->headerdata['exmf'], $this->notes);
         
            \App\Entity\IOState::addIOState($this->document_id, $this->payed, \App\Entity\IOState::TYPE_BASE_INCOME);

        }


        if ($this->headerdata['emp'] > 0) {
            //авансовый    отчет
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::OUTCOME_TO_MF;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $this->headerdata["emp"];
            $ua->amount = $amount;
            if ($this->headerdata['examount'] > 0) {
                $ua->amount += $this->headerdata['examount'];
            }
            if ($ua->amount > 0) {
                $ua->save();
            }
        }

        return true;
    }

    public function generateReport() {


        $i = 1;
        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {


            $detail[] = array("no"        => $i++,
                              "item_name" => $item->itemname,
                              "item_code" => $item->item_code,
                              "snumber"   => $item->snumber,
                              "msr"       => $item->msr,
                              "quantity"  => H::fqty($item->quantity),
                              "price"     => H::fa($item->price),
                              "amount"    => H::fa($item->quantity * $item->price)
            );
        }

        $header = array(
            "_detail"         => $detail,
            'date'            => H::fd($this->document_date),
            "total"           => H::fa($this->amount),
            "to"              => $this->headerdata["storename"],
            "emp"             => false,
            "notes"           => nl2br($this->notes),
            "document_number" => $this->document_number
        );
        if ($this->headerdata["emp"] > 0 && $this->headerdata['examount']) {
            $header['emp'] = $this->headerdata["empname"];
            $header['examount'] = H::fa($this->headerdata["examount"]);
        }

        $report = new \App\Report('doc/incomeitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОТ-000000';
    }

}
