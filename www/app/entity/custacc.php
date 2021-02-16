<?php

namespace App\Entity;

/**
 * Класс-сущность  лицевой счет  контрагента
 *
 * @table=cust_acc
 * @keyfield=ca_id
 * @view=ca_id
 */
class CustAcc extends \ZCL\DB\Entity
{

    protected function init() {
        $this->ca_id = 0;
        
    }
    
    public  static function  Create($doc,$amount,$notes='')   {
         if($doc >0) {    //если передано  id
             $doc = \App\Entity\Doc\Document::load($doc) ;
         }
         if(($doc->customer_id >0) == false ) return;
         if(strlen($notes)==0)  $notes = $doc->meta_desc;
         $ca = new  CustAcc();
         $ca->document_id = $doc->document_id;
         $ca->customer_id = $doc->customer_id;
         $ca->notes = $notes;
         if($doc->headerdata['contract_id']>0) {
             $ca->contract_id = $doc->headerdata['contract_id'];
         }
         $ca->save();
    }
}