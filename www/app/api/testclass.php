<?php

namespace App\API;

/**
 * Пример  использования  произвольного кастомного  класса  в API
 * пример  вызова /api/TestClass/Hello/1
 */
class TestClass
{

    public function Hello($p) {

        //если  POST
        $request = file_get_contents('php://input');

        echo "Hi";
    }

}
