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
        $firm = H::getFirmData(  $this->branch_id);

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
                        "isval"           => strlen($this->headerdata['val']) > 1,
                        "valname"           => $this->headerdata['valname'],
                        "rate"           => $this->headerdata['rate'],
                        "customer_name"   => $this->customer_name,
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "storeemp"             => false,
                         "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false
        );
        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }
        if ($this->headerdata["storeemp"] > 0  ) {
            $header['storeemp'] = $this->headerdata["storeempname"];
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
        $rate= doubleval($this->headerdata["rate"]??0);

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


        $k = $this->amount > 0 ? $total / $this->amount : 1;


        //аналитика
        foreach ($this->unpackDetails('detaildata') as $item) {


            if ($total > 0) {

                $iprice = H::fa($item->price * $k); //пересчитываем  учетную цену
            } else {
                $iprice = 0;
            }
       //     $item->amount = $iprice * $item->quantity;
            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $iprice, $item->snumber, $item->sdate, true,$this->headerdata['comission']==1 ? $this->customer_id :0,$this->headerdata['storeemp']??0);

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

        $payed = doubleval($this->headerdata['payed']??0);

        $payed = H::fa( $payed * $rate);
        $this->payamount = H::fa($this->headerdata['payamount'] * $rate);
        $this->amount = H::fa($this->amount * $rate);


        $this->payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $payed, $this->headerdata['payment']);
      
        $this->DoBalans() ;

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
                \App\Entity\IOState::addIOState($this->document_id, 0 - $this->headerdata["delivery"], \App\Entity\IOState::TYPE_NAKL);
                
           }
           
            
            
        } else {
            \App\Entity\IOState::addIOState($this->document_id, 0 - $payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);
        }

        
        if(($common['ci_update'] ?? 0 )==1) {
             foreach ($this->unpackDetails('detaildata') as $item) {
                 
                 $ci = \App\Entity\CustItem::getFirst("item_id={$item->item_id} and customer_id={$this->customer_id}") ;
                 if($ci == null){
                    $ci = new \App\Entity\CustItem() ;    
                 }
                 $ci->item_id = $item->item_id;
                 $ci->customer_id = $this->customer_id;
                 $ci->price = $item->price;
                 $ci->quantity = 0;
                 $ci->cust_code = $item->custcode??'';
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
            $b->amount = $this->payamount;
            $b->optype = \App\Entity\CustAcc::SELLER;
            $b->save();
        }
        //платежи       
        foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where paytype < 1000 and   coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = 0-$p['amount'];
            $b->createdon = strtotime($p['paydate']);
            $b->optype = \App\Entity\CustAcc::SELLER;
            $b->save();
        }
        
    }
}     

