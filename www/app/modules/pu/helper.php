<?php

namespace App\Modules\PU;

use App\System;
use App\Helper as H;

/**
 * Вспомагательный  класс
 */
class Helper
{

   

    public static function connect() {

        $modules = System::getOptions("modules");
  
        try {
          $ret =   self::make_request("GET","/api/v1/order_status_options/list",null);
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }

        if(!is_array($ret)) {
           //System::setSuccessMsg(H::l('connected'));    
           return;
        }
        
        $list = array();
        foreach($ret['order_status_options'] as $st) {
           $list[$st['name']]=$st['title'] ;           
        }

        return $list;
        
    }
    
    public static function make_request($method, $url, $body) {
      
        $modules = System::getOptions("modules");

       
      
        $headers = array (
            'Authorization: Bearer ' . $modules['puapitoken'],
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://my.prom.ua'  . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);

        $result = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new  \Exception(curl_error($request));     
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpcode >=300) {
            throw new  \Exception("http code ".$httpcode);     
        }
        curl_close($ch);
         
        return json_decode($result, true);
    }    
    
}
