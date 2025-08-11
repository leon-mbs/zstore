<?php

namespace App\API;

class common extends JsonRPC
{
    //получение  токена
    public function token($args) {


        $api = \App\System::getOptions('api');

        $user = \App\Helper::login($args['login'], $args['password']);

        if ($user instanceof \App\Entity\User) {
          //  $key = strlen($api['key']) > 0 ? $api['key'] : "defkey";
            $key = 'api'.\App\Helper::getSalt();            
            $exp = strlen($api['exp']) > 0 ? $api['exp'] : 60;

            $payload = array(
                "user_id" => $user->user_id,
                "iat"     => time(),
                "exp"     => time() + $exp * 60
            );

            //            $jwt = \Firebase\JWT\JWT::encode($payload, $key);
            $jwt = \Firebase\JWT\JWT::encode($payload, $key, 'HS256');

        } else {
            throw new \Exception("Невірний логін", -1000);
        }

        return $jwt;
    }

    //проверка  API. Авторизация  не  требуется
    public function checkapi() {
        return "OK";
    }


    //список  производственных участвков
    public function parealist() {
        $list = \App\Entity\ProdArea::findArray('pa_name', "disabled<>1","pa_name");

        return $list;
    }

  

    //список  источников  продаж
    public function sourcelist() {
        $common = \App\System::getOptions('common');
        $list = array();
        foreach ($common['salesources'] as $s) {
            $list[$s->id] = $s->name;
        }

        return $list;
    }


}
