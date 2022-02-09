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
         // $ret =   $this->make_request();
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }


        System::setSuccessMsg(H::l('connected'));


    }
    
    public function make_request($method, $url, $body) {
      
        $modules = System::getOptions("modules");

       
      
        $headers = array (
            'Authorization: Bearer ' . $modules['puapitoken'],
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $modules['pusite'] . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     //   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }    
    
}
