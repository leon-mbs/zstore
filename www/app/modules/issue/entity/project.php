<?php

namespace App\Modules\Issue\Entity;

/**
 *  Класс  инкапсулирующий   проект
 * @table=issue_projectlist
 * @view=issue_projectlist_view
 * @keyfield=project_id
 */
class Project extends \ZCL\DB\Entity
{

    const STATUS_NEW         = 1;
    const STATUS_INPROCESS   = 2;
    const STATUS_REOPENED    = 3;
    const STATUS_WA          = 4;
    const STATUS_SHIFTED     = 5;
    const STATUS_WAITPAIMENT = 6;
    const STATUS_CLOSED      = 12;

    protected function init() {
        $this->project_id = 0;
        $this->status = 1;
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
        $this->desc = (string)($xml->desc[0]);
        parent::afterLoad();
    }

    public static function getStatusList() {
        $list = array();
        $list[self::STATUS_NEW] = \App\Helper::l('pr_new');

        $list[self::STATUS_INPROCESS] = \App\Helper::l('pr_inp');
        $list[self::STATUS_WA] = \App\Helper::l('pr_wa');
        $list[self::STATUS_SHIFTED] = \App\Helper::l('pr_shifted');
        $list[self::STATUS_WAITPAIMENT] = \App\Helper::l('pr_wp');
        $list[self::STATUS_REOPENED] = \App\Helper::l('pr_reopened');
        $list[self::STATUS_CLOSED] = \App\Helper::l('pr_closed');
        return $list;
    }

}
