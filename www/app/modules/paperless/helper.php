<?php

namespace App\Modules\Paperless;

use App\System;

class Helper
{
    public static function connect() {
        $modules = System::getOptions("modules");
        $code ="";

        $post="response_type=code&agentCheck=true&client_id={$modules['plclientid']}";
        $url= "https://paperless.com.ua/PplsService/oauth/authorize";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded','Accept: application/json'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
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
            if($ret['state'] =='ok') {
                $code = $ret['code'] ;

            }
            if($ret['state'] =='err') {
                return  array('error',$ret['desc']) ;

            }

        }

        $client_secret = hash("sha512", $modules['plclientid']. $modules['plsecret'] .$code) ;

        $post = "grant_type=authorization_code&client_id={$modules['plclientid']}";
        $post .= "&client_secret={$client_secret}&code={$code}";

        $url= "https://paperless.com.ua/PplsService/oauth/token";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded','Accept: application/json'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
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

            return  array('ok',$ret['access_token']) ;



        }


    }

    /**
    *
    * @param mixed $token      токен с  конекта
    * @param mixed $docdata    содержимое  документа ( можно  уже  с ЭЦП )
    * @param mixed $docname    имя  которое будет  показано в  сервиве (имя  файла, номер накладной  и т.д.)
    * @param mixed $email      если задано, файл будет расшарен на  соответствующего контрагента
    */
    public static function send($token, $docdata, $docname, $email="") {
        $modules = System::getOptions("modules");
        $url= "https://paperless.com.ua/api2/checked/upload";
        $boundary = str_replace('-', '', \App\Util::guid());

        //  $doc = file_get_contents("c:/Users/leonm/Downloads/test.pdf") ;
        $docdata = base64_encode($docdata) ;
        $eol = "\r\n";


        $post="--{$boundary}".$eol;
        $post.="Content-Disposition: form-data; name=\"file\"; filename=\"{$docname}\"".$eol;
        $post.="Content-Type: application/octet-stream;".$eol;
        $post.="Content-Transfer-Encoding: binary".$eol.$eol;


        $post .=  $docdata.$eol;
        $post .=  "--{$boundary}--".$eol;

        $size = strlen($post);





        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                         "Accept: application/json",
                         "Cookie: sessionId=\"Bearer {$token}, Id {$modules['plclientid']}\""
                         ,"Content-Type: multipart/form-data; boundary={$boundary}; charset=UTF-8",
                         "Content-Length: {$size}"
                         ));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE)  ;

        if (curl_errno($ch) > 0) {
            $msg = curl_error($ch);
            return  array('error',$msg) ;
        }
        curl_close($ch) ;
        $res = @json_decode($result, true)  ;
        if (!is_array($res)  || $res['state'] != 'ok') {
            $msg = "Invalid answer ".$res;
            return  array('error',$msg) ;
        }
        $id = $res['resourceDTO'][0]['id'] ;
        if(strlen($email)==0) {
            return  array('ok') ;
        }
        $url= "https://paperless.com.ua/api2/checked/share/".$id;
        $post  = "{\"requestList\":[{\"email\":\"{$email}\",\"comment\":\"\",\"mode\":0}]}"  ;
        $a = json_decode($post) ;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                         "Accept: application/json",
                         "Cookie: sessionId=\"Bearer {$token}, Id {$modules['plclientid']}\""
                        ,"Content-Type: application/json"
        ));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE)  ;
        if (curl_errno($ch) > 0) {
            $msg = curl_error($ch);
            return  array('error',$msg) ;
        }              curl_close($ch) ;
        if($httpcode ==200) {
            return  array('ok') ;
        } else {
            return  array('error',"http code ".$httpcode) ;
        }


    }
}
