<?php

namespace App\Pages;

use App\Entity\Doc\Document;

//страница  для  загрузки  файла экcпорта
class Doclink extends \Zippy\Html\WebPage
{

    public function __construct($hash) {
         parent::__construct();
 
         if(strlen($hash)==0)  {
                 header("HTTP/1.0 404 Not Found");
                 die;
                  
         }
        $hash = Document::qstr('%'.$hash.'%') ;
        $doc = Document::getFirst("content like ".$hash) ;
        if ($doc == null) {
            header("HTTP/1.0 404 Not Found");
            die;
        }

        $doc = $doc->cast();

        $html = $doc->generateReport();
      
        echo $html;
        die;
        
    }

}
