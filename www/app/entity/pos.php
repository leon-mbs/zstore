<?php

namespace App\Entity;

/**
 * Клас-сущность  терминал
 *
 * @table=poslist
 * @keyfield=pos_id
 */
class Pos extends \ZCL\DB\Entity {

    protected function init() {
        $this->pos_id = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<details>";

        $this->details .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->details .= "<address><![CDATA[{$this->address}]]></address>";
        $this->details .= "<phone><![CDATA[{$this->phone}]]></phone>";
        $this->details .= "<ip><![CDATA[{$this->ip}]]></ip>";
        $this->details .= "<sip><![CDATA[{$this->sip}]]></sip>";
        $this->details .= "<viber><![CDATA[{$this->viber}]]></viber>";
        $this->details .= "<tele><![CDATA[{$this->tele}]]></tele>";
        $this->details .= "<sup><![CDATA[{$this->sup}]]></sup>";
        $this->details .= "<email><![CDATA[{$this->email}]]></email>";
        $this->details .= "<pricetype>{$this->pricetype}</pricetype>";
        $this->details .= "<mf>{$this->mf}</mf>";
        $this->details .= "<store>{$this->store}</store>";
        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);

        $this->mf = (int) ($xml->mf[0]);
        $this->pricetype = (int) ($xml->pricetype[0]);
        $this->store = (int) ($xml->store[0]);
        $this->address = (string) ($xml->address[0]);
        $this->phone = (string) ($xml->phone[0]);
        $this->ip = (string) ($xml->ip[0]);
        $this->sip = (string) ($xml->sip[0]);
        $this->viber = (string) ($xml->viber[0]);
        $this->tele = (string) ($xml->tele[0]);
        $this->sup = (string) ($xml->sup[0]);
        $this->email = (string) ($xml->email[0]);
        $this->comment = (string) ($xml->comment[0]);

        parent::afterLoad();
    }

    public static function getConstraint() {
        return \App\ACL::getBranchConstraint();
    }

}
