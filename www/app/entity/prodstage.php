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
        $this->detail .= "<beznal>{$this->beznal}</beznal>";
        $this->detail .= "<btran>{$this->btran}</btran>";
        $this->detail .= "<btranin>{$this->btranin}</btranin>";
        $this->detail .= "<bank><![CDATA[{$this->bank}]]></bank>";
        $this->detail .= "<bankacc><![CDATA[{$this->bankacc}]]></bankacc>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        if (strlen($this->detail) == 0) {
            return;
        }

        $xml = simplexml_load_string($this->detail);
        $this->beznal = intval($xml->beznal[0]);
        $this->btran = floatval($xml->btran[0]);
        $this->btranin = floatval($xml->btranin[0]);
        $this->bank = (string)($xml->bank[0]);
        $this->bankacc = (string)($xml->bankacc[0]);

        parent::afterLoad();
    }

 
}
