<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;
use App\Helper as H;

/**
 * Класс-сущность  документ приходная  накладая
 *
 */
class GoodsReceipt extends Document
{
    public function generateReport() {
        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $i = 1;

        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {
            $name = $item->itemname;
            if (strlen($item->snumber) > 0) {
                $name .= ' (' . $item->snumber . ' ' . H::fd($item->sdate) . ')';
            }

            $detail[] = array("no"       => $i++,
                              "itemname" => $name,

                              "itemcode" => $item->item_code,
                              "barcode"  => $item->bar_code,
                              "custcode" => $item->custcode,
                              "quantity" => H::fqty($item->quantity),
                              "price"    => H::fa($item->price),
                              "msr"      => $item->msr,
                              "amount"   => H::fa($item->quantity * $item->price)
            );
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "basedoc"         => $this->headerdata["basedoc"],
                        "firm_name"       => $firm['firm_name'],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "isval"           => strlen($this->_doc->headerdata['val']) > 1,
                        "valname"           => $this->_doc->headerdata['valname'],
                        "rate"           => $this->_doc->headerdata['rate'],
                        "customer_name"   => $this->customer_name,
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false
        );
        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }

        $header['notes'] = nl2br($this->notes)  ;
        $header['storename'] = $this->headerdata["storename"]  ;
        $header['isprep'] = $this->headerdata["prepaid"] > 0;
        $header['isdisc'] = $this->headerdata["disc"] > 0;
        $header['isnds'] = $this->headerdata["nds"] > 0;
        $header['isval'] = strlen($this->headerdata['val']) > 1;
        $header['outnumber'] = strlen($this->headerdata['outnumber']) > 0 ? $this->headerdata['outnumber'] : false;

        $header['prepaid'] = H::fa($this->headerdata["prepaid"]);
        $header['disc'] = H::fa($this->headerdata["disc"]);
        $header['delivery'] = H::fa($this->headerdata["delivery"]);
        $header['nds'] = H::fa($this->headerdata["nds"]);
        $header['rate'] = $this->headerdata["rate"];
        if ($header['rate'] == 0 || $header['rate'] == 1) {
            $header['isval'] = false;
        }
        $val = H::getValList();
        $header['val'] = $val[$this->headerdata['val']];

        $report = new \App\Report('doc/goodsreceipt.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $types = array();
        $common = \App\System::getOptions("common");
        if ($this->amount == 0) {
            // return;
        }
        $rate= doubleval($this->headerdata["rate"]);

        if ($rate == 0 || $rate == 1) {
            $rate =1;
        }

        $total = $this->amount;
        if ($this->headerdata["disc"] > 0) {
            $total = $total - $this->headerdata["disc"];
        }
        if ($this->headerdata["nds"] > 0) {
            $total = $total + $this->headerdata["nds"];
        }



        if($this->headerdata['delivery'] > 0  &&    $this->headerdata['spreaddelivery'] ==1) {
            $total = $total + $this->headerdata["delivery"];  // распределяем накладные  затраты  на  себестоимость
        }

        $total = $total * $rate;


        $k = $total / $this->amount;


        //аналитика
        foreach ($this->unpackDetails('detaildata') as $item) {


            if ($total > 0) {

                $iprice = H::fa($item->price * $k); //пересчитываем  учетную цену
            } else {
                $iprice = 0;
            }
       //     $item->amount = $iprice * $item->quantity;
            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $iprice, $item->snumber, $item->sdate, true);

            $sc = new Entry($this->document_id, $iprice * $item->quantity, $item->quantity);
            $sc->setStock($stock->stock_id);
            // $sc->setExtCode($iprice); //Для АВС
            $sc->setOutPrice($iprice);
            $sc->tag=Entry::TAG_BAY;

            $sc->save();

            //запоминаем  курс
            if (strlen($this->headerdata['val']) > 1 && $this->headerdata['rate'] != 0 && $this->headerdata['rate'] != 1) {
                $it = Item::load($item->item_id);
                $it->val = $this->headerdata['val'];
                $it->rate = $this->headerdata['rate'];
                $it->save();
            }
        }

        $payed = $this->headerdata['payed'];

        $payed = H::fa( $payed * $rate);
        $this->payamount = H::fa($this->headerdata['payamount'] * $rate);


        $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $payed, $this->headerdata['payment']);
        if ($payed > 0) {
            $this->payed = $payed;
        }

        if($this->headerdata['delivery'] > 0) {
           if($this->headerdata['spreaddelivery']== 0) { //если  не  распределяем на  цену
               \App\Entity\IOState::addIOState($this->document_id, 0 - $payed + $this->headerdata["delivery"], \App\Entity\IOState::TYPE_BASE_OUTCOME);
               \App\Entity\IOState::addIOState($this->document_id, 0 - $this->headerdata["delivery"], \App\Entity\IOState::TYPE_NAKL);
           }
           if($this->headerdata['baydelivery']== 1) { //если платит  покупатель
                $pay = new \App\Entity\Pay();
                $pay->mf_id = $this->headerdata['payment'];
                $pay->document_id = $this->document_id;
                $pay->amount = 0-$this->headerdata['delivery'];
                $pay->paytype = \App\Entity\Pay::PAY_DELIVERY;
                $pay->paydate = time();
                $pay->notes = 'Доставка';
                $pay->user_id = \App\System::getUser()->user_id;
                $pay->save();
           }
           
            
            
        } else {
            \App\Entity\IOState::addIOState($this->document_id, 0 - $payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);
        }

        
        if(H::getKeyValBool('CI_optupdate')==true) {
             foreach ($this->unpackDetails('detaildata') as $item) {
                 
                 $ci = \App\Entity\CustItem::getFirst("item_id={$item->item_id} and customer_id={$this->customer_id}") ;
                 if($ci == null){
                    $ci = new \App\Entity\CustItem() ;    
                 }
                 $ci->item_id = $item->item_id;
                 $ci->customer_id = $this->customer_id;
                 $ci->price = $item->price;
                 $ci->quantity = 0;
                 $ci->cust_code = $item->custcode;
                 $ci->comment = $this->document_number;
                 $ci->updatedon = time();
                 
                 $ci->save();
                 
             }
        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'ПН-000000';
    }

    public function getRelationBased() {
        $list = array();

        $list['RetCustIssue'] = self::getDesc('RetCustIssue');
        $list['GoodsReceipt'] = self::getDesc('GoodsReceipt');
        $list['ProdIssue'] = self::getDesc('ProdIssue');
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');
        $list['MoveItem'] = self::getDesc('MoveItem');

        return $list;
    }

}     

