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
    const STATUS_NEW=0;
    const STATUS_CLOSE=1;
    const STATUS_INPROCESS=2;
    //const STATUS_FINISHED=3;
    const STATUS_QA=4;
    const STATUS_REOPENED=5;
    const STATUS_RETURNED=6;
    const STATUS_WA=7;
    const STATUS_SHIFTED=8;

    protected function init() {
        $this->issue_id = 0;
        $this->customer_id = 0;
        $this->user_id = 0;
        $this->status = 0;
        $this->priority = 0;
        $this->hours = 0;
 
        $this->price = 0;
        $this->createdon = time();
        $this->lastupdate = time();
         
 
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные  
        $this->detail = "<content>";
        $this->detail .= "<hours>{$this->discount}</hours>";
        $this->detail .= "<price>{$this->type}</price>";
        $this->detail .= "<createdon>{$this->createdon}</createdon>";
        $this->detail .= "<createdby>{$this->createdby}</createdby>";
        $this->detail .= "<desc><![CDATA[{$this->desc}]]></desc>";
 
        $this->detail .= "<createdbyname><![CDATA[{$this->createdbyname}]]></createdbyname>";
        $this->detail .= "</content>";

        return true;
    }

    protected function afterLoad() {
   
        $this->lastupdate = strtotime($this->lastupdate);
        
        //распаковываем  данные из  
        $xml = simplexml_load_string($this->content);
     
        $this->hours = (int) ($xml->hours[0]);
        $this->price = (int) ($xml->price[0]);
        $this->createdon = (int) ($xml->createdon[0]);
        $this->createdby = (int) ($xml->createdby[0]);
        $this->createdbyname = (string) ($xml->createdbyname[0]);
      
        $this->desc = (string) ($xml->desc[0]);
  
        parent::afterLoad();
    }
  
  
    public static function getStatusList(){
        $list = array();
        $list[self::STATUS_NEW] =  'Новая';
        $list[self::STATUS_CLOSE] =  'Закрыта';
        $list[self::STATUS_INPROCESS] =  'В работе';
      //  $list[self::STATUS_FINISHED] =  'Закончена';
        $list[self::STATUS_QA] =  'На проверке';
        $list[self::STATUS_REOPENED] =  'Переоткрыта';
        $list[self::STATUS_RETURNED] =  'Возвращена на доработку';
        $list[self::STATUS_WA] =  'На  утверждении';
        $list[self::STATUS_SHIFTED] =  'Отложена';
        
        return $list;
    }
}
