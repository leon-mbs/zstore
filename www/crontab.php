<?php
  
require_once 'init.php';
 
\App\System::checkIP()  ;
 
\App\Entity\CronTask::do();
