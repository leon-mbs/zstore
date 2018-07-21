<?php

namespace App\API;

/**
 * Класс  для  работы  с  кассовыми  апаратами
 */
class Pos extends \App\RestFul
{
     //Демо
    public function post($xml) {
        $error = "";


        //$doc = new \App\Entity\Doc\RegisterReceipt();

        // todo
       // $doc->save();

        $this->OKAnswer();
    }

}
