<?php

namespace App;

/**
 * Класс  для  хранения  в сессии  пользовательских  данных
 *
 */
class Session
{
    private $values = array();
    public $filter = array();
    public $start = 0;

    public function __construct() {

    }

    public function __set($name, $value) {
        $this->values[$name] = $value;
    }

    public function __get($name) {
        return  $this->values[$name]  ?? null;
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
        $this->start = 0;
    }
    //длительность сеанса  в секундах
    public function duration() {
        if(intval($this->start) > 0) {
            return  time() - $this->start;
        } else {
            $this->start  = time();
            return  0;
        }

    }

}
