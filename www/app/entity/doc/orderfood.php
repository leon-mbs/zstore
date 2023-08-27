<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;
use App\Helper as H;

/**
 * Класс-сущность  документ  заказ общепит
 *
 */
class OrderFood extends Document
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
                              "price"      => H::fasell($item->price),
                              "amount"     => H::fasell($item->quantity * $item->price)
            );
        }


        $common = \App\System::getOptions('common');

        $firm = H::getFirmData($this->firm_id);
        $deliverydata = "";
        $deliverydata = $this->headerdata["delivery_name"];
        if ($this->headerdata["delivery"] > 1) {
            $deliverydata = $deliverydata . ', ' . $this->headerdata["ship_address"];
        }
        $deliverydata = $deliverydata . ', ' . date("Y-m-d H:i", $this->headerdata["deltime"]);
        //   $delbonus = $this->getBonus(false) ;

        $header = array('date'         => H::fd($this->document_date),
                        "_detail"      => $detail,
                        "firm_name"    => $firm["firm_name"],
                        "shopname"     => $common["shopname"],
                        "isdelivery"   => $this->headerdata["delivery"] > 0,
                        "deliverydata" => $deliverydata,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"]) > 0 ? $this->headerdata["fiscalnumber"] : false,


                        "notes"   => strlen($this->notes) > 0 ? $this->notes : false ,
                        "contact"   => $this->headerdata["contact"],
                        "exchange"        => H::fasell($this->headerdata["exchange"]),
                        "pos_name"        => $this->headerdata["pos_name"],
                        "time"            => H::fdt($this->headerdata["time"]),
                        "document_number" => $this->document_number,
                        "total"           => H::fasell($this->amount),
                        "bonus"           => H::fa($this->headerdata["bonus"] >0) ? H::fa($this->headerdata["bonus"]) : false,
                        "totaldisc"         => H::fasell($this->headerdata["totaldisc"]),
                        "isdisc"          => $this->headerdata["totaldisc"] > 0,
                        "payamount"       => H::fasell($this->payamount)
        );

        $report = new \App\Report('doc/orderfood.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function generatePosReport($ps=false) {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            $name = $item->itemname;

            $detail[] = array(
                "tovar_name" => $name,
                "quantity"   => H::fqty($item->quantity),
                "amount"     => H::fasell($item->quantity * $item->price)
            );
        }
        $i = 1;

        $common = \App\System::getOptions('common');

        $firm = H::getFirmData($this->firm_id, $this->branch_id);
        $addbonus = $this->getBonus() ;
        $delbonus = $this->getBonus(false) ;
        $allbonus = 0 ;
        if($this->customer_id >0) {
            $c=\App\Entity\Customer::load($this->customer_id);
            $allbonus = $c->getBonus();
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "username"        => $this->headerdata['cashier'] ,
                        "firm_name"       => $firm["firm_name"],
                        "shopname"        => strlen($common["shopname"]) > 0 ? $common["shopname"] : false,
                        "address"         => $firm["address"],
                        "phone"           => $firm["phone"],
                        "inn"             => strlen($firm["inn"]) >0 ? $firm["inn"] : false,
                        "tin"             => strlen($firm["tin"]) >0 ? $firm["tin"] : false,
                        "checkslogan"     => $common["checkslogan"],
                        "customer_name"   => strlen($this->customer_name) > 0 ? $this->customer_name : false,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"]) > 0 ? $this->headerdata["fiscalnumber"] : false,
                        "fiscalnumberpos"  => strlen($this->headerdata["fiscalnumberpos"]) > 0 ? $this->headerdata["fiscalnumberpos"] : false,
                        "exchange"        => H::fasell($this->headerdata["exchange"]),
                        "pos_name"        => $this->headerdata["pos_name"],
                        "time"            => H::fdt($this->headerdata["time"]),
                        "document_number" => $this->document_number,
                        "total"           => H::fasell($this->amount),
                        "payed"           => H::fasell($this->headerdata['payed']),
                        "totaldisc"         => H::fasell($this->headerdata["totaldisc"]),
                        "isdisc"          => $this->headerdata["totaldisc"] > 0,
                         "addbonus"           => $addbonus > 0 ? H::fa($addbonus) : false,
                        "delbonus"           => $delbonus > 0 ? H::fa($delbonus) : false,
                        "allbonus"           => $allbonus > 0 ? H::fa($allbonus) : false,
                       "docqrcode"       => $this->getQRCodeImage(),
                       "docqrcodeurl"     =>  $this->getQRCodeImage(true),

                        "payamount" => H::fasell($this->payamount)
        );

        if($header['inn'] != false) {
            $header['tin'] = false;
        }

        $frases = explode(PHP_EOL, $header['checkslogan']) ;
        if(count($frases) >0) {
            $i=  rand(0, count($frases) -1)  ;
            $header['checkslogan']   =   $frases[$i];
        }


        if($ps) {
            $report = new \App\Report('doc/orderfood_bill_ps.tpl');
        } else {
            $report = new \App\Report('doc/orderfood_bill.tpl');
        }

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {


        return true;
    }

    protected function getNumberTemplate() {
        return 'ЗК-000000';
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_POS);
    }


    public function DoPayment() {
        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {



            $payed = $this->payed;
            if ($this->headerdata['exchange'] > 0 && $this->payed > $this->headerdata['exchange']) {

                $payed = $this->payed - $this->headerdata['exchange']; //без здачи
            }


            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $payed, $this->headerdata['payment']);
            if ($payed > 0) {
                $this->payed = $payed;
            }

            \App\Entity\IOState::addIOState($this->document_id, $this->payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);


        }
    }

    public function DoStore() {

        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from entrylist where document_id =" . $this->document_id);
        $conn->Execute("delete from iostate where document_id=" . $this->document_id);


        foreach ($this->unpackDetails('detaildata') as $item) {

            $onstore = H::fqty($item->getQuantity($this->headerdata['store'])) ;
            $required = $item->quantity - $onstore;


            //оприходуем  с  производства
            if ($required >0 && $item->autoincome == 1 && ($item->item_type == Item::TYPE_PROD || $item->item_type == Item::TYPE_HALFPROD)) {

                if ($item->autooutcome == 1) {    //комплекты
                    $set = \App\Entity\ItemSet::find("pitem_id=" . $item->item_id);
                    foreach ($set as $part) {
                        $lost = 0;
                        $itemp = \App\Entity\Item::load($part->item_id);
                        if($itemp == null) {
                            continue;
                        }
                        $itemp->quantity = $required * $part->qty;
                        if ($itemp->checkMinus($itemp->quantity, $this->headerdata['store']) == false) {
                            throw new \Exception("На складі всього ".H::fqty($itemp->getQuantity($this->headerdata['store']))." ТМЦ {$itemp->itemname}. Списання у мінус заборонено");

                        }

                        //учитываем  отходы
                        if ($itemp->lost > 0) {
                            $k = 1 / (1 - $itemp->lost / 100);
                            $itemp->quantity = $itemp->quantity * $k;
                            $lost = $k - 1;


                        }


                        $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $itemp);

                        foreach ($listst as $st) {
                            $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                            $sc->setStock($st->stock_id);
                            $sc->tag=Entry::TAG_TOPROD;

                            $sc->save();
                            if ($lost > 0) {
                                $io = new \App\Entity\IOState();
                                $io->document_id = $this->document_id;
                                $io->amount = 0 - $st->quantity * $st->partion * $lost;
                                $io->iotype = \App\Entity\IOState::TYPE_TRASH;

                                $io->save();

                            }

                        }
                    }
                }


                $price = $item->getProdprice();

                if ($price == 0) {
                    throw new \Exception('Не розраховано собівартість готової продукції '. $item->itemname);
                }
                $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $price, $item->snumber, $item->sdate, true);

                $sc = new Entry($this->document_id, $required * $price, $required);
                $sc->setStock($stock->stock_id);
                $sc->tag=Entry::TAG_FROMPROD;

                $sc->save();
            }


            if ($item->checkMinus($item->quantity, $this->headerdata['store']) == false) {
                throw new \Exception("На складі всього ".H::fqty($item->getQuantity($this->headerdata['store']))." ТМЦ {$item->itemname}. Списання у мінус заборонено");
            }

            $k = 1;   //учитываем  скидку

            $dd =   doubleval($this->headerdata['bonus']) + doubleval($this->headerdata['totaldisc']) ;

            if ($dd > 0 && $this->amount > 0) {
                $k =   ($this->amount - $dd)/ $this->amount;
            }


            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                //   $sc->setExtCode($item->price * $k - $st->partion); //Для АВС
                $sc->setOutPrice($item->price * $k);
                $sc->tag=Entry::TAG_SELL;

                $sc->save();
            }
        }
    }

    //есть  ли  невыданные  блюда
    public function inProcess() {
        foreach ($this->unpackDetails('detaildata') as $item) {
            $fs = intval($item->foodstate);
            if($fs == 1 || $fs==2) {
                return true;
            }

        }

        return false;
    }

}
