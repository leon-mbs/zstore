<?php

namespace App\API;

/**
 * Пример  для  использования Json RPC
 * пример  вызова /api/Orders
 */
class Orders extends \App\JsonRPC
{

    //{"jsonrpc": "2.0", "method": "getList", "params": {"post": "12345"}, "id": 1}
    public function getList($type) {
        return array();
    }

}
