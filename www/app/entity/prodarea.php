<?php

namespace App\Entity;

/**
 * Класс-сущность  производственный участок
 *
 * @table=parealist
 * @keyfield=pa_id
 */
class ProdArea extends \ZCL\DB\Entity
{
    protected function init() {
        $this->pa_id = 0;
    }
    public static function getConstraint() {
        $br = \App\ACL::getBranchConstraint();
        if (strlen($br) > 0) {
            $br = " (" . $br . " or coalesce(branch_id,0)=0)  ";
        }   
        return $br;
    }
    
    //todo
    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  documents where   user_id = {$this->user_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Не можна  видаляти користувача з документами" : '';
    }  
    
  
      
}    
 
