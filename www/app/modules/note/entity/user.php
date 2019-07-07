<?php

namespace App\Entity;

use \ZCL\DB\Entity;

/**
 *  Класс  инкапсулирующий   сущность  User
 * @table=users
 * @keyfield=user_id
 */
class User extends Entity
{

    /**
     * @see Entity
     *
     */
    protected function init() {

        $this->user_id = 0;
    }

    /**
     * Проверка  залогинивания
     *
     */
    public function isLogined() {
        return $this->user_id > 0;
    }

    /**
     * Выход из  системмы
     *
     */
    public function logout() {
        $this->init();
    }

    /**
     * Возвращает  пользователя   по  логину
     *
     * @param mixed $login
     */
    public static function getByEmail($email) {
        $conn = \ZCL\DB\DB::getConnect();
        return User::getFirst('email = ' . $conn->qstr($email));
    }

    /**
     * Возвращает ID  пользователя
     *
     */
    public function getUserID() {
        return $this->user_id;
    }

    protected function beforeSave() {
        parent::beforeSave();


        //упаковываем  данные в detail
        $this->details = "<detail>";

        $this->details .= "<phone>{$this->phone}</phone>";


        $this->details .= "</detail>";

        return true;
    }

    protected function afterLoad() {

        $this->createdon = strtotime($this->createdon);
        $this->lastlogin = strtotime($this->lastlogin);

        if (strlen($this->details) > 0) {
            //распаковываем  данные из detail
            $xml = simplexml_load_string($this->details);

            $this->phone = (string) ($xml->phone[0]);
        }

        parent::afterLoad();
    }

}
