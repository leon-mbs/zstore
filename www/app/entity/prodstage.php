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
        $this->detail .= "<notes><![CDATA[{$this->notes}]]></notes>";

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
        $this->hours = intval($xml->hours[0]);
        $this->notes = (string)($xml->notes[0]);

        parent::afterLoad();
    }

 
}
