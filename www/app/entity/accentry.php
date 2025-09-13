<?php

namespace App\Entity;

/**
 * Клас-сущность  бухгалтерская проводка
 *
 * @view=acc_entry_view
 * @table=acc_entry
 * @keyfield=id
 */
class AccEntry extends \ZCL\DB\Entity
{
    protected function init() {
       $this->id=0;
    }

    protected function afterLoad() {

        $this->createdon = strtotime($this->createdon);
    }

   /**
   * добавить  проводку
   * 
   * @param mixed $dt
   * @param mixed $ct
   * @param mixed $amount
   * @param mixed $doc_id
   * @param mixed $date   задается  если  отличается  от  документа
   */
   public static function addEntry($dt,$ct,$amount,$doc_id,$date=0) {
        if($amount==0) {
            return;
        } 

       
        if(strlen($dt ?? '')==0 && strlen($ct ?? '')==0) {
            return;
        } 
        if($dt==$ct) {
            return;
        } 
              
              
        $en = new AccEntry();
        $en->document_id = $doc_id;
        $en->amount = $amount;
        if(strlen($dt ?? '')>0) {
            $en->accdt = $dt;
        }
        if(strlen($ct ?? '')>0) {
            $en->accct = $ct;
        }
        if($date>0) {
            $en->createdon = $date;
        }
        $en->save();
    }
  
   /**
   * ограничение на  документы
   * 
   */
   public static function getDocConstraint( ) {
        $user = \App\System::getUser();

        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $where .= " and " . $c;
        }

        if ($user->rolename != 'admins') {
            if ($user->onlymy == 1) {

                $where .= " and d.user_id  = " . $user->user_id;
            }

            $where .= " and d.meta_id in({$user->aclview}) ";
        } 
     }
}
