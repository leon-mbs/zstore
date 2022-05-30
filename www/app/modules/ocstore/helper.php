<?php

namespace App\Modules\OCStore;

use App\System;
use App\Helper as H;


/**
 * Вспомагательный  класс
 */
class Helper
{

    /**
     * Функция для  работы  с  API опенкарта
     *
     * @param mixed $url адрес  API  например <youropencartsite>/index.php?route=api/login'
     * @param mixed $params параметры например array('username' => $apiname,'key' => $key );
     */
    public static function do_curl_request($url, $params = array()) {

        $ssl = \App\System::getSession()->ocssl == 1;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, _ROOT . 'upload/apicookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, _ROOT . 'upload/apicookie.txt');

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);

        $params_string = '';
        if (is_array($params) && count($params)) {
            foreach ($params as $key => $value) {
                $params_string .= $key . '=' . $value . '&';
            }
            rtrim($params_string, '&');

            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
        }

        //execute post
        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            \App\System::setErrorMsg($error);
            return false;
        }
        $data = json_decode($result, true);
        if ($data === null) {
            if (strlen($result) > 0) {
                \App\System::setErrorMsg($result,true);
            } else {
                \App\System::setErrorMsg(H::l("nodataresponse",true));
            }


            return false;
        }
        //close connection
        curl_close($ch);

        return $result;
    }


    public static function connect() {
        $modules = System::getOptions("modules");


        $site = $modules['ocsite'];
        $apiname = $modules['ocapiname'];
        $key = $modules['ockey'];
        $site = trim($site, '/');
        $ssl = $modules['ocssl'];

        $url = $site . '/index.php?route=api/login';

        $fields = array(
            'username' => $apiname,
            'key'      => $key
        );
        System::getSession()->ocssl = $ssl;

        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {

            return;
        }

        $data = json_decode($json, true);
        if ($data === null) {
            System::setErrorMsg($json);
            return;
        }
        if (is_array($data) && count($data) == 0) {

            System::setErrorMsg(H::l('nodataresponse'));
            return;
        }

        if (is_array($data['error'])) {
            System::setErrorMsg(implode(' ', $data['error']));
        } else {
            if (strlen($data['error']) > 0) {
                System::setErrorMsg($data['error']);
            }
        }

        if (strlen($data['success']) > 0) {

            if (strlen($data['api_token']) > 0) { //версия 3
                System::getSession()->octoken = "api_token=" . $data['api_token'];
            }
            if (strlen($data['token']) > 0) { //версия 2.3
                System::getSession()->octoken = "token=" . $data['token'];
            }


            System::setSuccessMsg(H::l('connected'));

            //загружаем список статусов
            $url = $site . '/index.php?route=api/zstore/statuses&' . System::getSession()->octoken;
            $json = Helper::do_curl_request($url, array());
            $data = json_decode($json, true);

            if ($data['error'] != "") {
                System::setErrorMsg($data['error']);
            } else {

                System::getSession()->statuses = $data['statuses'];
            }
            //загружаем список категорий
            $url = $site . '/index.php?route=api/zstore/cats&' . System::getSession()->octoken;
            $json = Helper::do_curl_request($url, array());
            $data = json_decode($json, true);

            if ($data['error'] != "") {
                System::setErrorMsg($data['error']);
            } else {

                System::getSession()->cats = $data['cats'];
            }
        }

    }

}
