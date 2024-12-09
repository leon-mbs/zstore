<?php

namespace App\Modules\VDoc;

use App\System;

class Helper
{
    public static function connect($token) {
 
        $url= "https://vchasno.ua/api/v2/documents";



     //   $url= "https://vchasno.ua/api/v2/billing/companies/rates/trials";
     
                        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Accept: application/json','Authorization: '.$token));
        curl_setopt($ch, CURLOPT_HEADER, 0);
   //     curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
   //     curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE)  ;
        if (curl_errno($ch) > 0) {
            $msg = curl_error($ch);
            return  array('error',$msg) ;
        }
        if (strlen($result) == 0) {
            return  array('error','Empty  answer') ;
        }
        if ($httpcode != 200) {
            return  array('error','http '.$httpcode) ;
        }
        curl_close($ch);
        $ret = @json_decode($result, true) ;
        if(is_array($ret)) {
            if( is_array( $ret['documents'] )) {
               return  array('ok') ;

            }
            if($ret['state'] =='err') {
                return  array('error',$ret['desc']) ;

            }

        }
 


    }

    /**
    * put your comment there...
    * 
    * @param mixed $doc
    * @param mixed $filename
    * @param mixed $token
    */
    public static function senddoc(  $doc, $filename,$token ) {
                                                

        $url= "https://vchasno.ua/api/v2/documents";
        

        $f = tempnam(sys_get_temp_dir(), "vdok");        
        file_put_contents($f,$doc)  ;
        
        $post = array(
            'file' => curl_file_create($f, 'application/pdf', $filename)
        );


                          
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                         "Authorization: {$token}"
                        
                         ));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE)  ;
        $ret = @json_decode($result, true) ;
   
        if (curl_errno($ch) > 0) {
            $msg = curl_error($ch);
            return  array('error',$msg) ;
        }
        if (strlen($result) == 0) {
            return  array('error','Empty  answer') ;
        }
        if ($httpcode > 201) {
            return  array('error','http '.$httpcode.' ' . ($ret['reason'] ?? '') ) ;
        }
        curl_close($ch);
        
        return  array('ok') ;

    }
}
 