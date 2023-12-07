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
        parent::__construct();
 
 
        $this->_tvars['curversion'] = System::CURR_VERSION;
        $this->_tvars['curversionbd'] = System::getOptions('version', false);

 
        if($this->_tvars['curversionbd'] != System::REQUIRED_DB){
           $this->_tvars['reqversion']  = " Версiя БД має  бути <b>".System::REQUIRED_DB."!</b>";                
        } else{
           $this->_tvars['reqversion']  = '';
        }
        
        
 
 
        $this->_tvars['show']  = false   ; 
 
        $json = @file_get_contents("https://zippy.com.ua/version.json");
        
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
        
        

    }   
    
    
}