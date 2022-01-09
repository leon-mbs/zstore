<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;
use App\Helper as H;
use App\System as System;

/**
 * Класс-сущность  документ  кассовый чек
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
        foreach ($this->unpackDetails('services') as $ser) {
            $detail[] = array("no"         => $i++,
                              "tovar_name" => $ser->service_name,
                              "tovar_code" => '',
                              "quantity"   => H::fqty($ser->quantity),
                              "msr"        => '',
                              "price"      => H::fa($ser->price),
                              "amount"     => H::fa($ser->quantity * $ser->price)
            );
        }

        $common = System::getOptions('common');

        $firm = H::getFirmData($this->firm_id);
        $printer = System::getOptions('printer');

        $style = "";
        if (strlen($printer['pa4width']) > 0) {
            $style = 'style=" width:' . $printer['pa4width'] . ';"';

        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm["firm_name"],
                        "style"           => $style,
                        "shopname"        => $common["shopname"],
                        "address"         => $firm["address"],
                        "phone"           => $firm["phone"],
                        "inn"             => $firm["inn"],
                        "customer_name"   => strlen($this->customer_name) > 0 ? $this->customer_name : false,
                        "exchange"        => H::fa($this->headerdata["exchange"]),
                        "pos_name"        => $this->headerdata["pos_name"],
                        "time"            => H::fdt($this->headerdata["time"]),
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "payed"           => H::fa($this->headerdata['payed']),
                        "paydisc"         => H::fa($this->headerdata["paydisc"]),
                        "isdisc"          => $this->headerdata["paydisc"] > 0,
                        "prepaid"         => $this->headerdata['payment'] == 0,
            
                        "docqrcode"       => $this->getQRCodeImage(),
                        "payamount"       => H::fa($this->payamount)
        );
        if($this->headerdata['payment']  >0){
            $mf = \App\Entity\MoneyFund::load($this->headerdata['payment'] );
            $header['nal']  = $mf->beznal!=1;
        }
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
                "quantity"   => H::fqty($item->quantity),
                "amount"     => H::fa($item->quantity * $item->price)
            );
        }
        $i = 1;
        foreach ($this->unpackDetails('services') as $ser) {
            $detail[] = array("no"         => $i++,
                              "tovar_name" => $ser->service_name,
                              "quantity"   => H::fqty($ser->quantity),
                              "amount"     => H::fa($ser->quantity * $ser->price)
            );
        }
        $common = System::getOptions('common');
        $printer = System::getOptions('printer');
        $style = "";
        if (strlen($printer['pdocfontsize']) > 0 || strlen($printer['pdocwidth']) > 0) {
            $style = 'style="font-size:' . $printer['pdocfontsize'] . 'px;width:' . $printer['pdocwidth'] . ';"';

        }

        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $header = array('date'          => H::fd($this->document_date),
                        "_detail"       => $detail,
                        "style"         => $style,
                        "username"      => System::getUser()->username,
                        "firm_name"     => $firm["firm_name"],
                        "shopname"      => strlen($common["shopname"]) > 0 ? $common["shopname"] : false,
                        "address"       => $firm["address"],
                        "phone"         => $firm["phone"],
                        "inn"           => strlen($firm["inn"]) >0 ? $firm["inn"] :$firm["tin"],
                        "checkslogan"   => $common["checkslogan"],
                        "customer_name" => strlen($this->headerdata["customer_name"]) > 0 ? $this->headerdata["customer_name"] : false,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"]) > 0 ? $this->headerdata["fiscalnumber"] : false,
                        "fiscalnumberpos"  => strlen($this->headerdata["fiscalnumberpos"]) > 0 ? $this->headerdata["fiscalnumberpos"] : false,

                        "pos_name"        => $this->headerdata["pos_name"],
                        "time"            => H::fdt($this->headerdata["time"]),
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "paydisc"         => H::fa($this->headerdata["paydisc"]),
                        "isdisc"          => $this->headerdata["paydisc"] > 0,
                        "exchange"        => $this->headerdata["exchange"] > 0 ? H::fa($this->headerdata["exchange"]) : false,
                        
                        "docqrcode"       => $this->getQRCodeImage(),
                        "payed"           => $this->headerdata['payed'] > 0 ? H::fa($this->headerdata['payed']) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false
        );

        $report = new \App\Report('doc/poscheck_bill.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //$conn = \ZDB\DB::getConnect();


        foreach ($this->unpackDetails('detaildata') as $item) {


            //оприходуем  с  производства
            if ($item->autoincome == 1 && $item->item_type == Item::TYPE_PROD) {

                if ($item->autooutcome == 1) {    //комплекты
                    $set = \App\Entity\ItemSet::find("pitem_id=" . $item->item_id);
                    foreach ($set as $part) {

                        $itemp = \App\Entity\Item::load($part->item_id);
                        $itemp->quantity = $item->quantity * $part->qty;
                        $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $itemp);

                        foreach ($listst as $st) {
                            $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                            $sc->setStock($st->stock_id);

                            $sc->save();
                        }
                    }
                }


                $price = $item->getProdprice();

                if ($price == 0) {
                    throw new \Exception(H::l('noselfprice', $item->itemname));
                }
                $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $price, $item->snumber, $item->sdate, true);

                $sc = new Entry($this->document_id, $item->quantity * $price, $item->quantity);
                $sc->setStock($stock->stock_id);

                $sc->save();
            }

            if (false == $item->checkMinus($item->quantity, $this->headerdata['store'])) {
                throw new \Exception(\App\Helper::l("nominus", $item->quantity, $item->itemname));
            }


            $k = 1;   //учитываем  скидку
            if ($this->headerdata["paydisc"] > 0 && $this->amount > 0) {
                $k = ($this->amount - $this->headerdata["paydisc"]) / $this->amount;
            }

            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                //   $sc->setExtCode($item->price * $k - $st->partion); //Для АВС
                $sc->setOutPrice($item->price * $k);
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
                if ($customer->bonus < 0) {
                    $customer->bonus = 0;
                }
                $customer->save();
            }
        }
        $payed = $this->payed;
        if ($this->headerdata['exchange'] > 0 && $this->payed > $this->headerdata['exchange']) {

            $payed = $this->payed - $this->headerdata['exchange']; //без здачи
        }
        foreach ($this->unpackDetails('services') as $ser) {

            $sc = new Entry($this->document_id, 0 - ($ser->price * $k * $ser->quantity), 0);
            $sc->setService($ser->service_id);
            // $sc->setExtCode(0 - ($ser->price * $k)); //Для АВС
            $sc->setOutPrice(0 - $item->price * $k);

            $sc->save();
        }
        if ($this->headerdata['payment'] > 0 && $payed > 0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $payed, $this->headerdata['payment'], \App\Entity\IOState::TYPE_BASE_INCOME);
            if ($payed > 0) {
                $this->payed = $payed;
            }
            \App\Entity\IOState::addIOState($this->document_id, $payed, \App\Entity\IOState::TYPE_BASE_INCOME);

        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'K-000000';
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_POS);
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = self::getDesc('Warranty');
        $list['ReturnIssue'] = self::getDesc('ReturnIssue');

        return $list;
    }


}
