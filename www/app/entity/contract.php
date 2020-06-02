<?php

namespace App\Entity;

/**
 * Клас-сущность  договор
 *
 * @table=contracts
 * @view=contracts_view
 * @keyfield=contract_id
 */
class Contract extends \ZCL\DB\Entity
{

    protected function init() {
        $this->contract_id = 0;
        $this->createdon = time();
    }

    protected function afterLoad() {

        $this->createdon = strtotime($this->createdon);
        
        $xml = @simplexml_load_string($this->details);

        $this->shortdesc = (string)($xml->shortdesc[0]);
        $this->payname = (string)($xml->payname[0]);
        $this->pay = (int)($xml->pay[0]);
        $this->file_id = (int)($xml->file_id[0]);

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->details = "<details>";
        //упаковываем  данные  
        $this->details .= "<shortdesc><![CDATA[{$this->shortdesc}]]></shortdesc>";
        $this->details .= "<payname><![CDATA[{$this->payname}]]></payname>";
        $this->details .= "<pay>{$this->pay}</pay>";
        $this->details .= "<file_id>{$this->file_id}</file_id>";
        $this->details .= "</details>";

        return true;
    }

    public  static  function PayList(){
        return array(
            1=>\App\Helper::l('cnal'),
            2=>\App\Helper::l('cbeznal'),
            3=>\App\Helper::l('ckredit'),
            4=>\App\Helper::l('creal')
        );
    }
}
