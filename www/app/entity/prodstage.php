<?php

namespace App\Entity;

/**
 * Класс-сущность  производственный этап
 *
 * @table=prodstage
 * @view=prodstage_view
 * @keyfield=st_id
 */
class ProdStage extends \ZCL\DB\Entity
{


    protected function init() {
        $this->st_id = 0;
         
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail>";
        $this->detail .= "<hours>{$this->hours}</hours>";
        $this->detail .= "<salary>{$this->salary}</salary>";
        $this->detail .= "<notes><![CDATA[{$this->notes}]]></notes>";
        $this->detail .= "<card><![CDATA[{$this->card}]]></card>";
        $emplist = base64_encode( serialize($this->emplist) );
        $this->detail .= "<emplist>{$emplist}</emplist>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
         $this->startdate = strtotime($this->startdate);
         $this->enddate = strtotime($this->enddate);
    
        
        
        if (strlen($this->detail) == 0) {
            return;
        }

        $xml = simplexml_load_string($this->detail);
        $this->hours = doubleval($xml->hours[0]);
        $this->salary = intval($xml->salary[0]);
        $this->notes = (string)($xml->notes[0]);
        $this->card = (string)($xml->card[0]);

        $this->emplist = @unserialize(@base64_decode((string)($xml->emplist[0])));
        if(!is_array($this->emplist)) $this->emplist = array();
        
        parent::afterLoad();
    }

 }        
