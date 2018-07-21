<?php

require_once 'init.php';


try {
    $conn = \ZDB\DB::getConnect();

    
 
    
    
    $logger->info("Cron");
 
} catch (Exception $e) {
    echo $e->getMessage();
    $logger->error($e);
}






    
 