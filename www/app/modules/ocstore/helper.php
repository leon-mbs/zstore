<?php

namespace App\Modules\OCStore;


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
        if ($data == null) {
            if (strlen($result) > 0) {
                \App\System::setErrorMsg($result);
            } else {
                \App\System::setErrorMsg(\App\Helper::l("nodataresponse"));
            }


            return false;
        }
        //close connection
        curl_close($ch);

        return $result;
    }

}
