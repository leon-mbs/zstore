<?php

namespace App\Entity;

/**
 * Клас-сущность  оборудование
 *
 * @table=equipments
 * @view=equipments_view
 * @keyfield=eq_id
 */
class Equipment extends \ZCL\DB\Entity
{
    public const IYPR_EQ = 1;
    public const IYPR_OS = 2;
    public const IYPR_NMA = 3;
      
    
    
    protected function init() {
        $this->eq_id = 0;
        $this->branch_id = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail><resemp_id>{$this->resemp_id}</resemp_id>";
        $this->detail .= "<serial>{$this->serial}</serial>";
            
        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        $this->resemp_id = (int)($xml->resemp_id[0]);
        $this->serial = (string)($xml->serial[0]);
 
     
        parent::afterLoad();
    }

  
   
 
    public static function getConstraint() {
        $br = \App\ACL::getBranchConstraint();
        if (strlen($br) > 0) {
            $br = " (" . $br . " or coalesce(branch_id,0)=0)  ";
        }   
        return $br;
    }

    public static function getTypeName(int $t){
        if($t==self::IYPR_EQ) return 'Обладнання' ;
        if($t==self::IYPR_OS) return 'Основні фонди' ;
        if($t==self::IYPR_NMA) return 'Нематеріальні активи' ;
        return 'N/A' ;
    }
    

    //todo
    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  documents where   user_id = {$this->user_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Не можна  видаляти користувача з документами" : '';
    }   
    
    
    public static function getList($search = '',$eq=false ) {

        $where = " disabled <> 1 ";
        if (strlen($search) > 0) {
            $search = Equipment::qstr('%' . $search . '%');
            $where .= " and  eq_name like {$search}   ";
        }
        if($eq){   //оборудование
           $where .= " and type = ".Equipment::IYPR_EQ;
          
        }
        
         $ret=[];
         foreach(Equipment::find( $where, "eq_name")  as $e ){
             $ret[$e->eq_id] = $e->eq_name. ', ' . $e->invnumber;
         }
         
         return $ret;
    }    
     
}
