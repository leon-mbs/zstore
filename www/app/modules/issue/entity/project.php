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
    public const STATUS_NEW         = 1;
    public const STATUS_INPROCESS   = 2;
    public const STATUS_REOPENED    = 3;
    public const STATUS_WA          = 4;
    public const STATUS_SHIFTED     = 5;
    public const STATUS_WAITPAIMENT = 6;
    public const STATUS_CLOSED      = 12;

    protected function init() {
        $this->project_id = 0;
        $this->status = 1;
        $this->createddate = time();
    }

    protected function beforeDelete() {

        return '';
    }

    protected function afterDelete() {

        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from issue_issuelist where   project_id=" . $this->project_id);
        $conn->Execute("delete from issue_projectacc where project_id=" . $this->project_id);
        $conn->Execute("delete from messages  where item_type=" . \App\Entity\Message::TYPE_PROJECT . " and item_id=" . $this->project_id);
        $conn->Execute("delete from files where item_type=" . \App\Entity\Message::TYPE_PROJECT . " and item_id=" . $this->project_id);
        $conn->Execute("delete from filesdata where   file_id not in (select file_id from files)");
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные
        $this->details = "<details>";
        $this->details .= "<desc><![CDATA[{$this->desc}]]></desc>";
        $this->details .= "<creator><![CDATA[{$this->creator}]]></creator>";
        $this->details .= "<creator_id>{$this->creator_id}</creator_id>";
        $this->details .= "<createddate>{$this->createddate}</createddate>";

        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {


        //распаковываем  данные из
        $xml = simplexml_load_string($this->details);
        $this->desc = (string)($xml->desc[0]);
        $this->creator = (string)($xml->creator[0]);
        $this->createddate = (int)($xml->createddate[0]);
        $this->creator_id = (int)($xml->creator_id[0]);

        parent::afterLoad();
    }

    public function getUsers() {
        $list = array();
        $conn = \ZDB\DB::getConnect();
        $res = $conn->Execute("select  user_id  from issue_projectacc   where   project_id={$this->project_id}   ");
        foreach ($res as $r) {
            $list[] = $r['user_id'];
        }
        return $list;
    }

    public function setUsers($users) {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from issue_projectacc where   project_id=" . $this->project_id);
        foreach ($users as $u) {
            $conn->Execute("insert into issue_projectacc  (project_id,user_id) value ({$this->project_id},{$u}) ");
        }
        $conn->Execute("insert into issue_projectacc  (project_id,user_id) value ({$this->project_id},". \App\System::getUser()->user_id  .")  ");
    }

    public static function getStatusList() {
        $list = array();
        $list[self::STATUS_NEW] = "Новий";

        $list[self::STATUS_INPROCESS] = "В роботі";
        $list[self::STATUS_WA] = "На затвердженні";
        $list[self::STATUS_SHIFTED] = "Відкладений";
        $list[self::STATUS_WAITPAIMENT] = "Очікує оплату";
        $list[self::STATUS_REOPENED] = "Перевідкритий";
        $list[self::STATUS_CLOSED] = "Закритий";
        return $list;
    }

}
