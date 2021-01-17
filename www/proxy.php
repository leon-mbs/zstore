<?php
// прокси  для текдок
        $im =  $_GET['im'];
        $file = @file_get_contents($im)  ;
        if($file==false)  return '';
        header('Content-Type:image/jpeg' );
        header('Content-Length: ' . strlen($file));
        echo $file;
?>
