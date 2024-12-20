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
        $this->detail = "<detail><emp_id>{$this->emp_id}</emp_id>";
        $this->detail .= "<serial>{$this->serial}</serial>";
      //  $this->detail .= "<code>{$this->code}</code>";
        $this->detail .= "<balance>{$this->balance}</balance>";
        $this->detail .= "<eq>{$this->eq}</eq>";
    //  $this->detail .= "<pa_id>{$this->pa_id}</pa_id>";
        $this->detail .= "<enterdate>{$this->enterdate}</enterdate>";
        $this->detail .= "<bh>". serialize($this->bh) ."</bh>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        $this->emp_id_old = (int)($xml->emp_id[0]);
        $this->serial = (string)($xml->serial[0]);
        $this->code = (string)($xml->code[0]);
        $this->balance = (string)($xml->balance[0]);
        $this->enterdate = (int)($xml->enterdate[0]);
        $this->eq = (int)($xml->eq[0]);
        $this->pa_id_old = (int)($xml->pa_id[0]);
        $this->bh = @unserialize( (string)($xml->bh[0]) );
        if(!is_array($this->bh)) {
            $this->bh=[] ;
        }
        parent::afterLoad();
    }

    //возвращает  оборудование для выпадающих списков
    public static function getQuipment() {
        $list = array();
        foreach (Equipment::find("disabled<>1 and detail like'%<eq>1</eq>%' ", "eq_name") as $eq) {
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

    public  function getBalance($tm=0) {
        if(count( $this->bh)==0) {
           return $this->balance;
        }
        $keys= array_keys($this->bh) ;
        sort($keys) ;
        
        foreach($keys as $i){
            
            if($i >$tm){
               break; 
            }
            $this->balance = $this->bh[$i] ;
        }
        return $this->balance;
    }
    public  function setBalance($am) {
      $this->bh[time()] = $am;
      $this->balance = $am;
    }
    
}
