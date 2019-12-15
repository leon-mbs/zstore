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

   }
   
   protected function beforeDelete() {

        return '';
   }  
    
   protected function afterDelete() {

        $conn = \ZDB\DB::getConnect();

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
        parent::afterLoad();
    }
}