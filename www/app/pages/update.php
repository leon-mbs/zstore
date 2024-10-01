<?php

namespace App\Pages;

use App\Entity\Employee;
use App\Entity\TimeItem;
use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
 

class Update extends \App\Pages\Base
{
    
     public function __construct() {
        global $_config; 
        parent::__construct();
   
        $t = '?t='.time(); 
 
        $this->add(new  ClickLink('updatefile',$this,'OnFileUpdate')) ;
        $this->add(new  ClickLink('updatesql',$this,'OnSqlUpdate')) ;
        $this->add(new  ClickLink('rollback',$this,'OnRollback')) ;
        $this->add(new  ClickLink('reload',$this,'OnFileUpdate')) ;
      
 
         
        $this->_tvars['curversion'] = System::CURR_VERSION;
        $this->_tvars['curversiondb'] =   System::getOptions('version', false);

        $requireddb=  System::REQUIRED_DB ;
 
        if($this->_tvars['curversiondb'] != $requireddb){
           $this->_tvars['reqversion']  = " Версiя БД має  бути <b>{$requireddb}!</b>";                
           $this->_tvars['actualdb'] = false;
        } else{
           $this->_tvars['reqversion']  = '';
           $this->_tvars['actualdb'] =true;
        }
        
        
 
        $this->_tvars['showdb']  = false   ;

        $this->_tvars['show']  = false   ; 
 
        $phpv =   phpversion()  ;
        
        $conn = \ZDB\DB::getConnect();
     
        $nocache= "?t=" . time()."&s=". H::getSalt() .'&phpv='.$phpv. '_'. System::CURR_VERSION ;
    
        $v = @file_get_contents("https://zippy.com.ua/checkver.php".$nocache);
        $data = @json_decode($v, true);
        if(!is_array($data)) {
            $v = @file_get_contents("https://zippy.com.ua/version.json");
            $data = @json_decode($v, true);
            
        }        
    
         
        if(!is_array($data)){
            $this->setError('Помилка завантаження  json') ;
            return  ;
        }
       
        
        $c = str_replace("v", "", \App\System::CURR_VERSION);
        $n = str_replace("v", "", $data['version']);
 
        $b= version_compare($n , $c);
        
        if ($b!=1 ) {  //не новая версия

           $this->_tvars['actual']  = true   ;
           $this->_tvars['show']  = false   ;
          
        } else {
           $this->_tvars['actual']  = false   ;
           $this->_tvars['show']  = true   ;
            
        } 
        
        $ca = explode('.', $c) ;
        $na = explode('.', $n) ;
                
        $this->_tvars['tooold']  = false;    
        if ($na[0] > ($ca[0]+1) || $na[1] > ($ca[1]+1) || $na[2] > ($ca[2]+1)  ) {

           $this->_tvars['tooold']  = true   ;//пропущено несколько
     //      $this->_tvars['show']  = false   ;
          
        }        

          
        $this->_tvars['newver']  = $data['version']   ;
        $this->_tvars['notes']  = $data['notes']   ;
        $this->_tvars['archive']  = $data['archive'] .$t   ;
        $this->_tvars['github']  = $data['github']    ;
     
        $this->_tvars['list']  = []   ;
        foreach($data['changelog'] as $item )  {
           $this->_tvars['list'][] = array('item'=>$item)  ;
        }
        
        $this->_tvars['showdb']  = false;
        
        //обновление  БД
        if ($data['fordb'] ===  $this->_tvars['curversiondb']   ) {

          $this->_tvars['showdb']  = true   ;
          $sqlurl= $data['sql'] ;
          $this->_tvars['sqlurl']  = $sqlurl .$t ;
          $this->_tvars['sql']  =  file_get_contents($this->_tvars['sqlurl'])   ;
             
        }  
        
        $this->_tvars['rollback']  = false;

         // откат к предыдущей       
         if(strlen(System::PREV_VERSION) >0 ) {
             $this->_tvars['rollback']  = true;
             $this->_tvars['prevversipn']  = System::PREV_VERSION;
             
             
         }     
         if($this->_tvars['tooold'] == true) {
             $this->_tvars['rollback']  = false;
         }     
         if($this->_tvars['show'] == true) {
             $this->_tvars['rollback']  = false;
         } 
     
          $this->_tvars['oldphpv']  = $phpv;    
        
 
          $b= version_compare("8.1.0" , $phpv);
          if($b==1)   {
              $this->_tvars['oldphp']  = true; 
                        
          }          
       
         \App\Session::getSession()->migrationcheck = false; 
    }   


