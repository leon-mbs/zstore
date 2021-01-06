<?php

namespace App\API;

/**
 * Пример  для  создания  произвольного класса  через  API
 * пример  вызова /api/Pos/Add/1
 */
class Pos
{
  
    public function Add($p) {
      
        //если  POST
        $request = file_get_contents('php://input');

        echo "Hi";
    }

}
