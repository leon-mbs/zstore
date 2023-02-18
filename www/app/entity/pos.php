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

        $this->details .= "<fiscalnumber>{$this->fiscalnumber}</fiscalnumber>";
        $this->details .= "<fiscallocnumber>{$this->fiscallocnumber}</fiscallocnumber>";
        $this->details .= "<fiscdocnumber>{$this->fiscdocnumber}</fiscdocnumber>";

        $this->details .= "<usefisc>{$this->usefisc}</usefisc>";
        $this->details .= "<testing>{$this->testing}</testing>";
        $this->details .= "<firm_id>{$this->firm_id}</firm_id>";
        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);

        $this->comment = (string)($xml->comment[0]);
        $this->address = (string)($xml->address[0]);
        $this->pointname = (string)($xml->pointname[0]);
        $this->fiscalnumber = (string)($xml->fiscalnumber[0]);
        $this->fiscallocnumber = (int)($xml->fiscallocnumber[0]);
        $this->fiscdocnumber = (int)($xml->fiscdocnumber[0]);
        $this->firm_id = (int)($xml->firm_id[0]);

        $this->testing = (int)($xml->testing[0]);
        $this->usefisc = (int)($xml->usefisc[0]);
        if (strlen($this->fiscdocnumber) == 0) {
            $this->fiscdocnumber = 1;
        }
        parent::afterLoad();
    }

    public static function getConstraint() {
        return \App\ACL::getBranchConstraint();
    }

}
