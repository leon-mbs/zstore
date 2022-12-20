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
                              "price"      => H::fa($item->price),
                              "amount"     => H::fa($item->quantity * $item->price)
            );
        }

        $totalstr =  \App\Util::money2str_ua($this->payamount);

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
                        "paydisc"         => H::fa($this->headerdata["paydisc"]),
                        "isdisc"          => $this->headerdata["paydisc"] > 0,

                        "bank"            => @$mf->bank,
                        "bankacc"         => @$mf->bankacc,
                        "isbank"          => (strlen($mf->bankacc) > 0 && strlen($mf->bank) > 0),
                        "notes"           => nl2br($this->notes),
                       
                        "payed"      => $this->payed > 0 ? H::fa($this->payed) : false,
                        "payamount"  => $this->payamount > 0 ? H::fa($this->payamount) : false

        );

        $header["customer_name"] = $this->headerdata["customer_name"];
        $header["phone"] = false;
        $header["address"] = false;
        $header["edrpou"] = false;
        $header["fedrpou"] = false;
        $header["finn"] = false;
        $header['isprep'] = $this->headerdata["prepaid"] > 0;
        $header['prepaid'] = H::fa($this->headerdata["prepaid"]);

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
        $k = 1;   //учитываем  скидку
        if ($this->headerdata["paydisc"] > 0 && $this->amount > 0) {
            $k = ($this->amount - $this->headerdata["paydisc"]) / $this->amount;
        }
        $amount = 0;
        foreach ($this->unpackDetails('detaildata') as $item) {


            //оприходуем  с  производства
            if ($item->autoincome == 1 && $item->item_type == Item::TYPE_PROD) {

                if ($item->autooutcome == 1) {    //комплекты
                    $set = \App\Entity\ItemSet::find("pitem_id=" . $item->item_id);
                    foreach ($set as $part) {

                        $itemp = \App\Entity\Item::load($part->item_id);
                        if($itemp == null)  continue;
                        $itemp->quantity = $item->quantity * $part->qty;

                        if (false == $itemp->checkMinus($itemp->quantity, $this->headerdata['store'])) {
                            throw new \Exception(H::l("nominus", H::fqty($itemp->getQuantity($this->headerdata['store'])), $itemp->itemname));
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
                    throw new \Exception(H::l('noselfprice', $item->itemname));
                }
                $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $price, $item->snumber, $item->sdate, true);

                $sc = new Entry($this->document_id, $item->quantity * $price, $item->quantity);
                $sc->setStock($stock->stock_id);
                $sc->tag=Entry::TAG_FROMPROD;

                $sc->save();
            }

            if (false == $item->checkMinus($item->quantity, $this->headerdata['store'])) {
                throw new \Exception(H::l("nominus", H::fqty($item->getQuantity($this->headerdata['store'])), $item->itemname));
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


 
        if ($this->headerdata['payment'] > 0 && $this->headerdata['payed'] > 0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->headerdata['payed'], $this->headerdata['payment']);
            if ($payed > 0) {
                $this->payed = $payed;
            }
            \App\Entity\IOState::addIOState($this->document_id, $this->headerdata['payed'], \App\Entity\IOState::TYPE_BASE_INCOME);
 

        }

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

        $printer = System::getOptions('printer');
        $style = "";
        if (strlen($printer['pdocfontsize']) > 0 || strlen($printer['pdocwidth']) > 0) {
            $style = 'style="font-size:' . $printer['pdocfontsize'] . 'px;width:' . $printer['pdocwidth'] . ';"';

        }


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm["firm_name"],
                        "style"           => $style,
                        "phone"           => $firm["phone"],
                        "customer_name"   => strlen($this->headerdata["customer_name"]) > 0 ? $this->headerdata["customer_name"] : false,
                        "document_number" => $this->document_number,
                         
                        "total"           => H::fa($this->amount)
        );
        if (strlen($this->headerdata["customer_name"]) == 0) {
            $header["customer_name"] = false;
        }

   
        if($ps)   {
          $report = new \App\Report('doc/goodsissue_bill_ps.tpl');
        }
        else 
          $report = new \App\Report('doc/goodsissue_bill.tpl');
 
        $html = $report->generate($header);

        return $html;
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_POS, self::EX_PDF);
    }

    protected function onState($state,$oldstate) {
        if($state == Document::STATE_EXECUTED) {
           if($this->hasStore() && $this->payed > 0 && $this->payamount == $this->payed ) { //провеен  и оплачен
             //  $this->updateStatus(Document::STATE_CLOSED) ;
               return;
           }          
               if($this->parent_id > 0)   {;
                   $parent = Document::load($this->parent_id);              
                   if($parent->meta_name == 'Order' || $parent->meta_name == 'Invoice') {   
                     if($parent->state== Document::STATE_PAYED) {   //оплачено
                         //$this->updateStatus(Document::STATE_CLOSED) ;                                  
                     }         
                   }         
               }
              
            
        } 
        if($state == Document::STATE_CLOSED) {
           if($this->parent_id > 0)   {;
               $parent = Document::load($this->parent_id);              
               if($parent->meta_name == 'Order' || $parent->meta_name == 'Invoice') {
                   $parent->updateStatus(Document::STATE_CLOSED) ;                                  
               }         
           }
       }        
              
    }    
    
}
