<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;

use App\Helper as H;
use App\System;

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
                              "disc"       => $item->disc,
                              "price"      => H::fa($item->price),
                              "amount"     => H::fa($item->quantity * $item->price)
            );
        }

        $totalstr =  \App\Util::money2str_ua($this->headerdata["payamount"]);

        $firm = H::getFirmData($this->firm_id, $this->branch_id);
        $mf = \App\Entity\MoneyFund::load($this->headerdata["payment"]);

        $printer = System::getOptions('printer');



        $header = array('date'      => H::fd($this->document_date),
                        "_detail"   => $detail,
                        "firm_name" => $firm['firm_name'],

                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "store_name"      => $this->headerdata["store_name"],
                        "order"           => strlen($this->headerdata["order"]) > 0 ? $this->headerdata["order"] : false,
                        "document_number" => $this->document_number,
                        "totalstr"        => $totalstr,
                        "total"           => H::fa($this->amount),
                       "totaldisc"           => $this->headerdata["totaldisc"] > 0 ? H::fa($this->headerdata["totaldisc"]) : false,
                         "stamp"           => _BASEURL . $firm['stamp'],
                        "isstamp"         => strlen($firm['stamp']) > 0,

                        "bank"            => $mf->bank ?? "",
                        "bankacc"         => $mf->bankacc ?? "",
                        "isbank"          => (strlen($mf->bankacc) > 0 && strlen($mf->bank) > 0),
                        "notes"           => nl2br($this->notes),

                        "iban"      => strlen($firm['iban']) > 0 ? $firm['iban'] : false,
                        "payed"      => $this->headerdata["payed"] > 0 ? H::fa($this->headerdata["payed"]) : false,
                        "payamount"  => $this->headerdata["payamount"] > 0 ? H::fa($this->headerdata["payamount"]) : false

        );

        $header["customer_name"] = $this->headerdata["customer_name"];
        $header["phone"] = false;
        $header["address"] = false;
        $header["edrpou"] = false;
        $header["fedrpou"] = false;
        $header["finn"] = false;
        $header['isprep'] = ($this->headerdata["prepaid"] ??0 )> 0;
        $header['prepaid'] = H::fa($this->headerdata["prepaid"]??'');

        if ($this->customer_id > 0) {
            $cust = \App\Entity\Customer::load($this->customer_id);
            $header["customer_name"] = $cust->customer_name;
            if (strlen($cust->phone) > 0) {
                $header["phone"] = $cust->phone;
            }
            if (strlen($cust->address) > 0) {
                $header["address"] = $cust->address;
            }
            if (strlen($cust->edrpou) > 0) {
                $header["edrpou"] = $cust->edrpou;
            }


        }
        if (strlen($firm['tin']) > 0) {
            $header["fedrpou"] = $firm['tin'];
        }
        if (strlen($firm['inn']) > 0) {
            $header["finn"] = $firm['inn'];
        }


        if (strlen($this->headerdata["customer_name"]) == 0) {
            $header["customer_name"] = false;
        }


        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }



        $report = new \App\Report('doc/goodsissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {




        $parts = array();
      //  $dd =   doubleval($this->headerdata['bonus']) +  doubleval($this->headerdata['totaldisc'])   ;
        $dd =    doubleval($this->headerdata['totaldisc'])   ;
        $k = 1;   //учитываем  скидку
        if ($dd > 0 && $this->amount > 0) {
            $k = ($this->amount - $dd) / $this->amount;
        }

        $amount = 0;
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

                        if (false == $itemp->checkMinus($itemp->quantity, $this->headerdata['store'])) {
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

            if (false == $item->checkMinus($item->quantity, $this->headerdata['store'])) {
                throw new \Exception("На складі всього ".H::fqty($item->getQuantity($this->headerdata['store']))." ТМЦ {$item->itemname}. Списання у мінус заборонено");

            }

            //продажа
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item);

            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                //   $sc->setExtCode($item->price * $k - $st->partion); //Для АВС
                $sc->setOutPrice($item->price * $k);
                $sc->tag=Entry::TAG_SELL;
                $sc->save();
                $amount += $item->price * $k * $st->quantity;
            }
        }



        $this->payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->headerdata['payed'], $this->headerdata['payment']);
 
        \App\Entity\IOState::addIOState($this->document_id, $this->headerdata['payed'], \App\Entity\IOState::TYPE_BASE_INCOME);

        $this->DoBalans() ;


        return true;
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = self::getDesc('Warranty');
        $list['ReturnIssue'] = self::getDesc('ReturnIssue');
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');
        $list['TTN'] = self::getDesc('TTN');
        $list['Invoice'] = self::getDesc('Invoice');

        return $list;
    }

    protected function getNumberTemplate() {
        return 'ВН-000000';
    }

    public function generatePosReport($ps=false) {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {


            $detail[] = array(
                "tovar_name" => $item->itemname,
                "tovar_code" => $item->item_code,

                "quantity"   => H::fqty($item->quantity),
                "price"      => H::fa($item->price),
                "amount"     => H::fa($item->quantity * $item->price)
            );
        }

        $firm = H::getFirmData($this->firm_id, $this->branch_id);
        $mf = \App\Entity\MoneyFund::load($this->headerdata["payment"]);

        $printer = System::getOptions('printer');
        $style = "";
        if (strlen($printer['pdocfontsize']??'') > 0 || strlen($printer['pdocwidth']??'') > 0) {
            $style = 'style="font-size:' . $printer['pdocfontsize'] . 'px;width:' . $printer['pdocwidth'] . ';"';

        }


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm["firm_name"],
                        "style"           => $style,
                        "phone"           => $firm["phone"],
                        "bank"            => $mf->bank ?? "",
                        "bankacc"         => $mf->bankacc ?? "",
                        "isbank"          => (strlen($mf->bankacc) > 0 && strlen($mf->bank) > 0),
                        "customer_name"   => strlen($this->headerdata["customer_name"]) > 0 ? $this->headerdata["customer_name"] : false,
                        "document_number" => $this->document_number,

                        "total"           => H::fa($this->amount)
        );
        if (strlen($this->headerdata["customer_name"]) == 0) {
            $header["customer_name"] = false;
        }


        if($ps) {
            $report = new \App\Report('doc/goodsissue_bill_ps.tpl');
        } else {
            $report = new \App\Report('doc/goodsissue_bill.tpl');
        }

        $html = $report->generate($header);

        return $html;
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_POS, self::EX_PDF);
    }

    protected function onState($state, $oldstate) {
        if($state == Document::STATE_EXECUTED) {

            if($this->parent_id > 0) {
                $order = Document::load($this->parent_id);
                if($order->meta_name == 'Order') {

                    if($order->payamount == 0 || ($order->payamount > 0 && $order->payamount == $order->payed)) {
                        $this->updateStatus(Document::STATE_DELIVERED) ;
                    }
                }
            }


        }


    }
    /**
    * @override
    */
    public function DoBalans() {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from custacc where optype in (2,3) and document_id =" . $this->document_id);
        if(($this->customer_id??0) == 0) {
            return;
        }
  
        //тмц
        if($this->payamount >0) {
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = 0-$this->payamount;
            $b->optype = \App\Entity\CustAcc::BUYER;
            $b->save();
        }
        
       //платежи       
        foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where    paytype < 1000 and coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = $p['amount'];
            $b->createdon = strtotime($p['paydate']);
            $b->optype = \App\Entity\CustAcc::BUYER;
            $b->save();
        }        
    }
    
}
