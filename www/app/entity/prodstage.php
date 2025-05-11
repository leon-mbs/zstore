<?php

namespace App\Entity;

use App\Helper;

/**
 * Класс-сущность  производственный этап
 *
 * @table=prodstage
 * @view=prodstage_view
 * @keyfield=st_id
 */
class ProdStage extends \ZCL\DB\Entity
{
    public const STATE_NEW       = 0;
    public const STATE_INPROCESS = 1;
    public const STATE_STOPPED   = 2;

    public const STATE_FINISHED  = 4;

    protected function init() {
        $this->st_id = 0;
        $this->state = 0;

    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail>";
        $this->detail .= "<notes><![CDATA[{$this->notes}]]></notes>";
        $this->detail .= "<card><![CDATA[{$this->card}]]></card>";
        $this->detail .= "<empids><![CDATA[{$this->empids}]]></empids>";
 
        $emplist = base64_encode(serialize($this->emplist));
        $this->detail .= "<emplist>{$emplist}</emplist>";
        $itemlist = base64_encode(serialize($this->itemlist));
        $this->detail .= "<itemlist>{$itemlist}</itemlist>";
     
        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
     

        if (strlen($this->detail) == 0) {
            return;
        }

        $xml = simplexml_load_string($this->detail);
        $this->notes = (string)($xml->notes[0]);
        $this->card = (string)($xml->card[0]);
        $this->empids = (string)($xml->empids[0]);
      
        $this->emplist = @unserialize(@base64_decode((string)($xml->emplist[0])));
        if (!is_array($this->emplist)) {
            $this->emplist = array();
        }
        $this->itemlist = @unserialize(@base64_decode((string)($xml->itemlist[0])));
        if (!is_array($this->itemlist)) {
            $this->itemlist = array();
        }

        parent::afterLoad();
    }

    public static function getStateName($state) {

        switch($state) {
            case ProdStage::STATE_NEW:
                return "Новий";
            case ProdStage::STATE_INPROCESS:
                return "Виконується";
            case ProdStage::STATE_FINISHED:
                return "Виконаний";
            case ProdStage::STATE_STOPPED:
                return "Зупинений";


            default:
                return "Невідомий статус";
        }
    }
}
