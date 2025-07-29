<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;
use App\Helper as H;
use App\System as System;

/**
 * Класс-сущность  документ  кассовый чек
 *
 */
class POSCheck extends Document
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
        foreach ($this->unpackDetails('services') as $ser) {
            $detail[] = array("no"         => $i++,
                              "tovar_name" => $ser->service_name,
                              "tovar_code" => '',
                              "quantity"   => H::fqty($ser->quantity),
                              "msr"        => '',
                              "price"      => H::fasell($ser->price),
                              "amount"     => H::fasell($ser->quantity * $ser->price)
            );
        }

        $common = System::getOptions('common');

        $firm = H::getFirmData( $this->branch_id);

        $shopname=$common["shopname"] ;
        $pos = \App\Entity\Pos::load($this->headerdata['pos']) ;
        if(strlen($pos->pointname) >0) {
           $shopname=$pos->pointname ;   
        }
        if(strlen($pos->address) >0) {
           $firm["address"]=$pos->address ;   
        }
    
        
        $printer = System::getOptions('printer');
 
        $pp = doubleval($this->headerdata['payed'])+ doubleval($this->headerdata['payedcard']);

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "firm_name"       => $firm["firm_name"],

                        "shopname"        => $shopname,
                        "address"         => $firm["address"],
                        "phone"           => $firm["phone"],
                        "inn"           => strlen($firm["inn"]) >0 ? $firm["inn"] : false,
                        "tin"           => strlen($firm["tin"]) >0 ? $firm["tin"] : false,
                        "customer_name"   => strlen($this->customer_name) > 0 ? $this->customer_name : false,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"] ?? null) > 0 ? $this->headerdata["fiscalnumber"] : false,
                        "exchange"        => H::fasell($this->headerdata["exchange"]),
                        "pos_name"        => $this->headerdata["pos_name"],
                        "time"            => H::fdt($this->headerdata["time"],true),
                        "document_number" => $this->document_number,
                        "total"           => H::fasell($this->amount),
                        "payed"           => H::fasell($pp),
                        "totaldisc"       => $this->headerdata["totaldisc"] > 0 ? H::fa($this->headerdata["totaldisc"]) : false,
                        "prepaid"         => $this->headerdata['prepaid'] > 0 ? H::fa($this->headerdata['prepaid']) : false   ,

                        "docqrcode"       => $this->getQRCodeImage(),
                        "payamount"       => H::fasell($this->payamount)
        );
        if($this->headerdata['payment'] ?? null  >0) {
            $mf = \App\Entity\MoneyFund::load($this->headerdata['payment']);
            $header['nal']  = $mf->beznal!=1;
        }
        $report = new \App\Report('doc/poscheck.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function generatePosReport($ps=false) {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            $name = strlen($item->shortname) > 0 ? $item->shortname : $item->itemname;

            $detail[] = array(
                "tovar_name" => $name,
                "quantity"   => H::fqty($item->quantity),
                "price"   => H::fasell($item->price),
                "amount"     => H::fasell($item->quantity * $item->price)
            );
        }
        $i = 1;
        foreach ($this->unpackDetails('services') as $ser) {
            $detail[] = array("no"         => $i++,
                              "tovar_name" => $ser->service_name,
                              "quantity"   => H::fqty($ser->quantity),
                              "price"   => H::fasell($ser->price),
                              "amount"     => H::fasell($ser->quantity * $ser->price)
            );
        }
        $common = System::getOptions('common');
        $printer = System::getOptions('printer');
        $style = "";
        if (strlen($printer['pdocfontsize']?? null) > 0 || strlen($printer['pdocwidth']?? null) > 0) {
            $style = 'style="font-size:' . $printer['pdocfontsize'] . 'px;width:' . $printer['pdocwidth'] . ';"';

        }

        $firm = H::getFirmData(  $this->branch_id);
    
        $shopname=$common["shopname"] ;
        $pos = \App\Entity\Pos::load($this->headerdata['pos']) ;
        if(strlen($pos->pointname) >0) {
           $shopname=$pos->pointname ;   
        }
        if(strlen($pos->address) >0) {
           $firm["address"]=$pos->address ;   
        }
    
    
        $addbonus = $this->getBonus() ;
        $delbonus = $this->getBonus(false) ;
        $allbonus = 0 ;
        if($this->customer_id >0) {
            $c=\App\Entity\Customer::load($this->customer_id);
            $allbonus = $c->getBonus();
        }
        $pp = doubleval($this->headerdata['payed'])+ doubleval($this->headerdata['payedcard']);

        $header = array('date'          => H::fd($this->document_date),
                        "_detail"       => $detail,
                        "style"         => $style,
                        "username"      => $this->headerdata['cashier'] ,
                        "firm_name"     => $firm["firm_name"],
                        "shopname"      => strlen($shopname) > 0 ? $shopname : false,
                        "address"       => $firm["address"],
                        "phone"         => $firm["phone"],
                        "inn"           => strlen($firm["inn"]) >0 ? $firm["inn"] : false,
                        "tin"           => strlen($firm["tin"]) >0 ? $firm["tin"] : false,
                        "checkslogan"   => $common["checkslogan"],
                        "customer_name" => strlen($this->headerdata["customer_name"]?? null) > 0 ? $this->headerdata["customer_name"] : false,
                        "fiscalnumber"  => strlen($this->headerdata["fiscalnumber"]?? null) > 0 ? $this->headerdata["fiscalnumber"] : false,
                        "fiscalnumberpos"  => strlen($this->headerdata["fiscalnumberpos"]?? null) > 0 ? $this->headerdata["fiscalnumberpos"] : false,
                        "notes"           => nl2br($this->notes),
                        "prepaid"         => $this->headerdata['prepaid'] > 0 ? H::fa($this->headerdata['prepaid']) : false   ,


                        "pos_name"        => $this->headerdata["pos_name"],
                        "time"            => H::fdt($this->headerdata["time"],true),
                        "document_number" => $this->document_number,
                        "total"           => H::fasell($this->amount),
                        "totaldisc"           => $this->headerdata["totaldisc"] > 0 ? H::fasell($this->headerdata["totaldisc"]) : false,
                        "exchange"        => $this->headerdata["exchange"] > 0 ? H::fasell($this->headerdata["exchange"]) : false,
                        "addbonus"           => $addbonus > 0 ? H::fa($addbonus) : false,
                        "delbonus"           => $delbonus > 0 ? H::fa($delbonus) : false,
                        "allbonus"           => $allbonus > 0 ? H::fa($allbonus) : false,
                        "trans"           => $this->headerdata["trans"] > 0 ? $this->headerdata["trans"] : false,
                        "payeq"           => strlen($pos->payeq ) > 0 ? $pos->payeq : false,
                        "isdocqrcode"     =>  $common['printoutqrcode']==1,
                        "docqrcodeurl"     =>  $this->getQRCodeImage(true),
                        "docqrcode"       => $this->getQRCodeImage(),
                        "payed"           => $pp > 0 ? H::fasell($pp) : false,
                        "payamount"       => $this->payamount > 0 ? H::fasell($this->payamount) : false
        );

        if($header['inn'] != false) {
            $header['tin'] = false;
        }

        $frases = explode(PHP_EOL, $header['checkslogan']) ;
        if(count($frases) >0) {
            $i=  rand(0, count($frases) -1)  ;
            $header['checkslogan']   =   $frases[$i];
        }
        if(strlen($header['checkslogan'] ??'') ==0) {
            $header['checkslogan']  = false;
        }

        //промокод        
        $pc = \App\Entity\PromoCode::find('type=2 and disabled <> 1  and coalesce(enddate,now()) >=now()' ,'id desc') ;
        foreach($pc as $p) {
           
          
           if($p->showcheck==1 && $p->disc>0) {
               $header['promo']  = 'Промокод '. $p->code . " на {$p->disc}% знижку";
               break;
           }
        }  
           
           
        if(strlen($header['promo']  ??'') ==0) {
            $header['promo']  = false;
        }
        
        
        $header['form1']  = false;
        $header['form2']  = false;
        $header['form3']  = false;


        if(($this->headerdata['payment']?? 0)  >0) {
            $mf = \App\Entity\MoneyFund::load($this->headerdata['payment']);
            $header['form1']  = $mf->beznal!=1;
            $header['form2']  = $mf->beznal==1;
        } else {
            if($this->headerdata['payed']>0) {
                $header['form1']  = true;
            }
            if($this->headerdata['payedcard']>0) {
                $header['form2']  = true;
            }
            if($this->headerdata['payed']>0  && $this->headerdata['payedcard']) {
                $header['form1']  = false;
                $header['form2']  = false;
                $header['form3']  = true;
            }
        }
        if($header['form1']  == true) {
           $header['payeq']  = false; 
        }
        
        if($ps) {
            $report = new \App\Report('doc/poscheck_bill_ps.tpl');
        } else {
            $report = new \App\Report('doc/poscheck_bill.tpl');
        }

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //$conn = \ZDB\DB::getConnect();
        $lost = 0;
      
        $dd =   doubleval($this->headerdata['bonus']) +  doubleval($this->headerdata['totaldisc'])   ;
        $k = 1;   //учитываем  скидку
        if ($dd > 0 && $this->amount > 0) {
            $k = ($this->amount - $dd) / $this->amount;
        }



        //оплата

        $pp = $this->headerdata['payed'];
        if ($this->headerdata['exchange'] > 0) {

            $pp = $pp - $this->headerdata['exchange']; //без здачи
        }          
        $payed = 0;
        if(($this->headerdata['payment'] ??0)  >0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $pp, $this->headerdata['payment']);
        } else {
            if(($this->headerdata['mfnal']??0)  >0 && $pp >0) {
                $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $pp, $this->headerdata['mfnal']);
            }
            if(($this->headerdata['mfbeznal']??0) >0 && ($this->headerdata['payedcard']??0) >0) {
                $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->headerdata['payedcard'], $this->headerdata['mfbeznal']);
            }
        }


        if ($payed > 0) {
            $this->payed = $payed;
        }
        \App\Entity\IOState::addIOState($this->document_id, $payed, \App\Entity\IOState::TYPE_BASE_INCOME);
        $this->DoBalans() ;

        
        if ($this->parent_id > 0) {
            $parent = Document::load($this->parent_id);
            if ($parent->meta_name == 'ServiceAct' ) {
                if($parent->state == Document::STATE_WP )  {
               //    $parent->updateStatus(Document::STATE_PAYED); 
                }
            //    $parent->setHD('waitpay',0); 
             //   $parent->save();             
                
                return true; //проводки выполняются  в  сервисе
            }
        }        
 

        // работы
        foreach ($this->unpackDetails('services') as $ser) {

            $sc = new Entry($this->document_id, 0 - ($ser->price * $k * $ser->quantity), 0-$ser->quantity);
            $sc->setService($ser->service_id);
            // $sc->setExtCode(0 - ($ser->price * $k)); //Для АВС
            $sc->setOutPrice( $ser->price * $k);
            $sc->cost= $ser->cost;
        
            $sc->save();
        }
 
        
        // товары
        foreach ($this->unpackDetails('detaildata') as $item) {

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

                        if ($itemp->checkMinus($itemp->quantity, $this->headerdata['store']) == false) {
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

                throw new \Exception("На складі всього ".$item->getQuantity($this->headerdata['store']) ." ТМЦ {$item->itemname}. Списання у мінус заборонено");

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
        
        if(strlen($this->headerdata['promocode']) > 0){
            \App\Entity\PromoCode::apply($this->headerdata['promocode'],$this);
        }
       
        //бонус  сотруднику

        $disc = \App\System::getOptions("discount");
        $emp_id = \App\System::getUser()->employee_id ;
        if($emp_id >0 && ($disc["bonussell"]??0) >0) {
            $b = intval( $this->amount * $disc["bonussell"] / 100);
            if($b >0){
                $ua = new \App\Entity\EmpAcc();
                $ua->optype = \App\Entity\EmpAcc::BONUS;
                $ua->document_id = $this->document_id;
                $ua->emp_id = $emp_id;
                $ua->amount = $b;
                $ua->save();
             
                $n = new \App\Entity\Notify();
                $n->user_id = \App\System::getUser()->user_id;;;
                $n->message = "Бонус " . $b  ;
                $n->sender_id =  \App\Entity\Notify::SYSTEM;
                $n->save(); 
            }
        }
 
        
        return true;
    }

    protected function getNumberTemplate() {
        return 'K-000000';
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_POS);
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = self::getDesc('Warranty');
        $list['ReturnIssue'] = self::getDesc('ReturnIssue');
        $list['TTN'] = self::getDesc('TTN');

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
        
               
       //платежи    
        if($this->payamount >0) {
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = 0-$this->payamount;
            $b->optype = \App\Entity\CustAcc::BUYER;
            $b->save();
        }
       //тмц      
        foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where paytype < 1000 and   coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = $p['amount'];
            $b->createdon = strtotime($p['paydate']);
            $b->optype = \App\Entity\CustAcc::BUYER;
            $b->save();
        }
        
    }

    
   protected function onState($state, $oldstate) {
        if($state == Document::STATE_EXECUTED  || $state == Document::STATE_PAYED) {

            if($this->parent_id > 0) {
                $order = Document::load($this->parent_id)->cast();
                if($order->meta_name == 'Order' && $order->state > 4) {

                          
                    if( count( $order->getNotSendedItem() ) >0 ) return;
            
                    if($order->state == Document::STATE_INSHIPMENT || 
                        $order->state == Document::STATE_INPROCESS ||  
                        $order->state == Document::STATE_FINISHED ||  
                        $order->state == Document::STATE_READYTOSHIP) {
                            
                        $order->updateStatus(Document::STATE_DELIVERED);
                    }                            
                    \App\Helper::log("order  state {$order->state} payamount {$this->payamount} payed  {$this->payed}  ");
                    if($this->payed  >= $this->payamount  ) {  //если  оплачено  
                        if ($order->state == Document::STATE_DELIVERED) {
                            $order->updateStatus(Document::STATE_CLOSED);
                        }
                    }
                    
                

                }
            }
        }
    }
     
    
}
