<?php

namespace App\Modules\HR;

use App\System;
use App\Helper as H;

/**
 * Вспомагательный  класс
 */
class Helper
{
    public static function connect() {
                          
        $modules = System::getOptions("modules");

        $body = [];
        $body['login'] = $modules['hrlogin'];
        $body['password'] = $modules['hrpassword'];
        
        try {
            $ret =   self::make_request("POST", "/api/auth", json_encode($body));
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }

        if(!is_array($ret)) {
            //System::setSuccessMsg("Успішне з`єднання");
            return;
        }

        if(($ret['token'] ??'')=='') {
            System::setErrorMsg('Invalid token');
            return;
        }

        return $ret['token'] ??'';

    }

    public static function make_request($method, $url, $body='') {

        $modules = System::getOptions("modules");
        $usessl = $modules['hrssl'];


        $headers = array(
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $modules['hrsite']  . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if (strlen($body)>0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $usessl == 1);
      //  \App\Helper::log(json_encode($body, JSON_UNESCAPED_UNICODE)) ;
        $result = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new  \Exception(curl_error($ch));
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpcode >=300) {
            throw new  \Exception("http code ".$httpcode);
        }
        curl_close($ch);

        $ret = json_decode($result, true)  ;
        if (strlen($ret['status']=='ERROR') ) {
            throw new  \Exception($ret['response']['message']);
        }
                
        return $ret['response'];
    }
    
}
