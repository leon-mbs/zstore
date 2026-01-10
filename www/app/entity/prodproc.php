<?php

namespace App\Entity;

use App\Helper;
use App\System;

/**
 * Класс-сущность  производственный процесс
 *
 * @table=prodproc
 * @view=prodproc_view
 * @keyfield=pp_id
 */
class ProdProc extends \ZCL\DB\Entity
{
    public const STATE_NEW       = 0;
    public const STATE_INPROCESS = 1;
    public const STATE_STOPPED   = 2;
    public const STATE_CANCELED  = 3;

    public const STATE_FINISHED  = 5;

    public $prodlist = array();

    protected function init() {
        $this->pp_id = 0;
        $this->state = 0;

    }

    //копирование процесса
    public function clone() {
        $proc = new ProdProc();
        $proc->detail = $this->detail;
        $proc->card = $this->card;
        $diff = time()- $this->startdateplan;
        if($diff<0) {
            $diff=0;
        }
        $proc->startdateplan = intval($this->startdateplan) + $diff;
        $proc->enddateplan = intval($this->enddateplan) + $diff;

        $proc->procname = $this->procname . "_copy";
        $proc->pp_id = 0;
        $proc->save();
        $stlist = ProdStage::find("pp_id=" . $this->pp_id);
        foreach ($stlist as $st) {
            $st->st_id = 0;
            $st->pp_id = $proc->pp_id;
            $st->startdateplan = $st->startdateplan + $diff;
            $st->enddateplan = $st->enddateplan + $diff;
         
            $st->save();
        }

        return $proc;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail>";
        $this->detail .= "<notes><![CDATA[{$this->notes}]]></notes>";
        $this->detail .= "<startdateplan>{$this->startdateplan}</startdateplan>";
        $this->detail .= "<enddateplan>{$this->enddateplan}</enddateplan>";

        $prodlist = base64_encode(serialize($this->prodlist));
        $this->detail .= "<prodlist>{$prodlist}</prodlist>";
  
        $this->detail .= "<store>{$this->store}</store>";


        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        if (strlen($this->detail) == 0) {
            return;
        }
        $this->startdate = strtotime($this->startdate);
        $this->enddate = strtotime($this->enddate);

        $xml = simplexml_load_string($this->detail);

        $this->notes = (string)($xml->notes[0]);
        $this->startdateplan = (string)($xml->startdateplan[0]);
        $this->enddateplan = (string)($xml->enddateplan[0]);
        $this->store = (int)($xml->store[0]);
       
        $this->prodlist = @unserialize(@base64_decode((string)($xml->prodlist[0])));
        if (!is_array($this->prodlist)) {
            $this->prodlist = array();
        }
   


        parent::afterLoad();
    }


    /**
     * Возвращает название  статуса  документа
     *
     * @param mixed $state
     * @return mixed
     */
    public static function getStateName($state) {

        switch($state) {
            case ProdProc::STATE_NEW:
                return "Новий";
            case ProdProc::STATE_INPROCESS:
                return "Виконується";
            case ProdProc::STATE_STOPPED:
                return "Припинено";
            case ProdProc::STATE_FINISHED:
                return "Виконаний";
            case ProdProc::STATE_CANCELED:
                return "Скасовано";


            default:
                return "Невідомий статус";
        }
    }


}
