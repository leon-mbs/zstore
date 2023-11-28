<?php

namespace App\API;

use App\Helper as H;

class saas extends JsonRPC
{
    protected function checkAcess() {
        $api = \App\System::getOptions('api');
        global $_config;


        $u =  $_SERVER['PHP_AUTH_USER'] ?? "";
        $p =  $_SERVER['PHP_AUTH_PW'] ?? "";

        if(strlen($p)==0) {
            return self::error(null, -1000, "Invalid saas");
        }
        if($u != "saas") {
            return self::error(null, -1000, "Invalid saas");
        }
        if($p != ($_config['common']['saas'] ?? "")) {
            return self::error(null, -1000, "Invalid saas");
        }

    }


    public function state() {
        $ret = array();

        $conn = \ZDB\DB::getConnect()  ;

        $ret['lastactive'] = $conn->GetOne("select max(lastactive) from users")  ; //последняя  активность
        $ret['docscount'] =  $conn->GetOne("select count(document_id) from document")  ; // кол. документов


        return $ret;
    }


}
