<?php

namespace App\API;

/**
 * Класс  для  работы  с  кассовыми  апаратами
 */
class Pos  
{

    //Демо
    public function post($p) {
        $error = "";
        $xml = $_POST['data'];
        $user = $_POST['user'];
        $pass = $_REQUEST['pass'];

        //$doc = new \App\Entity\Doc\RegisterReceipt();
        // todo
        // $doc->save();

        return "";
    }

}
