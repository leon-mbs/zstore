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
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<details>";

        $this->details .= "<comment><![CDATA[{$this->comment}]]></comment>";

        $this->details .= "<pricetype>{$this->pricetype}</pricetype>";
        $this->details .= "<mf>{$this->mf}</mf>";
        $this->details .= "<store>{$this->store}</store>";
        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);

        $this->mf = (int)($xml->mf[0]);
        $this->pricetype = (string)($xml->pricetype[0]);
        $this->store = (int)($xml->store[0]);
        $this->comment = (string)($xml->comment[0]);

        parent::afterLoad();
    }

    public static function getConstraint() {
        return \App\ACL::getBranchConstraint();
    }

}
