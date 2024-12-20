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
        $this->detail .= "<datein>{$this->datein}</datein>";
        $this->detail .= "<dateout>{$this->dateout}</dateout>";
           
        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        $this->resemp_id = (int)($xml->resemp_id[0]);
        $this->datein = (int)($xml->datein[0]);
        $this->dateout = (int)($xml->dateout[0]);
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

    
    
}
