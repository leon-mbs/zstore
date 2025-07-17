<?php

namespace App\Entity;

/**
 * Клас-сущность  терминал
 *
 * @table=poslist
 * @keyfield=pos_id
 */
class Pos extends \ZCL\DB\Entity
{
    protected function init() {
        $this->pos_id = 0;
        $this->fiscalnumber = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<details>";

        $this->details .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->details .= "<address><![CDATA[{$this->address}]]></address>";
        $this->details .= "<pointname><![CDATA[{$this->pointname}]]></pointname>";
        $this->details .= "<payeq><![CDATA[{$this->payeq}]]></payeq>";

        $this->details .= "<fiscalnumber>{$this->fiscalnumber}</fiscalnumber>";
        $this->details .= "<fiscallocnumber>{$this->fiscallocnumber}</fiscallocnumber>";
        $this->details .= "<fiscdocnumber>{$this->fiscdocnumber}</fiscdocnumber>";
        $this->details .= "<firmname>{$this->firmname}</firmname>";
        $this->details .= "<tin>{$this->tin}</tin>";
        $this->details .= "<ipn>{$this->ipn}</ipn>";

        $this->details .= "<usefisc>{$this->usefisc}</usefisc>";
        $this->details .= "<testing>{$this->testing}</testing>";

        $this->details .= "<vktoken>{$this->vktoken}</vktoken>";
        $this->details .= "<cbkey>{$this->cbkey}</cbkey>";
        $this->details .= "<cbpin>{$this->cbpin}</cbpin>";
        $this->details .= "<autoshift>{$this->autoshift}</autoshift>";
        $this->details .= "<ppoowner><![CDATA[{$this->ppoowner}]]></ppoowner>";
        $this->details .= "<ppocert><![CDATA[{$this->ppocert}]]></ppocert>";
        $this->details .= "<ppokey><![CDATA[{$this->ppokey}]]></ppokey>";
        $this->details .= "<ppopassword>{$this->ppopassword}</ppopassword>";
       
        $this->details .= "<ppokeyid>{$this->ppokeyid}</ppokeyid>";
        $this->details .= "<ppoisjks>{$this->ppoisjks}</ppoisjks>";
        
        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);

        $this->comment = (string)($xml->comment[0]);
        $this->address = (string)($xml->address[0]);
        $this->pointname = (string)($xml->pointname[0]);
        $this->payeq = (string)($xml->payeq[0]);
        $this->vktoken = (string)($xml->vktoken[0]);
        $this->cbkey = (string)($xml->cbkey[0]);
        $this->cbpin = (string)($xml->cbpin[0]);
        $this->fiscalnumber = (string)($xml->fiscalnumber[0]);
        $this->fiscallocnumber = (int)($xml->fiscallocnumber[0]);
        $this->fiscdocnumber = (int)($xml->fiscdocnumber[0]);
        $this->firmname = (string)($xml->firmname[0]);
        $this->tin = (string)($xml->tin[0]);
        $this->ipn = (string)($xml->ipn[0]);

        $this->autoshift = (int)($xml->autoshift[0]);

        $this->testing = (int)($xml->testing[0]);
        $this->usefisc = (int)($xml->usefisc[0]);
        if (strlen(''.$this->fiscdocnumber ) == 0) {
            $this->fiscdocnumber = 1;
        }
        
        $this->ppoowner = (string)($xml->ppoowner[0]);
        $this->ppokey = (string)($xml->ppokey[0]);
        $this->ppocert = (string)($xml->ppocert[0]);
        $this->ppopassword = (string)($xml->ppopassword[0]);
        $this->ppoisjks = (int)($xml->ppoisjks[0]);
        $this->ppokeyid = (string)($xml->ppokeyid[0]);
  
        
        parent::afterLoad();
    }

    public static function getConstraint() {
        return \App\ACL::getBranchConstraint();
    }
    protected function beforeDelete() {

        $cnt= \App\Entity\Doc\Document::findCnt("content like '%<pos>{$this->pos_id}</pos>%'") ;


        if($cnt >0) {
            return "Термiнал вже використаний в чеках";
        }

        $st = \App\Modules\PPO\PPOHelper::rroState($this->fiscalnumber, $this) ;
        if($st['ShiftState'] ==1) {
            return "Вiдкрита змiна";
        }

        return "";
    }



}
