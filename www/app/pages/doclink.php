<?php

namespace App\Pages;

use App\Entity\Doc\Document;

//страница  для  загрузки  документа по  внешней ссылке
class Doclink extends \Zippy\Html\WebPage
{
    public function __construct($hash) {
        parent::__construct();

        // точний збіг коду посилання
        $hash = trim((string)$hash);
        if(strlen($hash) < 16 || !preg_match('~^[a-z0-9+=/]+$~', $hash)) {
            header("HTTP/1.0 404 Not Found");
            die;

        }
        
        $conn= \ZDB\db::getConnect()  ;
        
        $h1 = $conn->qstr('%<hash><![CDATA['.$hash.']]></hash>%') ;
        $h2 = $conn->qstr('%<hash>'.$hash.'</hash>%') ;

        $id = intval( $conn->GetOne(" select document_id from documents where content like {$h1} or content like {$h2} ") );
        
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
