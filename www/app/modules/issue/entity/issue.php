<?php

namespace App\Modules\Issue\Entity;

    
/**
 *  Класс  инкапсулирующий   задачу
 * @table=issue_issuelist
 * @view=issue_issuelistview
 * @keyfield=issue_id

 */
class Issue extends Issue
{

    protected function init() {
        $this->issue_id = 0;
        $this->customer_id = 0;
        $this->user_id = 0;
        $this->status = 0;
        $this->priority = 0;
        $this->hours = 0;
 
        $this->price = 0;
        $this->created = time();
        $this->lastupdate = time();
         
 
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные  
        $this->detail = "<content>";
        $this->detail .= "<hours>{$this->discount}</hours>";
        $this->detail .= "<price>{$this->type}</price>";
        $this->detail .= "<desc><![CDATA[{$this->desc}]]></desc>";
        $this->detail .= "</content>";

        return true;
    }

    protected function afterLoad() {
        $this->created = strtotime($this->created);
        $this->lastupdate = strtotime($this->lastupdate);
        
       
        //распаковываем  данные из  
        $xml = simplexml_load_string($this->content);

     
        $this->hours = (int) ($xml->hours[0]);
        $this->price = (int) ($xml->price[0]);
      
        $this->desc = (string) ($xml->desc[0]);

        parent::afterLoad();
    }
  
}