    public function OnFileUpdate($sender)   {
    
        try {
            if (!is_writeable( _ROOT .'app/')) {
                $this->setError('Нема  права  запису');
                return;        
            }

               
            $zip = new \ZipArchive()  ;

            $archive = _ROOT.'upload/update.zip' ;
            @unlink($archive) ;
            
            @file_put_contents($archive, file_get_contents($this->_tvars['archive'] )) ;
         
            if(filesize($archive)==0) {
                $this->setError('Помилка завантаження файлу');
                return;        
            }

            if ($zip->open($archive) === TRUE) {
           
                $destination =_ROOT; 
                
                $zip->extractTo($destination);
                $zip->close();

           }  else {
                $this->setError('Помилка  архіву');
                return;        
               
           }
           $this->setSuccess('Файли оновлені')  ;
           App::RedirectURI("/index.php?p=/App/Pages/Update");

         
      } catch(\Exception $e){
            $msg = $e->getMessage()  ;
     
            $this->setErrorTopPage($msg)  ;
   
       } 
         
    }
 
    public function OnSqlUpdate($sender)   {
       global $_config; 
  
       $sql= file_get_contents($this->_tvars['sqlurl'] )  ;
       if(strlen($sql)==0) {
           $this->setError('Не знайдено  файл оновлення')  ;
           return;
       }
       
       
       $sql_array = explode(';',$sql) ;
       
  
       try{                 
                  
  
             $b= mysqli_connect($_config['db']['host'], $_config['db']['user'], $_config['db']['pass'], $_config['db']['name']) ; 

             if($b ==false) {
                   $this->setErrorTopPage('Invalid connect')  ;
                   return;
             } 
             mysqli_query($b,"SET NAMES 'utf8'") ;
             foreach($sql_array as $s) {
                 $s = trim($s);
                 if(strlen($s)==0) {
                     continue;
                 }
                 $r= mysqli_query($b,$s) ;
                 if($r ==false) {
                       $msg=mysqli_error($b)  ;
                       $this->setErrorTopPage($s.' '.$msg) ;
                       return;
                 } 
            
                 
             }
  
 

         $this->setSuccess('БД оновлена')  ;
         App::RedirectURI("/index.php?p=/App/Pages/Update");
         
         
       } catch(\Exception $e){
            $msg = $e->getMessage()  ;
     
            $this->setErrorTopPage($msg)  ;
   
       }
       
 
    }
 
    public function OnRollback($sender)   {
    
        try {
            if (!is_writeable( _ROOT .'app/')) {
                $this->setError('Нема  права  запису');
                return;        
            }

               
            $zip = new \ZipArchive()  ;

            $archive = _ROOT.'upload/update.zip' ;
            @unlink($archive) ;
            $path = "https://zippy.com.ua/updates/update-".System::PREV_VERSION.".zip";
            
            @file_put_contents($archive, file_get_contents($path)) ;
         
            if(filesize($archive)==0) {
                $this->setError('Помилка завантаження файлу');
                return;        
            }
         
            if ($zip->open($archive) === TRUE) {
           
                $destination =_ROOT; 
                
                $zip->extractTo($destination);
                $zip->close();

           }  else {
                $this->setError('Помилка  архіву');
                return;        
               
           }
           $this->setSuccess('Файли оновлені')  ;
           App::RedirectURI("/index.php?p=/App/Pages/Update");

         
      } catch(\Exception $e){
            $msg = $e->getMessage()  ;
     
            $this->setErrorTopPage($msg)  ;
   
       } 
         
    }
    
}
 