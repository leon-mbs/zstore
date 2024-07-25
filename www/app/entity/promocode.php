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

        $this->used = (string)($xml->used[0]);
        $this->showcheck = (int)($xml->showcheck[0]);
   
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
        
           $code = PromoCode::getFirst('disabled=00 and code='.PromoCode::qstr($code)) ;
           if($code==null) {
               return "Недійсний код";
           }
           if($code->dateto >0  && $code->dateto < time() ) {
               return "Просрочений код";
           }
           if(strlen( $code->used ?? '') > 0  ) {
               if($code->type==1 || $code->type==3 ){
                  return "Вже використаний";    
               }
           }
           
           if($code->type==3){
               if( intval($code->customer_id) != intval($customer_id)) {
                  return "Персональний код"; 
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
        
          
      
    }
}
