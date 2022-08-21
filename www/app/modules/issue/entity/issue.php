<?php

namespace App\Modules\Issue\Entity;

/**
 *  Класс  инкапсулирующий   задачу
 * @table=issue_issuelist
 * @view=issue_issuelist_view
 * @keyfield=issue_id
 */
class Issue extends \ZCL\DB\Entity
{

    const STATUS_NEW       = 1;
    const STATUS_INPROCESS = 2;
    const STATUS_QA        = 4;
    const STATUS_REOPENED  = 5;
    const STATUS_RETURNED  = 6;
    const STATUS_WA        = 7;
    const STATUS_SHIFTED   = 8;
    const STATUS_CLOSED    = 12;
    const PRIORITY_HIGH    = 1;
    const PRIORITY_NORMAL  = 2;
    const PRIORITY_LOW     = 3;

    protected function init() {
        $this->issue_id = 0;
        $this->project_id = 0;

        $this->user_id = 0;
        $this->status = 1;
        $this->priority = 0;
        $this->hours = 0;

        $this->createdon = time();
        $this->lastupdate = time();
        $this->notes = '';
    }

    /**
     * проверка на  право удаления
     *
     */
    protected function beforeDelete() {

        return '';
    }

    protected function afterDelete() {

        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from messages where item_type=" . \App\Entity\Message::TYPE_ISSUE . " and item_id=" . $this->issue_id);
        $conn->Execute("delete from files where item_type=" . \App\Entity\Message::TYPE_DOC . " and item_id=" . $this->issue_id);
        $conn->Execute("delete from filesdata where   file_id not in (select file_id from files)");
        $conn->Execute("delete from issue_history where issue_id=" . $this->issue_id);
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные  
        $this->details = "<details>";
        $this->details .= "<hours>{$this->hours}</hours>";
        $this->details .= "<price>{$this->price}</price>";
        $this->details .= "<createdon>{$this->createdon}</createdon>";
        $this->details .= "<createdby>{$this->createdby}</createdby>";
        $this->details .= "<desc><![CDATA[{$this->desc}]]></desc>";

        $this->details .= "<createdbyname><![CDATA[{$this->createdbyname}]]></createdbyname>";
        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {

        $this->lastupdate = strtotime($this->lastupdate);

        //распаковываем  данные из  
        $xml = simplexml_load_string($this->details);

        $this->hours = (int)($xml->hours[0]);
        $this->price = (int)($xml->price[0]);
        $this->createdon = (int)($xml->createdon[0]);
        $this->createdby = (int)($xml->createdby[0]);
        $this->createdbyname = (string)($xml->createdbyname[0]);

        $this->desc = (string)($xml->desc[0]);

        parent::afterLoad();
    }

    public static function getStatusList() {
        $list = array();
        $list[self::STATUS_NEW] = \App\Helper::l('is_new');
        $list[self::STATUS_INPROCESS] = \App\Helper::l('is_inp');
        $list[self::STATUS_QA] = \App\Helper::l('is_qa');
        $list[self::STATUS_REOPENED] = \App\Helper::l('is_reopened');
        $list[self::STATUS_RETURNED] = \App\Helper::l('is_ret');
        $list[self::STATUS_WA] = \App\Helper::l('is_wa');
        $list[self::STATUS_SHIFTED] = \App\Helper::l('is_shifted');
        $list[self::STATUS_CLOSED] = \App\Helper::l('is_closed');

        return $list;
    }

   public static function getPriorityList() {
        $list = array();

  
        
        $list[self::PRIORITY_HIGH] = \App\Helper::l('is_sthigh');
        $list[self::PRIORITY_NORMAL] = \App\Helper::l('is_stnorm');
        $list[self::PRIORITY_LOW] = \App\Helper::l('is_stlow');
        return $list;
    }

    public function addStatusLog($desc) {
        $user = \App\System::getUser();
        $conn = \ZCL\DB\DB::getConnect();
        $createdon = $conn->DBDate(time());
        $desc = Issue::qstr($desc);
        $sql = "insert  into issue_history (issue_id,createdon,user_id ,description  ) values ({$this->issue_id},{$createdon},{$user->user_id} ,{$desc}) ";
        $conn->Execute($sql);
    }

    public function getLogList() {
        $stlist = Issue::getStatusList();
        $list = array();
        $conn = \ZCL\DB\DB::getConnect();
        $sql = "select i.*,u.username from  issue_history i join users_view u on i.user_id = u.user_id where issue_id={$this->issue_id} order  by hist_id";
        $res = $conn->Execute($sql);
        foreach ($res as $v) {
            $item = new \App\DataItem();

            $item->createdon = strtotime($v['createdon']);
            $item->username = $v['username'];
            $item->description = $v['description'];

            $list[] = $item;
        }

        return $list;
    }

}
