<?php

namespace App\API;

/**
 * пример Rest API
 * вызов /api/TestRest/1/2   (параметры  всегда должны  быть  через  /)
 */
class TestRest extends \App\API\Base\RestFul
{

    public function get($params) {

        $this->TextAnswer("Hi");
    }

}
