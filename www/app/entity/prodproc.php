<?php

namespace App\Entity;

use App\Helper;
use App\System;

/**
 * Класс-сущность  производственный процесс
 *
 * @table=prodproc
 * @keyfield=pp_id
 */
class ProdProc extends \ZCL\DB\Entity
{

    const STATE_NEW=0;
    const STATE_INPROCESS=1;
    const STATE_STOPPED=2;
    const STATE_FINISHED=3;
    const STATE_CANCELED=4;
    
    public $prodlist = array();
    
    protected function init() {
        $this->pp_id = 0;
  
    }
    public function clone() {
        $proc = new ProdProc();
         
        return  $proc;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail>";
        $this->detail .= "<notes><![CDATA[{$this->notes}]]></notes>";
        $prodlist = base64_encode( serialize($this->prodlist) );
        $this->detail .= "<prodlist><{$prodlist}></prodlist>";
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
        $prodlist = @unserialize(@base64_decode((string)($xml->prodlist[0])));
        if(!is_array($prodlist)) $prodlist = array();
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
                return Helper::l('stpp_new');
            case ProdProc::STATE_INPROCESS:
                return Helper::l('stpp_inprocess');
            case ProdProc::STATE_STOPPED:
                return Helper::l('stpp_stopped');
            case ProdProc::STATE_FINISHED:
                return Helper::l('stpp_finished');
            case ProdProc::STATE_CANCELED:
                return Helper::l('stpp_canceled');
            

            default:
                return Helper::l('st_unknow');
        }
    }  
 
    
 
}
