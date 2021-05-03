<?php

namespace App\API;

class common extends \App\API\Base\JsonRPC
{

    //получение  токена
    public function token($args) {


        $api = \App\System::getOptions('api');

        $user = \App\Helper::login($args['login'], $args['password']);

        if ($user instanceof \App\Entity\User) {
            $key = strlen($api['key']) > 0 ? $api['key'] : "defkey";
            $exp = strlen($api['exp']) > 0 ? $api['exp'] : 60;

            $token = array(
                "user_id" => $user->user_id,
                "iat"     => time(),
                "exp"     => time() + $exp * 60
            );

            $jwt = \Firebase\JWT\JWT::encode($token, $key);
        } else {
            throw new \Exception(\App\Helper::l('invalidlogin'), -1000);
        }

        return $jwt;
    }

    public function checkapi() {
        return "OK";
    }
}
