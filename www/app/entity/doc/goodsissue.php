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
                              "pricenonds"      => H::fa($item->pricenonds),
                              "amount"     => H::fa($item->quantity * $item->price)
            );
        }

        $totalstr =  \App\Util::money2str_ua($this->payamount);

        $firm = H::getFirmData(  $this->branch_id);
        $mf = \App\Entity\MoneyFund::load($this->headerdata["payment"]);

        $printer = System::getOptions('printer');

        $iban=$mf->iban??'';
        if(strlen($mf->payname ??'') > 0) $firm['firm_name']   = $mf->payname;
        if(strlen($mf->address ??'') > 0) $firm['address']   = $mf->address;
        if(strlen($mf->tin ??'') > 0) $firm['fedrpou']   = $mf->tin;
        if(strlen($mf->inn ??'') > 0) $firm['finn']   = $mf->inn;
   

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
                       "iscustaddress"    => false,

                        "bank"            => $mf->bank ?? "",
                        "bankacc"         => $mf->bankacc ?? "",
                        "isbank"          => (strlen($mf->bankacc) > 0 && strlen($mf->bank) > 0),
                        "notes"           => nl2br($this->notes),

                        "iban"      => strlen($iban) > 0 ? $iban : false,
                        "payed"      => $this->headerdata["payed"] > 0 ? H::fa($this->headerdata["payed"]) : false,
                        "payamount"  => $this->payamount > 0 ? H::fa($this->payamount) : false

        );

        $header["customer_name"] = $this->headerdata["customer_name"];
        $header["phone"] = false;
        $header["fphone"] = false;   
        $header["edrpou"] = false;
        $header["fedrpou"] = false;
        $header["finn"] = false;
        $header["isfop"] = false;
        $header['isprep'] = ($this->headerdata["prepaid"] ??0 )> 0;
        $header['prepaid'] = H::fa($this->headerdata["prepaid"]??'');
        $header["nds"] = false;
   
        if ($this->customer_id > 0) {
            $cust = \App\Entity\Customer::load($this->customer_id);
            $header["customer_name"] = $cust->customer_name;
            if (strlen($cust->phone) > 0) {
                $header["phone"] = $cust->phone;
            }
       
            if (strlen($cust->edrpou) > 0) {
                $header["edrpou"] = $cust->edrpou;
            }

            if (strlen($cust->address) > 0) {
                $header["iscustaddress"] = true;
                $header["custaddress"] = $cust->address;
            }
        }
        if (strlen($firm['tin']) > 0) {
            $header["fedrpou"] = $firm['tin'];
        }
        if (strlen($firm['phone']) > 0) {
            $header["fphone"] = $firm['phone'];
        }  

        if (strlen($this->headerdata["customer_name"]) == 0) {
            $header["customer_name"] = false;
        }
        $header["address"] = $firm['address'];        
    
        if ( ($this->headerdata["fop"] ??0) > 0) {
            $header["isfirm"] = false;
            $header["isfop"] = true;
            
            $fops=$firm['fops']??[];
            $fop = $fops[$this->headerdata["fop"]] ;
            $header["fop_name"] = $fop->name ??'';
            $header["fop_edrpou"] = $fop->edrpou ??'';
            $header["address"] = $fop->address ??'';            
        }
        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }

        if ($this->getHD('nds',0) > 0) {
            $header["nds"] = H::fa($this->getHD('nds' )) ;
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

            $onstore = H::fqty($item->getQuantity($this->headerdata['store'],"",0,$this->headerdata['storeemp']??0)) ;
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

            //продажа
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item,$this->headerdata['storeemp']??0);

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

        if($this->getHD('payed',0) > 0){
            $this->payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->headerdata['payed'], $this->headerdata['payment']);
            \App\Entity\IOState::addIOState($this->document_id, $this->headerdata['payed'], \App\Entity\IOState::TYPE_BASE_INCOME);
        }
        
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

        $firm = H::getFirmData(  $this->branch_id);
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
        if($state == Document::STATE_EXECUTED  || $state == Document::STATE_PAYED) {

            if($this->parent_id > 0) {
                $order = Document::load($this->parent_id)->cast();
                if($order->meta_name == 'Invoice' && $order->parent_id > 0) {
                      $order = Document::load($order->parent_id);
                      $order = $order->cast() ;
                      
                }
                 
                if($order->meta_name == 'Order' && $order->state > 4) {

                          
                    if( count( $order->getNotSendedItem() ) >0 ) return;
            
                    if($order->state == Document::STATE_INSHIPMENT || 
                        $order->state == Document::STATE_INPROCESS ||  
                        $order->state == Document::STATE_FINISHED ||  
                        $order->state == Document::STATE_READYTOSHIP) {
                            
                        $order->updateStatus(Document::STATE_DELIVERED);
                    }                            
               
                    if($this->payed  >= $this->payamount  ) {  //если  оплачено  
                        if ($order->state == Document::STATE_DELIVERED) {
                            $order->updateStatus(Document::STATE_CLOSED);
                        }
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
   
        $this->DoAcc();        
              
    }
    
    public   function DoAcc() {
             if(\App\System::getOption("common",'useacc')!=1 ) return;
             parent::DoAcc()  ;
      
             $ia=\App\Entity\AccEntry::getItemsEntry($this->document_id,Entry::TAG_TOPROD) ;
             foreach($ia as $a=>$am){
                 \App\Entity\AccEntry::addEntry( '23',$a, $am,$this->document_id)  ; 
             }       
             $ia=\App\Entity\AccEntry::getItemsEntry($this->document_id,Entry::TAG_FROMPROD) ;
             foreach($ia as $a=>$am){
                 \App\Entity\AccEntry::addEntry( $a,'23', $am,$this->document_id)  ; 
             }       
             $ia=\App\Entity\AccEntry::getItemsEntry($this->document_id,Entry::TAG_SELL) ;
             foreach($ia as $a=>$am){
                 \App\Entity\AccEntry::addEntry('90',$a, $am,$this->document_id)  ; 
             }
             
             $this->DoAccPay('36');   
             
            if ($this->getHD('nds',0) > 0){
               $date= $this->document_date;
               if($this->parent_id > 0 ){  //первое  событиен
                   foreach(\App\Entity\Pay::find("document_id=".$this->parent_id) as $p) {
                       $date = $pay->paydate;
                       break;
                   }
               }             
                \App\Entity\AccEntry::addEntry('641','36',$this->getHD('nds' ),$this->document_id,$date,\App\Entity\AccEntry::TAG_NDS )  ; 
               
            }    
      }        
}
