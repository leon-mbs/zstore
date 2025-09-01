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
        
        if($optype==1  )  {
           $entry->optype = EqEntry::OP_INCOME;
           $entry->amount = $this->amount ;
           IOState::addIOState($entry->document_id, $entry->amount,IOState::TYPE_OTHER_INCOME) ;
        }
        if($optype==2  )  {   //закупка                                                      
           $entry->optype = EqEntry::OP_INCOME;
           $entry->amount = $this->amount ;
        //   IOState::addIOState($entry->document_id, $entry->amount,IOState::TYPE_INEQ) ;
 
           $b = new \App\Entity\CustAcc();
           $b->customer_id = $this->customer_id;
           $b->document_id = $this->document_id;
           $b->amount = $this->amount;
           $b->createdon = $this->document_date;
           $b->optype = \App\Entity\CustAcc::SELLER;
           $b->save();           
 
        }
        if($optype==3  )  {   // сл  склада
           $entry->optype = EqEntry::OP_INCOME;
           $entry->amount = $this->amount ;
       //    IOState::addIOState($entry->document_id, $entry->amount,IOState::TYPE_INEQ) ;

           $stock = \App\Entity\Stock::load($this->headerdata['item_id'] );

           $sc = new \App\Entity\Entry($this->document_id, $stock->partion, -1);
           $sc->setStock($stock->stock_id);
 
           $sc->save();          
           
        }
        
        if($optype==10  )  {    // с  производства
           $entry->optype = EqEntry::OP_INCOME;
           $entry->amount = $this->amount ;
        //   IOState::addIOState($entry->document_id, $entry->amount,IOState::TYPE_INEQ) ;

                     
           
        }
        
        
        
        if($optype==4   )  {     
           $entry->optype= EqEntry::OP_MOVE;
           $entry->amount = 0   ;
        }
        if($optype==5   )  {
           $entry->optype = EqEntry::OP_AMOR;
           $entry->amount = 0-$this->amount   ;
        //   IOState::addIOState($entry->document_id, $entry->amount,IOState::TYPE_AMOR) ;
           
        }
        if($optype==6   )  {
           $entry->optype = EqEntry::OP_REM;
           $entry->amount = $this->amount   ;
        //   IOState::addIOState($entry->document_id, $entry->amount,IOState::TYPE_INVEQ) ;
        }
  
       if($optype==7 )  {
           $entry->optype= EqEntry::OP_OUTCOME;
           $entry->amount = 0-$eq->getBalance() ;
           IOState::addIOState($entry->document_id, 0-$entry->amount,IOState::TYPE_OUTEQ) ;
       }
       if($optype==8 )  {    //продажа
           $entry->optype= EqEntry::OP_OUTCOME;
        
           $entry->amount = $this->amount;
           
           IOState::addIOState($entry->document_id, 0-$eq->getBalance() ,IOState::TYPE_OUTEQ) ;
      
           IOState::addIOState($entry->document_id, $entry->amount,IOState::TYPE_OTHER_INCOME) ;
          
           $b = new \App\Entity\CustAcc();
           $b->customer_id = $this->customer_id;
           $b->document_id = $this->document_id;
           $b->amount = $this->amount;
           $b->createdon = $this->document_date;
           $b->optype = \App\Entity\CustAcc::BUYER;
           $b->save();           
     
       
       }
       if($optype==9 )  {    // на  склад
           $entry->optype = EqEntry::OP_OUTCOME;
           $entry->amount = 0-$eq->getBalance() ;
           IOState::addIOState($entry->document_id, $entry->amount,IOState::TYPE_OUTEQ) ;
            
           $stock = \App\Entity\Stock::getStock($this->headerdata['store_id'], $this->headerdata['item_id'], 0-$entry->amount);

           $sc = new \App\Entity\Entry($this->document_id, $stock->partion, 1);
           $sc->setStock($stock->stock_id);
 
           $sc->save();            
       }
       
       $entry->save();
        
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

}
