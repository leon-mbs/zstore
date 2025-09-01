<?php
  
require_once 'client.php';


$client = new ApiClient("http://local.zstore/",2) ;

$client->login('admin','admin');
$client->call('common','parealist');

