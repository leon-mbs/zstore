<?php

namespace App;

/**
 * Класс  для  хранения  в сессии  пользовательских  данных
 *
 */
class Session
{

    private $values = array();
    public  $filter = array();

    public function __construct() {

    }

    public function __set($name, $value) {
        $this->values[$name] = $value;
    }

    public function __get($name) {
        return @$this->values[$name];
    }

    /**
     * Возвращает  инстанс  сессии
     * @return Session
     */
    public static function getSession() {
        if (!isset($_SESSION['App_session'])) {
            $_SESSION['App_session'] = new Session();
        }
        return $_SESSION['App_session'];
    }

    public function clean() {
        $this->values = array();
        $this->filter = array();
    }

}
