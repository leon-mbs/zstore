<?php

namespace App;

use App\Entity\User;

/**
 * Класс  содержащи  методы  работы   с  наиболее  важными
 * системмными  данными
 */
class System
{
    const CURR_VERSION= "6.5.3";

    private static $_options = array();   //  для кеширования  
    private static $_cache   = array();   //  для кеширования

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

    public static function getBranch() {

        return Session::getSession()->branch_id;
    }

    public static function setBranch(int $branch_id) {
        Session::getSession()->branch_id = $branch_id;
    }
  
    public static function getCustomer() {

        return (int)Session::getSession()->customer_id;
    }

    public static function setCustomer(int $customer_id) {
        Session::getSession()->customer_id = $customer_id;
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
     * @param mixed $isserialise
     */
    public static function getOptions($group,$isserialise=true) {

        if (isset(self::$_options[$group])) {
            return self::$_options[$group];
        }
        $conn = \ZDB\DB::getConnect();

        $rs = $conn->GetOne("select optvalue from options where optname='{$group}' ");
        if (strlen($rs) > 0) {
            if(!$isserialise) return $rs;  //неупакопано
            
            $d =    @unserialize(@base64_decode($rs) );
            if(!is_array($d) ) {
               $d =  @unserialize( $rs );; //для  совместивости   
            }
            self::$_options[$group] = $d;
        }
         
        return @self::$_options[$group];
    }

    /**
     * возвращает настройку
     *
     * @param mixed $group
     * @param mixed $option
     */
    public static function getOption($group, $option) {

        $options = self::getOptions($group);

        return $options[$option];
    }

    /**
     * Записывает набор  параметров  по имени набора
     *
     * @param mixed $group
     * @param mixed $options
     */
    public static function setOptions($group, $options) {
        self::$_options[$group] = $options;
        $options = serialize($options);
        $options = base64_encode($options) ;    
        $conn = \ZDB\DB::getConnect();
        $conn->Execute(" delete from options where  optname='{$group}' ");
        $conn->Execute(" insert into options (optname,optvalue) values ('{$group}'," . $conn->qstr($options) . " ) ");
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

    public static function setSuccessMsg($msg) {
        Session::getSession()->smsg = $msg;
    }

    public static function getSuccesMsg() {
        return Session::getSession()->smsg;
    }

  
    public static function setErrorMsg($msg,$toppage=false) {
       if($toppage) 
          Session::getSession()->emsgtp = $msg;
       else 
          Session::getSession()->emsg = $msg;   
    }

    public static function getErrorMsg( ) {
        return Session::getSession()->emsg;
    }
    public static function getErrorMsgTopPage( ) {
        return Session::getSession()->emsgtp;
    }

    public static function setWarnMsg($msg) {
        Session::getSession()->wmsg = $msg;
    }

    public static function getWarnMsg() {
        return Session::getSession()->wmsg;
    }

    public static function setInfoMsg($msg ) {
        Session::getSession()->imsg = $msg;
    }

    public static function getInfoMsg() {
        return Session::getSession()->imsg;
    }
    public static function clean() {
        self::$_cache = [] ;
        self::$_cache = [] ;
    }
}
