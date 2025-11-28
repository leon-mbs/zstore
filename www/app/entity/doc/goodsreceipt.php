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
        $header['val'] = $val[$this->headerdata['val']]??'';

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



        if( $this->headerdata['deliverytype']   ==2  && $this->headerdata['delivery'] > 0) {
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

         $io= $this->payamount;
         $del = $this->headerdata['delivery'] * $rate;
         if($del > 0) {
           if($this->headerdata['deliverytype']  < 4 ) { 
               \App\Entity\IOState::addIOState($this->document_id, 0- $del, \App\Entity\IOState::TYPE_BASE_OUTCOME);
           }  
           if($this->headerdata['deliverytype'] ==2 || $this->headerdata['deliverytype'] == 4 ) { 
               $io = $io - $del;   //вычитаем из общих расходов  (по оплате)
           }  
       
        }  

        \App\Entity\IOState::addIOState($this->document_id, 0 - $io, \App\Entity\IOState::TYPE_BASE_OUTCOME);
       
     
        if(($common['ci_update'] ?? 0 )==1) { // обновление журнала  товары у поставщика
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

        $this->DoAcc();  
        
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
   
        // к оплате
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
        $this->DoAcc();  
      
    }
    
    
   protected function onState($state, $oldstate) {
        if($state == Document::STATE_EXECUTED  || $state == Document::STATE_PAYED) {

            if($this->parent_id > 0) {
                $order = Document::load($this->parent_id)->cast();
                if($order->meta_name == 'OrderCust'  ) {
                      $order = $order->cast() ;
                      $order->updateStatus(Document::STATE_CLOSED);
                      
                }    
            }
      }
   }
   
 public   function DoAcc() {
         if(\App\System::getOption("common",'useacc')!=1 ) return;
         parent::DoAcc()  ;
    
    
         $ia=\App\Entity\AccEntry::getItemsEntry($this->document_id,Entry::TAG_BAY) ;
         foreach($ia as $a=>$am){
             \App\Entity\AccEntry::addEntry($a,'63', $am,$this->document_id)  ; 
         } 
   
         $this->DoAccPay('63'); 
               
         if($this->headerdata['delivery'] > 0) {
           if($this->headerdata['deliverytype']== 1) { 
                \App\Entity\AccEntry::addEntry('941',  '23',   $this->headerdata['delivery'],$this->document_id )  ; 
        
           }
           if($this->headerdata['deliverytype']== 2) { 
                \App\Entity\AccEntry::addEntry(null,  '941',   $this->headerdata['delivery'],$this->document_id )  ; 
        
           }
           
        }  
        
        
   
        
        if ($this->headerdata["disc"] > 0) {
           \App\Entity\AccEntry::addEntry('63', '71',   $am,$this->document_id,$p->paydate)  ; 
        }
        if ($this->headerdata["nds"] > 0) {
           //   если  предоплата то дата первого события
           $date= $this->document_date;
           if($this->parent_id >0){
               foreach(\App\Entity\Pay::find("document_id=".$this->parent_id) as $p) {
                   $date = $pay->paydate;
                   break;
               }
           }
           \App\Entity\AccEntry::addEntry('63','641',    $this->headerdata["nds"],$this->document_id,$date,null,\App\Entity\AccEntry::TAG_NDS  )  ; 
        }                    
    } 
    
}

