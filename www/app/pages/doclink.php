<?php

namespace App\Pages;

use App\Entity\Doc\Document;

//страница  для  загрузки  документа по  внешней ссылке
class Doclink extends \Zippy\Html\WebPage
{
    public function __construct($hash) {
        parent::__construct();

        if(strlen($hash)==0) {
            header("HTTP/1.0 404 Not Found");
            die;

        }
        
        $conn= \ZDB\db::getConnect()  ;
        
        $hash = $conn->qstr('%'.$hash.'%') ;
        
        $id = intval( $conn->GetOne(" select document_id from documents where content like ".$hash) );
        
        $doc = Document::load($id) ;
        if ($doc == null) {
            header("HTTP/1.0 404 Not Found");
            die;
        }

        $doc = $doc->cast()   ;
        if($doc->meta_name=="POSCheck" || $doc->meta_name=="OrderFood"  ) {
            $html = $doc->generatePosReport();
        }   else {
            $html = $doc->generateReport();
        }
        
        echo $html;
        die;

    }

}
