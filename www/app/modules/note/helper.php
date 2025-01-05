<?php

namespace App\Modules\Note;

use ZCL\DB\DB as DB;

/**
 * Вспомагательный  класс  для  работы  с  бизнес-данными
 */
class Helper
{
    public static function addFile($file, $itemid) {
        $conn = DB::getConnect();

        $data = file_get_contents($file['tmp_name']);
       
        if(strlen($data) > (1024*1024*4) ) {
           // throw new \Exception('Розмір файлу більше 4M');
           return 0;
        }  
        $filename = $file['name'];

        $filename = $conn->qstr($filename);
        $sql = "insert  into files (item_id,filename,description,item_type) values ({$itemid},{$filename},'',4) ";
        $conn->Execute($sql);
        $id = $conn->Insert_ID();


        $data = $conn->qstr($data);
        $sql = "insert  into filesdata (file_id,filedata) values ({$id},{$data}) ";
        $conn->Execute($sql);
    }

    public static function deleteFile($file_id) {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete  from  files  where  file_id={$file_id}");
        $conn->Execute("delete  from  filesdata  where  file_id={$file_id}");
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
