<?php

namespace App\Entity;

/**
 * Клас-сущность  лицевой счет  контрагента
 *
 * @table=custacc
 * @view=custacc_view
 * @keyfield=ca_id
 */
class CustAcc extends \ZCL\DB\Entity
{
     const TYPE_PAYMENT = 0; 
     const TYPE_BONUS = 1; 

    protected function init() {
        $this->customer_id = 0;
        $this->optype = 0;
        
    }
    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
    }
    
    public static  function addEntry($customer_id,$amount,$document_id,$optype=0){
         $custacc = new  CustAcc();
         $custacc->customer_id = $customer_id;
         $custacc->optype = $optype;
         $custacc->document_id = $document_id;
         $custacc->createdon=time() ;
     
         $custacc->save();
    }
}
