<?php

namespace App\API;

/**
 * пример Rest API 
 * вызов /api/TestRest/1/2
 */
class TestRest extends  RestFul
{
 
    public function get($params) {

        $this->TextAnswer("Hi");
    }

}
