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
    public const TAG_NDS = 1;

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
   public static function addEntry($dt,$ct,$amount,$doc_id, $date=0, $tagdt=0, $tagct=0) {
        if($amount==0) {
            return;
        } 

       
        if(strlen($dt ?? '')==0 && strlen($ct ?? '')==0) {
            return;
        } 
        if($dt==$ct) {
            return;
        } 
        
        $acclist= Account::getList();
        $numlist = array_keys($acclist) ;
   
        if(!in_array(intval($dt),$numlist) ) {
            throw new \Exception("Невiрний рахунок ".$dt);
        }            
        if(!in_array(intval($ct),$numlist) ) {
            throw new \Exception("Невiрний рахунок ".$ct);
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
        if($tagdt>0) {
            $en->tagdt = $tagdt;
        }
        if($tagdt>0) {
            $en->tagct = $tagct;
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
