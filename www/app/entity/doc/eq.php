<?php

namespace App\Entity\Doc;

use App\Entity\Equipment;
use App\Entity\EqEntry;
use App\Entity\IOState;
use App\Helper as H;

/**
 * Класс-сущность  документ операции с ОС и НМА
 *
 */                                   
class EQ extends Document
{
    public function Execute() {
        $eq =  Equipment::load($this->headerdata['eq_id']);
        $optype= $this->headerdata['optype'] ;

        $entry = new EqEntry( );
        $entry->document_id = $this->document_id;
        $entry->eq_id = $eq->eq_id;
        $entry->optype = $optype;
        
        if($optype==EqEntry::OP_INCOME  )  {
     
           $entry->amount = 0 ;

        }
        if($optype==EqEntry::OP_BUY  )  {   //закупка                                                      
            
           $entry->amount = $this->amount ;
         
           $b = new \App\Entity\CustAcc();
           $b->customer_id = $this->customer_id;
           $b->document_id = $this->document_id;
           $b->amount = $this->amount;
           $b->createdon = $this->document_date;
           $b->optype = \App\Entity\CustAcc::SELLER;
           $b->save();           
 
        }
        if($optype==EqEntry::OP_STORE  )  {   // со  склада
          
           $entry->amount = $this->amount ;
     
           $stock = \App\Entity\Stock::load($this->headerdata['item_id'] );

           $sc = new \App\Entity\Entry($this->document_id, $stock->partion, -1);
           $sc->setStock($stock->stock_id);
 
           $sc->save();          
           
        }
        
        if($optype==EqEntry::OP_PROD )  {    // с  производства
     
           $entry->amount = $this->amount ;
    
        }
        
        
        
        if($optype==EqEntry::OP_MOVE   )  {     
           
           $entry->amount = 0   ;
        }
        if($optype==EqEntry::OP_AMOR   )  {
          
           $entry->amount = 0-$this->amount   ;
     
        }
        if($optype==EqEntry::OP_REPAIR   )  {
 
           $entry->amount = $this->amount   ;
        }
  
       if($optype==EqEntry::OP_OUTCOME )  {
       
           $entry->amount = 0-$eq->getBalance() ;
          
       }
       if($optype==EqEntry::OP_SELL )  {    //продажа
         
        
           $entry->amount = 0-$this->amount  ;
        
           $b = new \App\Entity\CustAcc();
           $b->customer_id = $this->customer_id;
           $b->document_id = $this->document_id;
           $b->amount = $this->amount;
           $b->createdon = $this->document_date;
           $b->optype = \App\Entity\CustAcc::BUYER;
           $b->save();           
     
       
       }
       if($optype==EqEntry::OP_TOSTORE )  {    // на  склад
            $entry->amount = 0-$this->amount ; 
         
         
           $stock = \App\Entity\Stock::getStock($this->headerdata['store_id'], $this->headerdata['item_id'], 0-$this->amount);

           $sc = new \App\Entity\Entry($this->document_id, $stock->partion, 1);
           $sc->setStock($stock->stock_id);
 
           $sc->save();            
       }
       if($optype==EqEntry::OP_LOST )  {     
          $entry->amount = 0-$this->amount   ;
    
       }   
       $entry->save();
    
       $this->DoAcc();       
       return true;
    }

    public function generateReport() {

        $eq =  Equipment::load($this->headerdata['eq_id']);
        $optype= $this->headerdata['optype'] ;
         
        $header = array(
            
            'eqname'          => $eq->eq_name,
            'invnumber'          => $eq->invnumber,
            'opname'          => $this->headerdata['optypename'],
            'amount'          => $this->amount,
            "notes"           => nl2br($this->notes),
            "document_date"      => H::fd($this->document_date),
            "document_number" => $this->document_number,
            "store_name" => $this->headerdata['store_name'] ??'',
            "item_name" => $this->headerdata['item_name']??'',
            "emp_name" => $this->headerdata['emp_name']??'',
            "pa_name" => $this->headerdata['pa_name']??'',
            "customer_name" => $this->customer_name ??''
        );
        
        
        $header['isamount']  = false;
        $header['iscust']  = false;
        $header['isemp']   = false;
        $header['ispa']    = false;
        $header['isitem']  = false;
        
        if($optype!=4) {
           $header['isamount'] = true;
        }
        if($optype==4) {
           $header['isemp'] = strlen($this->headerdata['emp_name'] ??'' ) >0;
           $header['ispa'] =  strlen($this->headerdata['pa_name'] ??'' ) >0;
        }
        if($optype==2 || $optype==8 ) {
           $header['iscust'] = true;
        }
        if($optype==3 || $optype==9 ) {
           $header['isitem'] = true;
        }
        
        
        $report = new \App\Report('doc/eq.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОС-000000';
    }

    public   function DoAcc() {
        if(\App\System::getOption("common",'useacc')!=1 ) return;
        parent::DoAcc()  ;
        
        $optype= $this->headerdata['optype'] ;
    
        $eq_id= intval($this->headerdata['eq_id']) ;
        if($optype==EqEntry::OP_INCOME  )  {
             \App\Entity\AccEntry::addEntry( '10' ,'15',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_BUY  )  {
             \App\Entity\AccEntry::addEntry( '15' ,'63',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_STORE  )  {
             \App\Entity\AccEntry::addEntry( '15' ,'28',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_STORE  )  {
             \App\Entity\AccEntry::addEntry( '15' ,'28',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_PROD  )  {
             \App\Entity\AccEntry::addEntry( '15' ,'23',$this->amount,$this->document_id)  ; 
             \App\Entity\AccEntry::addEntry( '10' ,'15',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_AMOR  )  {
             \App\Entity\AccEntry::addEntry( '91' ,'13',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_REPAIR  )  {
             \App\Entity\AccEntry::addEntry( '15' ,'23',$this->amount,$this->document_id)  ; 
             \App\Entity\AccEntry::addEntry( '10' ,'15',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_OUTCOME  )  {
             \App\Entity\AccEntry::addEntry( '13' ,'10',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_LOST  )  {
             \App\Entity\AccEntry::addEntry( '97' ,'10',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_TOSTORE  )  {
             \App\Entity\AccEntry::addEntry( '28' ,'10',$this->amount,$this->document_id)  ; 
        }
        if($optype==EqEntry::OP_SELL  )  {
             \App\Entity\AccEntry::addEntry( '90' ,'10',$this->amount,$this->document_id)  ; 
             \App\Entity\AccEntry::addEntry( '36' ,'70',$this->amount,$this->document_id)  ; 
        }
       
         
   }    
}
