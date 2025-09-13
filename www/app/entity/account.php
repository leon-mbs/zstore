<?php

namespace App\Entity;

/**
 * Клас-сущность  бухгалтерский  счет
 *
 */
class Account  
{
  
    /**
     * список  счетов
     *
     */
    public static function getList($namewithcode =false,$onlyman=false) {
         $ret=[];
         $ret[23]='Виробництво';
         $ret[40]='Статутний капiтал';
         $ret[79]='Фiнансовий результат';
         
         if($namewithcode) { 
            $retc=[]; 
            foreach($ret as $c=>$n) {
               $retc[$c] = $c.' '. $n  ;
            }
            return $retc;
         }
         
         
         return $ret;
    }
 
    /**
     * список использованых счетов
     *
     */
    public static function getUsedList($namewithcode =false ) {
           $all=self::getList($namewithcode) ;
           $ret=[];
           $conn = \ZDB\DB::getConnect();
           $col=$conn->GetCol("select distinct  accdt from acc_entry");
           foreach($col  as $code){
              if(!isset($ret[$code])) {
                 $ret[$code]  = $all[$code]  ;
              }
           }
           $col=$conn->GetCol("select distinct  accct from acc_entry");
           foreach($col as $code){
              if(!isset($ret[$code])) {
                 $ret[$code]  = $all[$code]  ;
              }
           }  
           
           
           return $ret;         
    }
 

   
    /**
    * обороты за период
    * 
    * @param mixed $from
    * @param mixed $to
    */
    public function getOb($from, $to) {
        $conn = \ZDB\DB::getConnect();
 
        $acc = $conn->qstr($this->acc_code.'%');
        
        $from = $conn->DBDate($from)  ;
        $to = $conn->DBDate($to)  ;
            
        $w = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $w .= " and " . $c;
        }        
        $sql = "select coalesce(sum(amount),0) from  acc_entry_view where accdt like {$acc} and createdon >= {$from} and createdon <= {$to}   {$w}";
        $obdt = $conn->GetOne($sql);
        $sql = "select coalesce(sum(amount),0) from  acc_entry_view where accct like {$acc} and createdon >= {$from} and createdon <= {$to}  {$w}" ;
        $obct = $conn->GetOne($sql);
        
        return ['dt'=>$obdt,'ct'=>$obct];
    }

    /**
    * сальдо на  дату
    * 
    * @param mixed $date
    * @param mixed $ext   развернутое
    */
    public function getSaldo($date, $ext=false) {
        $conn = \ZDB\DB::getConnect();
 
        $acc = $conn->qstr($this->acc_code.'%');
        
        $date = $conn->DBDate($date)  ;
            
        $w = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $w .= " and " . $c;
        }        
        $sql = "select coalesce(sum(amount),0) from  acc_entry_view where accdt like {$acc} and createdon <= {$date} {$w}";
        $startdt = $conn->GetOne($sql);
        $sql = "select coalesce(sum(amount),0) from  acc_entry_view where accct like {$acc} and createdon <= {$date} {$w}" ;
        $startct = $conn->GetOne($sql);
        
        
        if($ext)  {
            return ['dt'=>$startdt,'ct'=>$startct];
        }
        
        //сворачиваем
        if($startdt > $startct) {
           $startdt = $startdt- $startct; 
           return ['dt'=>$startdt,'ct'=>0];
         
        }   else {
           $startct = $startct- $startdt; 
         
           return ['dt'=>0,'ct'=>$startct];
            
        }
        
    
    }

 
 
  

    /**
    * оборот между счетами
    * 
    * @param mixed $acc_dt
    * @param mixed $acc_ct
    * @param mixed $from
    * @param mixed $to
    * @param mixed $bc
    */
    public static function getObBetweenAccount($acc_dt, $acc_ct, $from, $to ) {
        $w = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $w .= " and " . $c;
        }  

        $conn = \ZDB\DB::getConnect();
        $acc_dt = $conn->qstr($acc_dt . '%');
        $acc_ct = $conn->qstr($acc_ct . '%');

        $sql = "select coalesce(sum(amount),0) from   acc_entry_view where {$w}  accdt like {$acc_dt} and  accct like {$acc_ct}   and createdon  >= " . $conn->DBDate($from) . " and createdon <= " . $conn->DBDate($to);

        return $conn->GetOne($sql) ;
    }

    /**
    * обороты  по  счету по документу
    * 
    * @param mixed $acc_code
    * @param mixed $doc_id
    */
    public static function getObDoc($acc_code,$doc_id) {
        $conn = \ZDB\DB::getConnect();
 
        $acc = $conn->qstr($acc_code.'%');
  
        
        $sql = "select coalesce(sum(amount),0) from  acc_entry_view where accdt like {$acc} and document_id=".$doc_id;
        $obdt = $conn->GetOne($sql);
        $sql = "select coalesce(sum(amount),0) from  acc_entry_view where accct like {$acc} and document_id=".$doc_id;
        $obct = $conn->GetOne($sql);
        
        return ['dt'=>$obdt,'ct'=>$obct];
    }
    
    //счета  с  проводками
    public  static  function getWithEntry() {
        $list=[];
        $conn = \ZDB\DB::getConnect();
        $sql = "  select distinct accct from  acc_entry_view where  document_id >0";
        foreach($conn->getCol($sql) as $acc ){
           $list[$acc]=$acc; 
        } 
   
        $sql = "  select distinct accdt from  acc_entry_view where  document_id >0";
        foreach($conn->getCol($sql) as $acc ){
           $list[$acc]=$acc; 
        } 
        return array_keys($list);
    }    
   
}
