<?php

namespace App\Entity;

/**
 * Класс-сущность  подписка  на  событие
 *
 * @table=subscribes
 * @keyfield=sub_id
 */
class Subscribe extends \ZCL\DB\Entity
{
    const TYPE_DOCSTATE =1;

    protected function init() {
        $this->cat_id = 0;
    }

 

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->docmetaname = (string)($xml->docmetaname[0]);
        $this->docstate = (int)($xml->docstate[0]);
      


        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();

        $this->detail = "<detail>";

        $this->detail .= "<docmetaname>{$this->docmetaname}</docmetaname>";
        $this->detail .= "<docstate>{$this->docstate}</docstate>";
   


        $this->detail .= "</detail>";

        return true;
    }

    public  static  function onDocumentState($metaname,$state) {
       
       $list = self::find('disabled <> 1 and sub_type= '. self::TYPE_DOCSTATE) ;
       
        
    }
    
}
