<?php

namespace App;

use \App\Entity\User;

/**
 * Класс  содержащи  методы  работы   с  наиболее  важными
 * системмными  данными
 */
class System
{

    private static $_options = array();   //  для кеширования  
    private static $_cache = array();   //  для кеширования  

    /**
     * Возвращает  текущего  юзера
     * @return  User
     */

    public static function getUser() {
        $user = Session::getSession()->user;
        if ($user == null) {
            $user = new User();
            self::setUser($user);
        }
        return $user;
    }

    /**
     * Устанавливавет  текущего  юзера  в  системме
     *
     * @param User $user
     */
    public static function setUser(User $user) {
        Session::getSession()->user = $user;
    }

    /**
     * Возвращает  сессию
     * @return  Session
     */
    public static function getSession() {

        return Session::getSession();
    }

    /**
     * Возвращает набор  параметром  по  имени набора
     *
     * @param mixed $group
     */
    public static function getOptions($group) {

        if (isset(self::$_options[$group])) {
            return self::$_options[$group];
        }
        $conn = \ZDB\DB::getConnect();

        $rs = $conn->GetOne("select optvalue from options where optname='{$group}' ");
        if (strlen($rs) > 0) {
            self::$_options[$group] = @unserialize($rs);
        }

        return self::$_options[$group];
    }

    /**
     * Записывает набор  параметров  по имени набора
     *
     * @param mixed $group
     * @param mixed $options
     */
    public static function setOptions($group, $options) {
        $options = serialize($options);
        $conn = \ZDB\DB::getConnect();

        $conn->Execute(" delete from options where  optname='{$group}' ");
        $conn->Execute(" insert into options (optname,optvalue) values ('{$group}'," . $conn->qstr($options) . " ) ");
        self::$_options[$group] = $options;
    }

    public static function setCache($key, $data) {
        self::$_cache[$key] = $data;
    }

    public static function getCache($key) {

        if (isset(self::$_cache[$key])) {
            return self::$_cache[$key];
        }
        return null;
    }

    public static function setSuccesMsg($msg) {
        Session::getSession()->smsg = $msg;
    }

    public static function getSuccesMsg() {
        return Session::getSession()->smsg;
    }

    public static function setErrorMsg($msg) {
        Session::getSession()->emsg = $msg;
    }

    public static function getErrorMsg() {
        return Session::getSession()->emsg;
    }

    public static function setWarnMsg($msg) {
        Session::getSession()->wmsg = $msg;
    }

    public static function getWarnMsg() {
        return Session::getSession()->wmsg;
    }

    public static function setInfoMsg($msg) {
        Session::getSession()->imsg = $msg;
    }

    public static function getInfoMsg() {
        return Session::getSession()->imsg;
    }

}
