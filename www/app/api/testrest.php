<?php

namespace App\API;

/**
 * пример Rest API
 * вызов /api/TestRest/test   (параметры  всегда должны  быть  через  /)
 */
class TestRest extends \App\API\Base\RestFul
{

    public function test($params) {

        $this->TextAnswer("Hi");
    }

}
