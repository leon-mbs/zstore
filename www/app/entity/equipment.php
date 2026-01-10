<?php

namespace App\Entity;

/**
 * Клас-сущность  оборудование
 *
 * @table=equipments
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
        $this->resemp_id = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail><resemp_id>{$this->resemp_id}</resemp_id>";
        $this->detail .= "<resemp_name>{$this->resemp_name}</resemp_name>";
        $this->detail .= "<serial>{$this->serial}</serial>";
            
        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        $this->resemp_id = (int)($xml->resemp_id[0]);
        $this->serial = (string)($xml->serial[0]);
        $this->resemp_name = (string)($xml->resemp_name[0]);
 
     
        parent::afterLoad();
    }

  
   
 
    public static function getConstraint() {
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $c = " (" . $c . " or coalesce(branch_id,0)=0)  ";
        }   
        
        
        $user = \App\System::getUser();
       
        if ($user->rolename != 'admins') {
            if (strlen($c) == 0) {
                $c = "1=1 ";
            }
      
            if (strlen($user->aclview) > 0) {
                $c .= " and detail like '%<resemp_id>{$user->employee_id}</resemp_id>%' ";
            }  
        }        
        
        
        return $c;
    }

    public static function getTypeName(int $t){
        if($t==self::IYPR_EQ) return 'Обладнання' ;
        if($t==self::IYPR_OS) return 'Основні фонди' ;
        if($t==self::IYPR_NMA) return 'Нематеріальні активи' ;
        return 'N/A' ;
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

    public  function getBalance( ) {
        $conn = \ZDB\DB::getConnect();
        $sql = "  select sum(amount)  from  eqentry where   eq_id =   {$this->eq_id}   ";
        return doubleval( $conn->GetOne($sql) );
  
    }   
}
