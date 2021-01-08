<?php

namespace App\API;

/**
 * Пример  для  использования Json RPC
 * пример  вызова /api/TestJsonRPC
 */
class TestJsonRPC extends  JsonRPC
{

    //{"jsonrpc": "2.0", "method": "Hello", "params": {"p1": "12345"}, "id": 1}
    public function Hello($p1) {
        return array();
    }

}
