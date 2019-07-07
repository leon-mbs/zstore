<?php

namespace App\Modules\Note;

use \App\Entity\User;
use \ZCL\DB\DB as DB;

/**
 * Вспомагательный  класс  для  работы  с  бизнес-данными
 */
class Helper
{

 
    public static function getCategoryList() {
        $list = array();
        $conn = \ZCL\DB\DB::getConnect();
        $rs = $conn->Execute("select * from categories order  by  categoryname");
        foreach ($rs as $row) {
            $list[$row['category_id']] = $row['categoryname'];
        }
        return $list;
    }

    
    public static function findFileByTopic($topic_id) {
        //return File::findBySql("select file_id,topic_id,details from files where  topic_id=" . $topic_id);
        
        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute("select * from files where item_id={$topic_id} and item_type=4");
        $list = array();
        foreach ($rs as $row) {
            $item = new \App\DataItem();
            $item->file_id = $row['file_id'];
            $item->filename = $row['filename'];


            $list[] = $item;
        }

        return $list;        
        
    }
    
    
}
