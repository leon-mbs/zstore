<?php

namespace App\API;

/**
 * пример Rest API
 * вызов /api/TestRest/test/1  (параметры  всегда должны  быть  через  /)
 */
class TestRest extends \App\API\Base\RestFul
{

    public function test($params) {

        $this->TextAnswer($params[0]);
    }

}
