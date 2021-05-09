<?php

namespace App\API;

/**
 * Пример  использования  произвольного кастомного  класса возвращающего  произвольные данные
 * пример  вызова /api/TestClass/Hello/1        (параметры  всегда должны  быть  через  /)
 */
class TestClass
{

    public function Hello($p) {

        //если  POST
        $request = file_get_contents('php://input');

        echo "Hi";
    }

}
