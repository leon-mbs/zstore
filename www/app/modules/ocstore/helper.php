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

      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, _ROOT . 'upload/apicookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, _ROOT . 'upload/apicookie.txt');

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $params_string = '';
        if (is_array($params) && count($params)) {
        
            $params_string = http_build_query($params);

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
        curl_close($ch);
        
        $data = json_decode($result, true);
        if ($data === null) {
            if (strlen($result) > 0) {
                \App\System::setErrorMsg($result, true);
            } else {
                \App\System::setErrorMsg("Немає даних відповіді", true);
            }


            return false;
        }
      
        

        return $result;
    }


    /**
    * список  магазинов
    * при  первом  вызове  переносит  магазин  из  старых  настроек (ocsite, ockey...)  и  сразу  сохраняет,
    * чтобы  импорт  работал  без  захода  в  настройки
    */
    public static function getList() {
        $list = System::getOption("modules", 'oclist', []);
        if (count($list) > 0) {
            return $list;
        }
        $modules = System::getOptions("modules");
        if (strlen($modules['ocsite'] ?? '') == 0) {
            return [];
        }
        $ss = \App\Helper::getSaleSources();

        $oc = new \App\DataItem() ;
        $oc->id = 1;
        $oc->site = trim($modules['ocsite'], '/');
        $oc->salesource = $modules['ocsalesource'] ?? 0;
        $oc->salesourcename = $ss[$oc->salesource] ?? '';
        $oc->apiname = $modules['ocapiname'] ?? '';
        $oc->insertcust = $modules['ocinsertcust'] ?? 0;
        $oc->dostore = $modules['ocdostore'] ?? 0;
        $oc->reserve = $modules['ocreserve'] ?? 0;
        $oc->ocv4 = $modules['ocv4'] ?? 0;
        $oc->key = $modules['ockey'] ?? '';
        $oc->pricetype = $modules['ocpricetype'] ?? '';
        $oc->mf = $modules['ocmf'] ?? 0;
        $oc->storeid = $modules['ocstoreid'] ?? 0;
        $oc->paytype = $modules['ocpaytype'] ?? 0;

        $list[$oc->id] = $oc;
        $modules['oclist'] = $list;
        System::setOptions("modules", $modules);

        return $list;
    }

    /**
    * список  магазинов  для  выбора  (если  источник  продаж  не  задан  - адрес  сайта)
    */
    public static function getSelectList() {
        $mlist = [];
        foreach (Helper::getList() as $m) {
            $mlist[$m->id] = strlen($m->salesourcename ?? '') > 0 ? $m->salesourcename : $m->site;
        }
        return $mlist;
    }

    public static function connect($m) {

        System::getSession()->statuses=null;
        System::getSession()->cats=null;
       
        $url = $m->site . '/index.php?route=api/login';
        if($m->ocv4==1) {
            $url = $m->site . '/index.php?route=api/account/login';
        }
        $fields = array(
            'username' => $m->apiname,
            'key'      => $m->key
        );
       
        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {

            return false;
        }

        $data = json_decode($json, true);
        if ($data === null) {
            System::setErrorMsg($json);
            return false;
        }
        if (is_array($data) && count($data) == 0) {

            System::setErrorMsg('Немає даних відповіді');
            return false;
        }

        if (is_array($data['error'] ?? null)) {
            System::setErrorMsg(implode(' ', $data['error']));
        } else {
            if (strlen($data['error']?? null) > 0) {
                System::setErrorMsg($data['error']);
                 return false;
            }
        }

        if (strlen($data['success']) > 0) {

            if (strlen($data['api_token']) > 0) { //версия 3
                System::getSession()->octoken = "api_token=" . $data['api_token'];
            }
            if (strlen($data['token']?? null) > 0) { //версия 2.3
                System::getSession()->octoken = "token=" . $data['token'];
            }

          
        
            //загружаем список статусов
            $url = $m->site . '/index.php?route=api/zstore/statuses&' . System::getSession()->octoken;
            if($m->ocv4==1) {
                $url = $m->site . '/index.php?route=api/zstore.statuses&' .System::getSession()->octoken;
            }
            $json = Helper::do_curl_request($url, array());
            $data = json_decode($json, true);

            if ($data['error'] != "") {
                System::setErrorMsg($data['error']);
                 return false;
            } else {

                System::getSession()->statuses = $data['statuses'];
            }
            //загружаем список категорий
            $url = $m->site . '/index.php?route=api/zstore/cats&' . System::getSession()->octoken;
            if($m->ocv4==1) {  
                $url = $m->site . '/index.php?route=api/zstore.cats&' . System::getSession()->octoken;
            }
            $json = Helper::do_curl_request($url, array());
            $data = json_decode($json, true);

            if ($data['error'] != "") {
                System::setErrorMsg($data['error']);
                 return false;
            } else {

                System::getSession()->cats = $data['cats'];
            }
        }
        return true;
    }

}
