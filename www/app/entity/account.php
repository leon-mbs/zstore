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
    * @param mixed $namewithcode    имя с  кодом
    * @param mixed $onlyman только доступные для  ручных проводок (без аналитики)
    */
    public static function getList($namewithcode =false,$onlyman=false) {
         $ret=[];
         $ret[10]='Основнi засоби';
         $ret[104]='Машини та обладнання';
         $ret[106]='Інструменти, прилади та інвентар';
       //  $ret[11]='Iншi необоротнi активи';
     //    $ret[112]='Малоцінні необоротні матеріальні активи';
         $ret[12]='Нематеріальні активи';
         $ret[13]='Знос необоротних активів';
         $ret[131]='Знос основних засобів';
         $ret[132]='Знос МНМА';
         $ret[15]='Капітальні інвестиції';
         $ret[20]='Виробничі запаси';
         $ret[201]='Сировина й матеріали';
       //  $ret[203]='Паливо';
         $ret[207]='Запчастини';
         $ret[22]='МШП';
         $ret[23]='Виробництво';
         $ret[25]='Напiвфабрикати';
         $ret[26]='Готова продукція';
         $ret[28]='Товари';
         $ret[30]='Каса';
         $ret[31]='Банк';
         $ret[36]='Розрахунки з покупцями';
         $ret[37]='Розрахунки з рiзними дебiторами';
    //     $ret[371]='Розрахунки за виданими авансами';
         $ret[372]='Розрахунки з пiдзвiтними особами';
         $ret[40]='Власний капiтал';
         $ret[44]='Нерозподiлений прибуток';
         $ret[63]='Розрахунки з постачальниками';
         $ret[64]='Розрахунки за податками й платежами';
         $ret[641]='Розрахунки за податками';
         $ret[642]='Розрахунки за платежами';
         $ret[643]='Податкові зобов’язання';
         $ret[644]='Податковий кредит';
         $ret[65]='Розрахунки за страхуванням';
         $ret[66]='Розрахунки по заробiтнiй платi';
         $ret[68]='Розрахунки за iншми операцiями';
    //     $ret[681]='Розрахунки за отриманими авансами';
         $ret[70]='Доходи від реалізації';
 
         $ret[71]='Доходи операційної діяльності';
         $ret[79]='Фінансові результати';
         $ret[90]='Собівартість реалізації';
 
         $ret[91]='Загальновиробничі витрати';
         $ret[92]='Адміністративні витрати';
         $ret[93]='Витрати на збут';
         $ret[94]='Інші витрати операційної діяльності';
         $ret[941]='Накладнi витрати';
         $ret[942]='Отриманi послуги';
         $ret[943]='Комуналка';
         $ret[944]='Аренда';
         $ret[947]='Недостачі та втрати від псування цінностей';
         $ret[949]='Iншi витрати операційної діяльності';
         $ret[97]='Iншi витрати';
         $ret[99]='Податок на прибуток';
        
         
         if($onlyman) { 
            $man=[40,44,641,642,643,644,65,704,71,79,904,94,941,943,944,97,99]; 
            $retc=[]; 
            foreach($ret as $c=>$n) {
                
               if(in_array($c,$man) ){
                   $retc[$c] = $n   ; 
               }
              
            }
            $ret =  $retc;       
         }
         if($namewithcode) { 
            $retc=[]; 
            foreach($ret as $c=>$n) {
               $retc[$c] = $c.' '. $n  ;
            }
            $ret =  $retc;
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
    public static function getOb($acc,$from, $to) {
        $conn = \ZDB\DB::getConnect();
 
        $acc = $conn->qstr($acc.'%');
        
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
    public static function getSaldo($acc,$date, $ext=false) {
        $conn = \ZDB\DB::getConnect();
 
        $acc = $conn->qstr($acc.'%');
        
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
 
 
     /**
    * счет  учета  ТМЦ по типу
    *  
    */
    public static  function getAccCode(){
            $list=[];
            $list[0]='28'; 
            $list[\App\Entity\Item::TYPE_TOVAR]='28'; 
            $list[\App\Entity\Item::TYPE_MAT]='201'; 
            $list[\App\Entity\Item::TYPE_MBP]='22'; 
            $list[\App\Entity\Item::TYPE_PROD]='26'; 
            $list[\App\Entity\Item::TYPE_HALFPROD]='25'; 
            return $list;
    
    }
   
   
}
