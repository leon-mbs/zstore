<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\System;
use \App\Util;

/**
 * Класс-сущность  документ  кассовый ек
 *
 */
class POSCheck extends Document {

    public function generateCheck() {

        if ($this->headerdata['exchange'] > 0)
            $this->payed += $this->headerdata['exchange']; //учитываем  со  здачей


        $check = array();
        $check[] .= $this->f30("Чек " . $this->document_number);
        $check[] .= $this->f30("вiд " . date('Y-m-d H:i:s', $this->headerdata['time']));
        $check[] .= $this->f30($this->headerdata["firmname"]);
        $check[] .= $this->f30("IПН " . $this->headerdata["inn"]);
        //  $check[] .=  $this->f30("Тел.  ". $firm['phone']);
        foreach (explode(',', $this->headerdata["phone"]) as $p) {
            $check[] .= $this->f30("Тел.  " . $p);
        }
        $check[] .= str_repeat('-', 30);
        $a = "Адрес " . $this->headerdata["address"];
        foreach (Util::mb_split($a, 30) as $p) {
            $check[] .= $this->f30($p);
        }
        $check[] .= str_repeat('-', 30);
        foreach ($this->detaildata as $value) {

            $t = $value['item_code'] . ' ' . $value['itemname'];
            foreach (Util::mb_split($t, 30) as $p) {
                $check[] .= $this->f30($p);
            }
            $q = '' . H::fqty($value['quantity']) . $value['msr'] . ' по ' . H::fa($value['price']);
            $check[] .= sprintf("%s%10s", $q . str_repeat(' ', 20 - mb_strlen($q)), H::fa($value['quantity'] * $value['price']));
        }

        $check[] .= str_repeat('-', 30);
        $check[] .= sprintf("%s%10s", 'Всього' . str_repeat(' ', 14), H::fa($this->amount));
        if ($this->headerdata["paydisc"] > 0) {
            $check[] .= sprintf("%s%10s", 'Знижка' . str_repeat(' ', 14), H::fa($this->headerdata["paydisc"]));
        }
        $check[] .= sprintf("%s%10s", 'До сплати' . str_repeat(' ', 11), H::fa($this->payamount));
        $check[] .= sprintf("%s%10s", 'Внесена оплата' . str_repeat(' ', 6), H::fa($this->payed));
        $check[] .= sprintf("%s%10s", 'Здача' . str_repeat(' ', 15), H::fa($this->headerdata["exchange"]));

        $check[] .= $this->f30("Дякуємо за довiру до нас!");


        return $check;
    }

    private function f30($s) {
        return $s . str_repeat(' ', 30 - mb_strlen($s));
    }


    public function generateReport() {

        $print = implode("<br>", $this->generateCheck());
        $print = str_replace(' ', '&nbsp;', $print);

        $header = array('print' => $print);

        $report = new \App\Report('poscheck.tpl');

        $html = $report->generate($header);

        return $html;
    }
    
    public function generatePosReport() {
        return $this->generateReport();   
    }   

    public function Execute() {
        //$conn = \ZDB\DB::getConnect();


        foreach ($this->detaildata as $item) {
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item['item_id'], $item['quantity'], $item['snumber']);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->setExtCode($item['price'] - $st->partion); //Для АВС 
                $sc->save();
            }
        }

        //списываем бонусы
        if ($this->headerdata['paydisc'] > 0 && $this->customer_id > 0) {
            $customer = \App\Entity\Customer::load($this->customer_id);
            if ($customer->discount > 0) {
                return; //процент
            } else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0 );
                $customer->save();
            }
        }
        if ($this->headerdata['exchange'] > 0 && $this->payed > $this->headerdata['exchange']) {

            $this->payed = $this->payed - $this->headerdata['exchange']; //без здачи
        }

        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            \App\Entity\Pay::addPayment($this->document_id, $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME);
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
