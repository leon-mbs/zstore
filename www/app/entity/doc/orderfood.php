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
                              "price"      => H::fa($item->price),
                              "amount"     => H::fa($item->quantity * $item->price)
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
           $delbonus = $this->getBonus(false) ;

        $header = array('date'         => H::fd($this->document_date),
                        "_detail"      => $detail,
                        "firm_name"    => $firm["firm_name"],
                        "shopname"     => $common["shopname"],
                        "isdelivery"   => $this->headerdata["delivery"] > 0,
                        "deliverydata" => $deliverydata,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"]) > 0 ? $this->headerdata["fiscalnumber"] : false,

                                                              
                        "notes"   => strlen($this->notes) > 0 ? $this->notes : false ,
                        "contact"   => $this->headerdata["contact"],
                        "exchange"        => H::fa($this->headerdata["exchange"]),
                        "pos_name"        => $this->headerdata["pos_name"],
                        "time"            => H::fdt($this->headerdata["time"]),
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "payed"           => H::fa($this->payed),
                        "paydisc"         => H::fa($this->headerdata["paydisc"]),
                        "delbonus"           => $delbonus > 0 ? H::fa($delbonus) : false,
                        "isdisc"          => $this->headerdata["paydisc"] > 0,
                        "payamount"       => H::fa($this->payamount)
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
                "amount"     => H::fa($item->quantity * $item->price)
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
                        "username"        => \App\System::getUser()->username,
                        "firm_name"       => $firm["firm_name"],
                        "shopname"        => strlen($common["shopname"]) > 0 ? $common["shopname"] : false,
                        "address"         => $firm["address"],
                        "phone"           => $firm["phone"],
                        "inn"             => $firm["inn"],
                        "checkslogan"     => $common["checkslogan"],
                        "customer_name"   => strlen($this->customer_name) > 0 ? $this->customer_name : false,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"]) > 0 ? $this->headerdata["fiscalnumber"] : false,
                        "fiscalnumberpos"  => strlen($this->headerdata["fiscalnumberpos"]) > 0 ? $this->headerdata["fiscalnumberpos"] : false,
                        "exchange"        => H::fa($this->headerdata["exchange"]),
                        "pos_name"        => $this->headerdata["pos_name"],
                        "time"            => H::fdt($this->headerdata["time"]),
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "payed"           => H::fa($this->payed),
                        "paydisc"         => H::fa($this->headerdata["paydisc"]),
                        "isdisc"          => $this->headerdata["paydisc"] > 0,
                         "addbonus"           => $addbonus > 0 ? H::fa($addbonus) : false,
                        "delbonus"           => $delbonus > 0 ? H::fa($delbonus) : false,
                        "allbonus"           => $allbonus > 0 ? H::fa($allbonus) : false,
                       "docqrcode"       => $this->getQRCodeImage(),
                       "docqrcodeurl"     =>  $this->getQRCodeImage(true),

                        "payamount" => H::fa($this->payamount)
        );
        $frases = explode(PHP_EOL, $header['checkslogan']) ;
        if(count($frases) >0)  {
            $i=  rand(0,count($frases) -1)  ;
            $header['checkslogan']   =   $frases[$i];        
        }
  

        if($ps)   {
          $report = new \App\Report('doc/orderfood_bill_ps.tpl');
        }
        else 
          $report = new \App\Report('doc/orderfood_bill.tpl');

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


            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $payed, $this->headerdata['payment'] );
            if ($payed > 0) {
                $this->payed = $payed;
            }

            \App\Entity\IOState::addIOState($this->document_id, 0 - $this->payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);


        }
    }

    public function DoStore() {
        if($this->hasStore()) return;
        
        foreach ($this->unpackDetails('detaildata') as $item) {


            //оприходуем  с  производства
            if ($item->autoincome == 1 && $item->item_type == Item::TYPE_PROD) {

                if ($item->autooutcome == 1) {    //комплекты
                    $set = \App\Entity\ItemSet::find("pitem_id=" . $item->item_id);
                    foreach ($set as $part) {

                        $itemp = \App\Entity\Item::load($part->item_id);
                        if($itemp == null)  continue;
                        $itemp->quantity = $item->quantity * $part->qty;
                        if ($itemp->checkMinus($itemp->quantity, $this->headerdata['store']) == false) {
                            throw new \Exception(\App\Helper::l("nominus", H::fqty($itemp->getQuantity($this->headerdata['store'])), $itemp->itemname));
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
                    throw new \Exception(H::l('noselfprice', $item->itemname));
                }
                $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $price, $item->snumber, $item->sdate, true);

                $sc = new Entry($this->document_id, $item->quantity * $price, $item->quantity);
                $sc->setStock($stock->stock_id);
                $sc->tag=Entry::TAG_FROMPROD;

                $sc->save();
            }


            if ($item->checkMinus($item->quantity, $this->headerdata['store']) == false) {
                throw new \Exception(\App\Helper::l("nominus", H::fqty($item->getQuantity($this->headerdata['store'])), $item->itemname));
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
                 $sc->tag=Entry::TAG_SELL;
                
                $sc->save();
            }
        }
    }


}
