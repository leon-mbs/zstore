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
      
    
    public const OP_INCOME = 1;
    public const OP_OUTCOME = 2;
    public const OP_BAL = 3;
    public const OP_MOVE = 4;
      
    
    private $bh=[]; 
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

    //возвращает  оборудование для выпадающих списков
    public static function getQuipment() {
        $list = array();
        foreach (Equipment::find("disabled<>1 and type=1 ", "eq_name") as $eq) {
            $list[$eq->eq_id] = $eq->eq_name;
            if (strlen($eq->serial) > 0) {
                $list[$eq->eq_id] = $eq->eq_name . ', ' . $eq->serial;
            }


        }
        return $list;
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
    
    public static function getOpName(int $t){
        if($t==self::OP_INCOME) return 'Ввод в  експлуатацію' ;
        if($t==self::OP_OUTCOME) return 'Виведення з експлуатації' ;
        if($t==self::OP_BAL) return 'Зміна балансової вартості' ;
        if($t==self::OP_MOVE) return 'Переміщення' ;
        return 'N/A' ;
   }
    
}
