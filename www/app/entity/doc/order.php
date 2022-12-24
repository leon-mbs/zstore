<?php

namespace App\Entity\Doc;

use App\Helper as H;
use App\System;

/**
 * Класс-сущность  документ расходная  накладая
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

                /*
                  $ocstoreopt = @unserialize($item->octoreoptions);  //опции с  опенкарта
                  if (is_array($ocstoreopt)) {
                  $t = "<table cellspacing='0' cellpadding='1' style='font-size:smaller'><tr><td style='padding: 1px;'>Опции:</td><td style='padding: 1px;'></td></tr>";
                  foreach ($ocstoreopt as $k => $v) {
                  $t .= "<tr><td style='padding: 1px;'>{$k}</td><td style='padding: 1px;'>{$v}</td></tr>";
                  }
                  $t .= "</table>";
                  $item->itemname = $item->itemname . $t;
                  } */
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


        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "phone"           => $this->headerdata["phone"],
                        "email"           => $this->headerdata["email"],
                        "delivery"        => $this->headerdata["delivery_name"],
                        "ship_address"    => strlen($this->headerdata["ship_address"]) > 0 ? $this->headerdata["ship_address"] : false,
                        "notes"           => nl2br($this->notes),
                        "outnumber"       => $this->headerdata["outnumber"],
                        "isoutnumber"     => strlen($this->headerdata["outnumber"]) > 0,
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "paydisc"         => H::fa($this->headerdata["paydisc"]),
                        "isdisc"          => $this->headerdata["paydisc"] > 0,
                         "addbonus"           => $addbonus > 0 ? H::fa($addbonus) : false,
                        "delbonus"           => $delbonus > 0 ? H::fa($delbonus) : false,
                        "allbonus"           => $allbonus > 0 ? H::fa($allbonus) : false,
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
        $list['ProdReceipt'] = self::getDesc('ProdReceipt');
        if($this->payed==0) $list['Invoice'] = self::getDesc('Invoice');
        $list['POSCheck'] = self::getDesc('POSCheck');
        $list['Task'] = self::getDesc('Task');
        $list['TTN'] = self::getDesc('TTN');
        $list['OrderCust'] = self::getDesc('OrderCust');

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
        if($ps)   {
          $report = new \App\Report('doc/order_bill_ps.tpl');
        }
        else 
          $report = new \App\Report('doc/order_bill.tpl');
        
        $html = $report->generate($header);

        return $html;
    }

    //резеорвирование товаров
    public  function reserve(){
          
          $this->unreserve();
        
          if(intval($this->headerdata['store'])==0)  return;
        
          $items = $this->unpackDetails('detaildata')  ;
        
          foreach ($items as $item) {
                if (false == $item->checkMinus($item->quantity, $this->headerdata['store'])) {
                    throw new \Exception(H::l("nominus", H::fqty($item->getQuantity($this->headerdata['store'])), $item->itemname));
                }
              
          }
          foreach ($items as $item) {

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
    public  function unreserve(){
           $conn = \ZDB\DB::getConnect();
           $conn->Execute("delete from entrylist where document_id =" . $this->document_id);
    }
    
    protected function onState($state,$oldstate) {

        if ($state == self::STATE_READYTOSHIP) {

         //  $this->reserve()  ;
        }

        if ($state < 5 || $state == self::STATE_REFUSED || $state == self::STATE_FAIL || $state == self::STATE_CLOSED) {
           
           $this->unreserve()  ;
            
        }
        if ($state == self::STATE_INPROCESS) {
          

            if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
                $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->payed, $this->headerdata['payment']);
                if ($payed > 0) {
                    $this->payed = $payed;
                }
                \App\Entity\IOState::addIOState($this->document_id, $this->payed, \App\Entity\IOState::TYPE_BASE_INCOME);

            }
        }
    }

}
