<?php

namespace App\Entity;

/**
 * Класc-сущность   тип начислений удержаний
 *
 * @table=queue
 * @keyfield=id
 */
class Queue extends \ZCL\DB\Entity
{
    protected function init() {

        $this->id = 0;
        $this->created = time();

    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->details = "<details>";
        $this->details .= "<type>{$this->type}</type>";
        $this->details .= "<data><![CDATA[". serialize($this->data ?? [])  ."]]></data>";
        $this->details .= "</details>";

        return true;
    }
    
    protected function afterLoad() {

        $this->created = strtotime($this->created);

        $xml = @simplexml_load_string($this->details);
  
        $this->data = unserialize(  (string)($xml->data[0]) );
        $this->type = (int)($xml->type[0]);

        parent::afterLoad();
    }   
}
