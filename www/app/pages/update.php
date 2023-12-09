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
 
        $this->add(new  ClickLink('updatesql',$this,'OnSqlUpdate')) ;
 
        $this->_tvars['curversion'] = System::CURR_VERSION;
        $this->_tvars['curversiondb'] = System::getOptions('version', false);

 
        if($this->_tvars['curversiondb'] != System::REQUIRED_DB){
           $this->_tvars['reqversion']  = " Версiя БД має  бути <b>".System::REQUIRED_DB."!</b>";                
        } else{
           $this->_tvars['reqversion']  = '';
        }
        
        
 
        $this->_tvars['showdb']  = false   ;

        $this->_tvars['show']  = false   ; 
 
        $json = @file_get_contents("https://zippy.com.ua/version.json");
        $json = @file_get_contents("http://local.site/version.json");
        
        $data = @json_decode($json,true) ;
        if($data == null){
            $this->setError('Помилка завантаження  json') ;
            return  ;
        }
        $this->_tvars['show']  = true   ;
        
        $c = str_replace("v", "", \App\System::CURR_VERSION);
        $n = str_replace("v", "", $data['version']);

        $ca = explode('.', $c) ;
        $na = explode('.', $n) ;
        
        if ($c === $n ) {

           $this->_tvars['actual']  = true   ;
           $this->_tvars['show']  = false   ;
          
        }        
       if ($na[0] > ($ca[0]+1) || $na[1] > ($ca[1]+1) || $na[2] > ($ca[2]+1)  ) {

           $this->_tvars['tooold']  = true   ;
           $this->_tvars['show']  = false   ;
          
        }        
        
           
        
        
        $this->_tvars['newver']  = $data['version']   ;
        $this->_tvars['notes']  = $data['notes']   ;
        $this->_tvars['archive']  = $data['archive']   ;
        $this->_tvars['github']  = $data['github']   ;
         ;
        $this->_tvars['list']  = []   ;
        foreach($data['changelog'] as $item )  {
           $this->_tvars['list'][] = array('item'=>$item)  ;
        }
        
        
        if (strlen($data['sqlm']) >0) {

          $this->_tvars['showdb']  = true   ;
          $sqlurl= $data['sqlm'] ;
          if($_config['db']['driver'] == 'postgres'){
            $sqlurl= $data['sqlp'] ;              
          }             
          $this->_tvars['sqlurl']  = $sqlurl  ;
          $this->_tvars['sql']  =  file_get_contents($sqlurl)   ;
             
          
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
       
    //   $db=\Zdb\db::getConnect()  ;
       
       try{
         
         $b= mysqli_connect($_config['db']['host'], $_config['db']['pass'], $_config['db']['user'], $_config['db']['name']) ; 
         if($b ==false) {
               $this->setErrorTopPage('Invalis connect')  ;
               return;
         } 
         $error="";
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
             // $db->Execute($sql) ; 
             
         }
 

         $this->setSuccess('БД оновлена')  ;
         App::Redirect("\\App\\Pages\\Update");
         
         
       } catch(\Exception $e){
            $msg = $e->getMessage()  ;
     
            $this->setErrorTopPage($msg)  ;
   
       }
       
 
    }
    
}