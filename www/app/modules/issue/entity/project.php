<?php

namespace App\Modules\Issue\Entity;

/**
 *  Класс  инкапсулирующий   проект
 * @table=issue_projectlist
 * @view=issue_projectlist_view
 * @keyfield=project_id
 */
class Project extends \ZCL\DB\Entity {

    protected function init() {
        $this->project_id = 0;
        $this->archived = 0;
    }

    protected function beforeDelete() {

        return '';
    }

    protected function afterDelete() {

        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from issue_issuelist where   project_id=" . $this->project_id);
        $conn->Execute("delete from messages where item_type=" . \App\Entity\Message::TYPE_PROJECT . " and item_id=" . $this->project_id);
        $conn->Execute("delete from files where item_type=" . \App\Entity\Message::TYPE_PROJECT . " and item_id=" . $this->project_id);
        $conn->Execute("delete from filesdata where   file_id not in (select file_id from files)");
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные  
        $this->details = "<details>";
        $this->details .= "<desc><![CDATA[{$this->desc}]]></desc>";

        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {

        $this->lastupdate = strtotime($this->lastupdate);

        //распаковываем  данные из  
        $xml = simplexml_load_string($this->details);
        $this->desc = (string) ($xml->desc[0]);
        parent::afterLoad();
    }

}
