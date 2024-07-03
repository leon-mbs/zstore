<?php

namespace App\Entity\Doc;

use App\Helper as H;
use App\System;
use App\Entity\Item;
use App\Entity\Entry;

/**
 * Класс-сущность  документ заказ
 *
 */
class Order extends \App\Entity\Doc\Document
{
    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {


                $detail[] = array("no"         => $i++,
                                  "tovar_name" => $item->itemname,
                                  "tovar_code" => $item->item_code,
                                  "quantity"   => H::fqty($item->quantity),
                                  "price"      => H::fa($item->price),
                                  "msr"        => $item->msr,
                                  "desc"       => $item->desc,
                                  "amount"     => H::fa($item->quantity * $item->price)
                );
            }
        }
        $addbonus = $this->getBonus() ;
        $delbonus = $this->getBonus(false) ;
        $allbonus = 0 ;
        if($this->customer_id >0) {
            $c=\App\Entity\Customer::load($this->customer_id);
            $allbonus = $c->getBonus();
        }

        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $da=  trim($this->headerdata["npaddressfull"] ) ;
        
        if(strlen($da)==0) {
           $da=  trim($this->headerdata["ship_address"] ) ;
        }
        
        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "phone"           => $this->headerdata["phone"],
                        "email"           => $this->headerdata["email"],
                        "delivery"        => $this->headerdata["delivery_name"],
                        "ship_address"    => strlen($da) > 0 ? $da: false,
                        "notes"           => nl2br($this->notes),
                        "outnumber"       => $this->headerdata["outnumber"],
                        "isoutnumber"     => strlen($this->headerdata["outnumber"]) > 0,
                        "document_number" => $this->document_number,
                         "iban"      => strlen($firm['iban']) > 0 ? $firm['iban'] : false,
                         "firm_name" => $firm['firm_name'],

                        "isfirm"          => strlen($firm["firm_name"]) > 0,

                        "total"           => H::fa($this->amount),
                        "totaldisc"           => $this->headerdata["totaldisc"] > 0 ? H::fa($this->headerdata["totaldisc"]) : false,
                        "addbonus"        => $addbonus > 0 ? H::fa($addbonus) : false,
                        "delbonus"        => $delbonus > 0 ? H::fa($delbonus) : false,
                        "allbonus"        => $allbonus > 0 ? H::fa($allbonus) : false,
                        "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false
        );
        $header['outnumber'] = strlen($this->headerdata['outnumber']) > 0 ? $this->headerdata['outnumber'] : false;




        $report = new \App\Report('doc/order.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'З-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');
        if($this->payed==0) {
            $list['Invoice'] = self::getDesc('Invoice');
        }
      //  $list['POSCheck'] = self::getDesc('POSCheck');
        $list['Task'] = self::getDesc('Task');
        $list['TTN'] = self::getDesc('TTN');
        $list['Order'] = self::getDesc('Order');
        $list['ProdReceipt'] = self::getDesc('ProdReceipt');


        return $list;
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_POS);
    }

    public function generatePosReport($ps=false) {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {


            $detail[] = array(
                "tovar_name" => $item->itemname,
                "quantity"   => H::fqty($item->quantity),
                "price"      => H::fa($item->price),
                "amount"     => H::fa($item->quantity * $item->price)
            );
        }

        $firm = H::getFirmData($this->firm_id, $this->branch_id);
        $printer = System::getOptions('printer');
        $style = "";
        if (strlen($printer['pdocfontsize']) > 0 || strlen($printer['pdocwidth']) > 0) {
            $style = 'style="font-size:' . $printer['pdocfontsize'] . 'px;width:' . $printer['pdocwidth'] . ';"';

        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm["firm_name"],
                        "phone"           => $firm["phone"],
                        "delivery"        => $this->headerdata["delivery_name"],
                         "customer_name"   => strlen($this->headerdata["customer_name"]) > 0 ? $this->headerdata["customer_name"] : false,
                        "document_number" => $this->document_number,
                        "style"           => $style,
                        "total"           => H::fa($this->amount)
        );
        if($ps) {
            $report = new \App\Report('doc/order_bill_ps.tpl');
        } else {
            $report = new \App\Report('doc/order_bill.tpl');
        }

        $html = $report->generate($header);

        return $html;
    }

    //резеорвирование товаров
    public function reserve() {

        $this->unreserve();

        if(intval($this->headerdata['store'])==0) {
            return;
        }

        $items = $this->unpackDetails('detaildata')  ;

        foreach ($items as $item) {
            $onstore = H::fqty($item->getQuantity($this->headerdata['store'])) ;
            $required = $item->quantity - $onstore;
      
            //оприходуем  с  производства
            if ($required >0 && $item->autoincome == 1 && ($item->item_type == Item::TYPE_PROD || $item->item_type == Item::TYPE_HALFPROD)) {

                if ($item->autooutcome == 1) {    //комплекты
                    $set = \App\Entity\ItemSet::find("pitem_id=" . $item->item_id);
                    foreach ($set as $part) {

                        $itemp = \App\Entity\Item::load($part->item_id);
                        if($itemp == null) {
                            continue;
                        }
                        $itemp->quantity = $required * $part->qty;

                        if (false == $itemp->checkMinus($itemp->quantity, $this->headerdata['store'])) {
                            throw new \Exception("На складі всього ".H::fqty($itemp->getQuantity($this->headerdata['store']))." ТМЦ {$itemp->itemname}. Списання у мінус заборонено");
                        }

                        $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $itemp);

                        foreach ($listst as $st) {
                            $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                            $sc->setStock($st->stock_id);
                            $sc->tag=Entry::TAG_TOPROD;

                            $sc->save();
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
       
        
        
            if (false == $item->checkMinus($item->quantity, $this->headerdata['store'])) {
                throw new \Exception("На складі всього ".H::fqty($item->getQuantity($this->headerdata['store']))." ТМЦ {$item->itemname}. Списання у мінус заборонено");

            }
 

            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new \App\Entity\Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                //  $sc->setOutPrice($item->price  );
                $sc->tag = \App\Entity\Entry::TAG_RESERV;
                $sc->save();

            }
        }

    }
    //отмена  резерва
    public function unreserve() {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from entrylist where document_id =" . $this->document_id);
    }

    protected function onState($state, $oldstate) {


        if ($state < 5 || $state == self::STATE_REFUSED || $state == self::STATE_FAIL || $state == self::STATE_CLOSED) {

            $this->unreserve()  ;

        }
        if ( $state == self::STATE_READYTOSHIP) {

            $this->unreserve()  ;
            $this->reserve()  ;

        }
        if ($state == self::STATE_INPROCESS) {

            if(strlen($this->headerdata['promocode']) > 0){
                \App\Entity\PromoCode::apply($this->headerdata['promocode'],$this);
            };

            if($this->payed >0) {
                $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->payed, $this->headerdata['payment']);
                if ($payed > 0) {
                    $this->payed = $payed;
                }
                \App\Entity\IOState::addIOState($this->document_id, $this->payed, \App\Entity\IOState::TYPE_BASE_INCOME);
            }

        }
    }
    public function DoBalans() {

    }
}
