<?php

namespace App\Entity;

/**
 * Клас-сущность  промокод
 *
 * @table=promocodes
 * @keyfield=id
 */
class PromoCode extends \ZCL\DB\Entity
{
    protected function init() {
        $this->id = 0;
        $this->disabled = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<details>";
        $this->details .= "<customer_id>{$this->customer_id}</customer_id>";
        $this->details .= "<customer_name><![CDATA[{$this->customer_name}]]></customer_name>";
        $this->details .= "<dateto>{$this->dateto}</dateto>";
        $this->details .= "<disc>{$this->disc}</disc>";
        $this->details .= "<discf>{$this->discf}</discf>";
        $this->details .= "<refbonus>{$this->refbonus}</refbonus>";
        $this->details .= "<showcheck>{$this->showcheck}</showcheck>";

        $this->details .= "<used>{$this->used}</used>";

        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);
        $this->customer_id = (int)($xml->customer_id[0]);
        $this->customer_name = (string)($xml->customer_name[0]);
        $this->dateto = (string)($xml->dateto[0]);
        $this->disc = (string)($xml->disc[0]);
        $this->discf = (string)($xml->discf[0]);

        $this->used = (string)($xml->used[0]);
        $this->showcheck = (int)($xml->showcheck[0]);
        $this->refbonus = (int)($xml->refbonus[0]);
   
        parent::afterLoad();
    }


    public static function findByCode($code) {
  
        $code = PromoCode::qstr($code) ;
        return PromoCode::getFirst("code=". $code) ;
        
    }
    
    public static function generate() {
        while(true){
            $code =   md5( \App\Helper::getSalt().time()); 
            $code = strtoupper(substr($code,0,8))  ;
            if(PromoCode::findByCode($code)==null){
                return $code;
            }
            
            
        }
        
    }
    
    
    /**
    * Проверка  промокода
    * 
    * @param mixed $code
    * @param mixed $customer_id
    */
    public static function check($code,$customer_id=0) {
           $conn = \ZDB\DB::getConnect();
        
           $code = PromoCode::getFirst('disabled=0 and code='.PromoCode::qstr($code)) ;
           if($code==null) {
               return "Недійсний промокод";
           }
           if($code->dateto >0  && $code->dateto < time() ) {
               return "Просрочений промокод";
           }
           if(strlen( $code->used ?? '') > 0  ) {
               if($code->type==1 || $code->type==3 ){
                  return "Промокод вже використаний";    
               }
           }
           
           if($code->type==3){
               if( intval($code->customer_id) != intval($customer_id)) {
                  return "Персональний промокод"; 
               }
           }
           if($code->type==4 && $customer_id >0 ){
            
               $cnt=intval($conn->GetOne("select count(*) from stats where  category=4 and  keyd= {$customer_id} and vald={$code->id}") );
               if($cnt > 0 ){
                  return "Промокод вже використаний";    
               } 
           }
           
           
           return "";
    }
    /**
    * применить  промокод
    * 
    * @param mixed $code
    * @param mixed $doc
    * @return mixed
    */
    public static function apply($code,$doc) {
        $ch = PromoCode::check($code,$doc->customer_id);
        if($ch !='') {
            return;
        }
        
        $code = PromoCode::getFirst( ' code='.PromoCode::qstr($code)) ;
        $code->used= date('Y-m-d').' '.$doc->document_number;
        $code->save() ;
        
        if($code->type==4 && $code->customer_id > 0) {
           \App\Helper::insertstat(4,$doc->customer_id,$code->id)  ;
           if($code->refbonus > 0){
                $b = new  \App\Entity\CustAcc() ;
                $b->customer_id = $code->customer_id;
                $b->optype =  \App\Entity\CustAcc::BONUS;
                $b->createdon = time();
                $b->document_id = $doc->document_id;
                $b->amount = (int)$code->refbonus;
     
                $b->save();            
           }             
        } 
      
    }
}
