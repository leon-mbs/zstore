<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;
use App\Helper as H;
use App\System;

/**
 * Класс-сущность  документ торгово-транспортная  накладная
 *
 */
class TTN extends Document
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
                    $s = ' (' . $item->snumber . ',' . H::fd($item->sdate) . ')';
                }
                $name .= $s;
            }
            if ($item->weight > 0) {
                $weight += $item->weight;
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


        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $printer = System::getOptions('printer');



        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm['firm_name'],
                        "customer_name"   => $this->customer_id ? $this->customer_name : $this->headerdata["customer_name"],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "store_name"      => $this->headerdata["store_name"],

                        "weight"          => $weight > 0 ? "Загальна вага {$weight} кг" : '',
                        "ship_address"    => strlen($this->headerdata["ship_address"]) > 0 ? $this->headerdata["ship_address"] : false,
                        "ship_number"     => strlen($this->headerdata["ship_number"]) > 0 ? $this->headerdata["ship_number"] : false,
                        "delivery_name"   => $this->headerdata["delivery_name"],
                        "order"           => strlen($this->headerdata["order"]) > 0 ? $this->headerdata["order"] : false,
                        "ship_amount"     => strlen($this->headerdata["ship_amount"]) > 0 ? H::fa($this->headerdata["ship_amount"]) : false,
                        "notes"           => nl2br($this->notes),
                        "emp_name"        => $this->headerdata["emp_name"],
                        "document_number" => $this->document_number,
                        "phone"           => $this->headerdata["phone"],
                        "email"           => $this->headerdata["email"],
                        "total"           => H::fa($this->amount),
        );

        if ($this->headerdata["sent_date"] > 0) {
            $header['sent_date'] = H::fd($this->headerdata["sent_date"]);
        }
        if ($this->headerdata["delivery_date"] > 0) {
            $header['delivery_date'] = H::fd($this->headerdata["delivery_date"]);
        }
        $header['outnumber'] = strlen($this->headerdata['outnumber']) > 0 ? $this->headerdata['outnumber'] : false;

        $report = new \App\Report('doc/ttn.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function generatePosReport($ps=false) {


        $i = 1;
        $detail = array();
        $weight = 0;

        foreach ($this->unpackDetails('detaildata') as $item) {


            $name = $item->itemname;
            if (strlen($item->snumber) > 0) {
                $s = ' (' . $item->snumber . ' )';
                if (strlen($item->sdate) > 0) {
                    $s = ' (' . $item->snumber . ',' . H::fd($item->sdate) . ')';
                }
                $name .= $s;
            }
            if ($item->weight > 0) {
                $weight += $item->weight;
            }

            $detail[] = array("no"         => $i++,
                              "tovar_name" => $name,
                              "tovar_code" => $item->item_code,
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
                        "firm_name"       => $firm['firm_name'],
                        "style"           => $style,
                        "customer_name"   => $this->customer_id ? $this->customer_name : $this->headerdata["customer_name"],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "ship_number"     => strlen($this->headerdata["ship_number"]) > 0 ? $this->headerdata["ship_number"] : false,
                        "order"           => strlen($this->headerdata["order"]) > 0 ? $this->headerdata["order"] : false,
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
        );

        if ($this->headerdata["sent_date"] > 0) {
            $header['sent_date'] = H::fd($this->headerdata["sent_date"]);
        }


        $report = new \App\Report('doc/ttn_bill.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //$conn = \ZDB\DB::getConnect();


        if ($this->headerdata['nostore'] == 1) {
            return;
        }


        if ($this->parent_id > 0) {
            $parent = Document::load($this->parent_id);
            if ($parent->meta_name == 'GoodsIssue' || $parent->meta_name == 'POSCheck') {
                return; //проводки выполняются  в  РН
            }
        }


        foreach ($this->unpackDetails('detaildata') as $item) {
            $onstore = H::fqty($item->getQuantity($this->headerdata['store'])) ;
            $required = $item->quantity - $onstore;


            //оприходуем  с  производства
            if ($required >0 && $item->autoincome == 1 && ($item->item_type == Item::TYPE_PROD) || $item->item_type == Item::TYPE_HALFPROD) {

                if ($item->autooutcome == 1) { //комплекты
                    $set = \App\Entity\ItemSet::find("pitem_id=" . $item->item_id);
                    foreach ($set as $part) {
                        $lost = 0;

                        $itemp = \App\Entity\Item::load($part->item_id);
                        if($itemp == null) {
                            continue;
                        }
                        $itemp->quantity = $required * $part->qty;

                        if (false == $itemp->checkMinus($itemp->quantity, $this->headerdata['store'])) {
                            throw new \Exception("На складі всього ".$itemp->getQuantity($this->headerdata['store']) ." ТМЦ {$itemp->itemname}. Списання у мінус заборонено");

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

            if (false == $item->checkMinus($item->quantity, $this->headerdata['store'])) {
                throw new \Exception("На складі всього ".$item->getQuantity($this->headerdata['store']) ." ТМЦ {$item->itemname}. Списання у мінус заборонено");
            }

            //продажа
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                //  $sc->setExtCode($item->price - $st->partion); //Для АВС
                $sc->setOutPrice($item->price);
                $sc->tag=Entry::TAG_SELL;
                $sc->save();
            }
        }

        return true;
    }

    public function onState($state, $oldstate) {

        if ($state == Document::STATE_INSHIPMENT) {
       
            //расходы на  доставку
            if ($this->headerdata['ship_amount'] > 0 && $this->headerdata['payseller'] == 1) {
                $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $this->headerdata['ship_amount'], H::getDefMF());
                if ($payed > 0) {
                    $this->payed = $payed;
                }
                \App\Entity\IOState::addIOState($this->document_id, 0 - $this->headerdata['ship_amount'], \App\Entity\IOState::TYPE_SALE_OUTCOME);

            }
        }
        $common = \App\System::getOptions("common");

        if ($this->parent_id > 0) {
            $order = Document::load($this->parent_id);

            $list = $order->getChildren('TTN');

            if (count($list) == 1 && $common['numberttn'] <> 1) {   //только  эта  ТТН
                if ($state == Document::STATE_DELIVERED && ($order->state == Document::STATE_INSHIPMENT || $order->state == Document::STATE_READYTOSHIP || $order->state == Document::STATE_INPROCESS)) {
                    $order->updateStatus(Document::STATE_DELIVERED);
                }
                if ($state == Document::STATE_INSHIPMENT && ($order->state == Document::STATE_INPROCESS || $order->state == Document::STATE_READYTOSHIP)) {
                    $order->updateStatus(Document::STATE_INSHIPMENT);
                }
                if ($state == Document::STATE_READYTOSHIP && $order->state == Document::STATE_INPROCESS) {
                    $order->updateStatus(Document::STATE_READYTOSHIP);
                }
            }
        }
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = self::getDesc('Warranty');
        $list['ReturnIssue'] = self::getDesc('ReturnIssue');

        return $list;
    }

    protected function getNumberTemplate() {
        return 'ТТН-000000';
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF);
    }



}
