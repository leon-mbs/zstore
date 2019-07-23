<?php

namespace App\Modules\Issue;

use \App\Entity\User;
use \App\Modules\Issue\Entity\Issue;
use \ZCL\DB\DB as DB;

/**
 * Вспомагательный  класс  для  работы  с  бизнес-данными
 */
class Helper
{
    public static function addFile($file, $itemid ) {
        $conn = DB::getConnect();
        $filename = $file['name'];

   
        $filename = $conn->qstr($filename);
        $sql = "insert  into files (item_id,filename,description,item_type) values ({$itemid},{$filename},'',4) ";
        $conn->Execute($sql);
        $id = $conn->Insert_ID();

        $data = file_get_contents($file['tmp_name']);
        $data = $conn->qstr($data);
        $sql = "insert  into filesdata (file_id,filedata) values ({$id},{$data}) ";
        $conn->Execute($sql);
    }
 
    public static function deleteFile($file_id) {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete  from  files  where  file_id={$file_id}");
        $conn->Execute("delete  from  filesdata  where  file_id={$file_id}");
    }
 
     
    
}
