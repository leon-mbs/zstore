<?php


$_config = array('common'=>[],'db'=>[],'smtp'=>[]) ;
$_config['common']['loglevel'] = 100  ;//DEBUG = 100,INFO = 200,WARNING = 300,ERROR = 400;
$_config['common']['timezone'] = 'Europe/Kiev'  ;


//соединение с  сервером  БД
$_config['db']['host'] = 'localhost:3306'  ;
$_config['db']['name'] = 'zstore'  ;
$_config['db']['user'] = 'root'  ;
$_config['db']['pass'] = 'root'  ;

//параметры  почтового сервера
$_config['smtp']['usesmtp'] = false ; //якщо false використовується sendmail. Заповнюється лише поле user, ящиком з котрого надсилає sendmail
$_config['smtp']['host'] = 'smtp.google.com' ;
$_config['smtp']['port'] = 587 ;
$_config['smtp']['user'] = 'admin.google.com' ;
$_config['smtp']['emailfrom'] = 'admin.google.com' ;
$_config['smtp']['pass'] = 'пароль' ;
$_config['smtp']['tls'] = true ;
