<?php

namespace App\Modules\Issue;

use \App\Entity\User;
use \App\Modules\Issue\Entity\Issue;
use \ZCL\DB\DB as DB;

/**
 * Вспомагательный  класс  для  работы  с  бизнес-данными
 */
class Helper {

    public static function addHistory($issue_id ,$hours,$notes='') {
         $user = \App\System::getUser();
         $conn = DB::getConnect();
         $notes = $conn->qstr($notes);
         $changed = $conn->DBDate(time()); 
         $sql = "insert  into issue_history (issue_id,changed,user_id,duration,notes) values ({$issue_id},{$changed},{$user->user_id},{$hours},{$notes}) ";
         $conn->Execute($sql);
        
    }
    public static function getHistoryList($issue_id ) {
         $list=array();
         $conn = DB::getConnect();
         $sql = "select * from  issue_history_view where issue_id={$issue_id} order  by id";
         $res = $conn->Execute($sql);
         foreach($res as $v){
             $item = new \App\DataItem();
 
             $item->changed  = strtotime($v['changed']);
             $item->username = $v['username'];
             $item->notes  = $v['notes'];
             $list[]= $item;
         }
         
         return $list;
    }
    
    
 
  
}
