<?php

namespace App\Modules\Tecdoc;

use App\System;

/**
* класс для  работы  с API поставщиков
*/
class APIHelper
{
      private $key="";

      public function __construct($sname ) {
          $modules = System::getOptions("modules");
          if($sname=="omega") {
             $this->key = $modules['td_omkey'];
          }
          
      } 
      
      //omega
      
      public function request($url,$body=[]){
         
         $url= "https://public.omega.page/public/api/v1.0". $url;
         $body['Key']  = $this->key;
         
         
         $post = json_encode($body, JSON_UNESCAPED_UNICODE) ;
         
         $ch = curl_init($url);
  
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
         curl_setopt($ch, CURLOPT_HEADER, 0);
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
         $result = curl_exec($ch);
         if (curl_errno($ch) > 0) {
             $msg = str_replace("'", "`", curl_error($ch)) ;
             return array('success' => false, 'data' => $msg);
         }
         curl_close($ch);                  
         $result = json_decode($result,true); 
         
         $ret=[];
         $ret['success'] = true;
         if( $result["Success"]) {
            $ret['data'] =  $ret['Data'] ;
         }  else {
            $ret['success'] = false;
            $ret['data'] =  "" ;
            foreach($result["Errors"] as $e) {
              $ret['data'] .= $e['Error']." " ;
            }
         }
         
         return $ret;
           
         
      }
      
      
}
 
 