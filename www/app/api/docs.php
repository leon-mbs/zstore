<?php
  

namespace App\API;
use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Helper as H;

class docs extends \App\API\Base\JsonRPC
{
     //список  статусов
     public function statuslist(){
        $list = \App\Entity\Doc\Document::getStateList();
     
        return $list;       
     }


     //записать ордер
     public function createorder($args){ 
         $doc = Document::create('Order') ;
         if(strlen($args['number'])>0 ){
             
            $num = Document::qstr($args['number'] );
            $d  = Document::getFirst(" meta_name='Order' and  document_number = {$num}  ");
            if($d != null){
                throw  new  \Exception(H::l("apinumberexists"));
            }   else {
                $doc->document_number = $args['number'] ;
            }
         }  else {
                $doc->document_number = $doc->nextNumber();
         }
         if($args['customer_id']>0) {
            $doc->customer_id = $args['customer_id'] ;
         }
         $doc->document_date = time();
         $doc->state = Document::STATE_NEW ;
         $doc->headerdata["api"]  = 1; //создан  с  API
         $doc->headerdata["phone"]  = $args['phone'];  
         $doc->headerdata["email"]  = $args['email'];  
         $doc->headerdata["ship_address"]  = $args['ship_address'];  
         $doc->notes  = @base64_decode($args['description']);  
         $details = array();
         $total =0;
         if(is_array($args['items']) && count($args['items'])>0) {
             foreach($args['items'] as $it) {
                  if($it['item_id'] >0) {
                     $item = Item::load($it['item_id']) ;  
                  } else {
                     $item = Item::getFirst("disabled<> 1 and item_code=". Item::qstr($it['code'])) ;    
                  }
                  if($item instanceof  Item){
                     
                     $item->quantity =  $it['quantity'] ;
                     $item->price =  $it['price']  ;
                     $item->amount = $item->quantity* $item->price;
                     $total = $total + $item->quantity* $item->price;
                     $details[$item->item_id] = $item; 
                  }
             }
         }  else {
             throw  new  \Exception(H::l("apinoitems"));
         }
         if(count($details)==0) throw  new  \Exception(H::l("apinoitems"));
         $doc->packDetails('detaildata', $details);
        
         $doc->save();
         
         return $doc->document_number;
     }
     
     // проверка  статусов документов по  списку  номеров 
     public function checkstatus($args){ 
         $list = array();
         
         if(!is_array($args['numbers'])) {
             throw new \Exception(H::l("apiinvalidparameters"));
         }
         foreach($args['numbers'] as $num) {
            $num = Document::qstr($num);
            $doc = Document::getFirst("  content   like '%<api>1</api>%' and  document_number = {$num}  ");
            if($doc instanceof Document) {
                $list[] = array (
                           "document_number"=> $doc->document_number,
                           "status"=>$doc->state 
                           
                     );
            }
         }
         
         return  $list;   
     }  
     
     //запрос на  отмену
     public function cancel($args){ 
        $doc=null; 
        if(strlen($args['number'])>0) {
            
            $code = Document::qstr($args['document_number']);
            $doc = Document::getFirst(" content like '%<api>1</api>%' and  document_number = {$code}  ");

        }   
        if($doc == null) {
            throw new  \Exception(H::l("apinodoc"));
        }  

            $user = \App\System::getUser();
            $admin = \App\Entity\User::getByLogin('admin');
            $n = new \App\Entity\Notify();
            $n->user_id = $admin->user_id;
            $n->dateshow = time();
            $n->message = H::l("apiasccancel",$user->username,$doc->document_number,$args['reason']);
            $n->save();
                
     }
   

     /*
      //список  документов
     public function doclist($args){
        if(in_array($args['type'],array('Order','TTN'))==false) {
             throw  new  \Exception('apinodoctype');       
        }
        $st = Document::getStateList() ;
        if(in_array($args['status'],array_keys($st))==false) {
             throw  new  \Exception('apinodocstate');       
        }
        
        $list = array();
        $docs = Document::find("state = {$args['status']} and meta_name='".$args['type']."'","document_number");
        foreach($docs as $doc) {
           $d = array();
           $d['document_id']    =  $doc->document_id;
           $d['document_number']  =  $doc->document_number;
           $d['document_date']  =  H::fd($doc->document_date);
           $d['customer_id']    =  $doc->customer_id;
           $d['customer_name']  =  $doc->customer_name;
           $d['description']    = base64_encode($doc->notes);
           $d['delivery']    = $doc->headerdata["delivery"];
           $d['ship_address']    = $doc->headerdata["ship_address"];
           $d['total']    =  H::fa($doc->amount) ;
           $d['status']    =   $doc->state  ;
           $d['statusname']    =  Document::getStateName($doc->state )  ;
           if($args['type']=='Order') {
               $d['payamount']    =  H::fa($doc->payamount) ;
               $d['payed']    =  H::fa($doc->payed) ;
           }
           if($args['type']=='TTN') {
               $d['weight']    =  $doc->headerdata["weight"] ;
               $d['ship_number']    =  $doc->headerdata["ship_number"] ;
               $d['ship_amount']    =  H::fa($doc->headerdata["ship_amount"]) ;
                
           }
          
           $detail = array(); 
           foreach ($doc->unpackDetails('detaildata') as $item) {
               $detail[] = array( 
                    "item_id"    => $item->item_id,
                    "itemname"   => $item->itemname,
                    "item_code"  => $item->item_code,
                    "quantity"   => H::fqty($item->quantity),
                    "price"      => H::fa($item->price),
                    "msr"        => $item->msr,
                    "amount"     => H::fa($item->quantity * $item->price) 
                  )  ;       
           }
           
           $d['items'] = $detail;   
           $list[] = $d;
        }
    
        return $list;       
     }
     */  
}
