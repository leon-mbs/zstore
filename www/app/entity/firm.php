<?php

namespace App\Entity;

/**
 * Класс-сущность  компания
 *
 * @table=firms
 * @keyfield=firm_id
 */
class Firm extends \ZCL\DB\Entity
{
    protected function init() {
        $this->firm_id = 0;
    }

    protected function afterLoad() {

        $xml = @simplexml_load_string($this->details);

        $this->address = (string)($xml->address[0]);
        $this->inn = (string)($xml->inn[0]);

        $this->phone = (string)($xml->phone[0]);

        $this->logo = (string)($xml->logo[0]);
        $this->stamp = (string)($xml->stamp[0]);
        $this->sign = (string)($xml->sign[0]);
        $this->tin = (string)($xml->tin[0]);
        $this->iban = (string)($xml->iban[0]);
        $this->payname = (string)($xml->payname[0]);
        $this->vdoc = (string)($xml->vdoc[0]);

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();

        $this->details = "<details>";
        $this->details .= "<address><![CDATA[{$this->address}]]></address>";
        $this->details .= "<logo><![CDATA[{$this->logo}]]></logo>";
        $this->details .= "<stamp><![CDATA[{$this->stamp}]]></stamp>";
        $this->details .= "<sign><![CDATA[{$this->sign}]]></sign>";
        $this->details .= "<inn>{$this->inn}</inn>";
        $this->details .= "<iban>{$this->iban}</iban>";
        $this->details .= "<vdoc>{$this->vdoc}</vdoc>";

        $this->details .= "<payname><![CDATA[{$this->payname}]]></payname>";
        $this->details .= "<phone>{$this->phone}</phone>";
        $this->details .= "<tin>{$this->tin}</tin>";
        $this->details .= "</details>";

        return true;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = " select count(*) from contracts where firm_id = {$this->firm_id} ";
        $cntc = $conn->GetOne($sql);
        $sql = " select count(*) from documents where firm_id = {$this->firm_id}  ";
        $cntd = $conn->GetOne($sql);
        return ($cntc > 0 || $cntd > 0) ? 'Не можна видаляти компанію, яка використовується' : "";
    }

    public static function getList() {
        return Firm::findArray("firm_name", "disabled <> 1", "firm_name");
    }

}
