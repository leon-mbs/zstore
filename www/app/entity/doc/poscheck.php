<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\System;
use \App\Util;

/**
 * Класс-сущность  документ  кассовый ек
 *
 */
class POSCheck extends Document {

    public function generateCheck( ) {
        
        $firm =System::getOptions('firm');
          
        $check = array();
        $check[] .=  "Чек ". $this->document_number;
        $check[] .=  "вiд ". date('Y-m-d H:i:s',$this->headerdata['time']);
        $check[] .=  "IПН ". $firm['inn'];
        $check[] .=  $firm['firmname'];
        $check[] .=  "Тел.  ". $firm['phone'];
        $check[] .=  "Viber ". $firm['viber'];
        $check[] .=  str_repeat('-',30);
      
        foreach ($this->detaildata as $value) {
      
            $t =  $value['item_code']. ' ' .$value['itemname'];
            foreach( Util::mb_split( $t,30) as $p){
                 $check[] .=  $p;
            }
            $q =  ''.H::fqty($value['quantity']).$value['msr'].' по '.H::fa($value['price']);
            $check[] .=  sprintf("%s%10s",$q.str_repeat(' ',20-mb_strlen($q)), H::fa($value['quantity'] * $value['price'])) ;
         
            
        }
         
        $check[] .=  str_repeat('-',30);
        $check[] .=  sprintf("%s%10s",'Всього'.str_repeat(' ',14), H::fa($this->amount)) ;
        if($this->headerdata["paydisc"] >0){
            $check[] .=  sprintf("%s%10s",'Знижка'.str_repeat(' ',14), H::fa($this->headerdata["paydisc"])) ;
        }  
        $check[] .=  sprintf("%s%10s",'До сплати'.str_repeat(' ',11), H::fa($this->payamount)) ;
        $check[] .=  sprintf("%s%10s",'Внесена оплата'.str_repeat(' ',6), H::fa($this->headerdata["payed"])) ;
        $check[] .=  sprintf("%s%10s",'Здача'.str_repeat(' ',15), H::fa($this->headerdata["exchange"])) ;
        $check[] .=  "Дякуємо за довiру до нас!" ;       
        /*
        foreach ($this->detaildata as $value) {

            if (isset($detail[$value['item_id']])) {
                $detail[$value['item_id']]['quantity'] += $value['quantity'];
            } else {
                $name = $value['itemname'];
                if (strlen($value['snumber']) > 0) {
                    $name .= ' (' . $value['snumber'] . ',' . date('d.m.Y', $value['sdate']) . ')';
                }


                $detail[] = array("no" => $i++,
                    "tovar_name" => $name,
                    "tovar_code" => $value['item_code'],
                    "quantity" => H::fqty($value['quantity']),
                    "msr" => $value['msr'],
                    "price" => H::fa($value['price']),
                    "amount" => H::fa($value['quantity'] * $value['price'])
                );
            }
        }

        */
   
        return $check;
    }

    public function generateReport() {
        
        $print = implode("<br>",$this->generateCheck()) ;
        $print = str_replace(' ','&nbsp;',$print) ;
        
        $header = array('print' => $print);

        $report = new \App\Report('poscheck.tpl');

        $html = $report->generate($header);

        return $html;
   
    }
    
    public function Execute() {
        //$conn = \ZDB\DB::getConnect();


        foreach ($this->detaildata as $item) {
            $listst = \App\Entity\Stock::pickup($this->headerdata['store'], $item['item_id'], $item['quantity'], $item['snumber']);
     
            foreach ($listst as $st) {
                $sc = new Entry($this->document_id, 0 - $st->quantity * $st->partion, 0 - $st->quantity);
                $sc->setStock($st->stock_id);
                $sc->setExtCode($item['price'] - $st->partion); //Для АВС 
                $sc->save();
            }
        }

        //списываем бонусы
        if ($this->headerdata['paydisc'] > 0) {
            $customer = \App\Entity\Customer::load($this->customer_id);
            if ($customer->discount > 0) {
                return; //процент
            } else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0 );
                $customer->save();
            }
        }

        $this->payed = 0;
        if ($this->headerdata['payment'] > 0 && $this->headerdata['payed']) {
            \App\Entity\Pay::addPayment($this->document_id, 1, $this->headerdata['payed'], $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME, $this->headerdata['paynotes']);
            $this->payed = $this->headerdata['payed'];
        }

        return true;
    }

    public function getRelationBased() {
        $list = array();
        $list['Warranty'] = 'Гарантийный талон';
        $list['ReturnIssue'] = 'Возврат';

        return $list;
    }

    protected function getNumberTemplate() {
        return 'К-000000';
    }
   
 
}
