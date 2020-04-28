<?php

namespace App\Entity;

/**
 *  Класс  инкапсулирующий   сущность  User
 * @table=users
 * @view=users_view
 * @keyfield=user_id
 */
class User extends \ZCL\DB\Entity
{

    /**
     * @see Entity
     *
     */
    protected function init() {
        $this->userlogin = "Гость";
        $this->user_id = 0;
        $this->defstore = 0;
        $this->defmf = 0;
        $this->hidesidebar = 0;
        $this->pagesize = 25;
        $this->createdon = time();
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
     * @see Entity
     *
     */
    protected function afterLoad() {
        $this->createdon = strtotime($this->createdon);

        $acl = @unserialize($this->acl);
        if (!is_array($acl)) {
            $acl = array();
        }
        $this->acltype = (int)$acl['acltype'];
        $this->onlymy = (int)$acl['onlymy'];
        $this->aclview = $acl['aclview'];
        $this->acledit = $acl['acledit'];
        $this->aclexe = $acl['aclexe'];
        $this->aclbranch = $acl['aclbranch'];
        $this->widgets = $acl['widgets'];
        $this->modules = $acl['modules'];

        $options = @unserialize($this->options);
        if (!is_array($options)) {
            $options = array();
        }
        $this->smartmenu = $options['smartmenu'];
        $this->defstore = (int)$options['defstore'];
        $this->defmf = (int)$options['defmf'];
        $this->pagesize = (int)$options['pagesize'];

        $this->popupmessage = (int)$options['popupmessage'];
        $this->hidesidebar = (int)$options['hidesidebar'];

        parent::afterLoad();
    }

    /**
     * @see Entity
     *
     */
    protected function beforeSave() {
        parent::beforeSave();

        $acl = array();
        $acl['acltype'] = $this->acltype;
        $acl['onlymy'] = $this->onlymy;
        $acl['aclview'] = $this->aclview;
        $acl['acledit'] = $this->acledit;
        $acl['aclexe'] = $this->aclexe;
        $acl['aclbranch'] = $this->aclbranch;
        $acl['widgets'] = $this->widgets;
        $acl['modules'] = $this->modules;
        $this->acl = serialize($acl);

        $options = array();
        $options['smartmenu'] = $this->smartmenu;
        $options['defstore'] = $this->defstore;
        $options['defmf'] = $this->defmf;
        $options['pagesize'] = $this->pagesize;
        $options['hidesidebar'] = $this->hidesidebar;
        $options['popupmessage'] = $this->popupmessage;


        $this->options = serialize($options);

        return true;
    }

    /**
     * @see Entity
     *
     */
    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  documents where   user_id = {$this->user_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? "Нельзя удалять пользователя с документами" : '';
    }

    /**
     * Возвращает  пользователя   по  логину
     *
     * @param mixed $login
     */
    public static function getByLogin($login) {
        $conn = \ZDB\DB::getConnect();
        return User::getFirst('userlogin = ' . $conn->qstr($login));
    }

    public static function getByEmail($email) {
        $conn = \ZDB\DB::getConnect();
        return User::getFirst('email = ' . $conn->qstr($email));
    }

    /**
     * Возвращает  пользователя   по  хешу
     *
     * @param mixed $md5hash
     */
    public static function getByHash($md5hash) {
        //$conn = \ZDB\DB::getConnect();
        $arr = User::find('md5hash=' . User::qstr($md5hash));
        if (count($arr) == 0) {
            return null;
        }
        $arr = array_values($arr);
        return $arr[0];
    }

    /**
     * Возвращает ID  пользователя
     *
     */
    public function getUserID() {
        return $this->user_id;
    }

    // Подставляется   сотрудник  если  назначен  логин
    public function getUserName() {
        $e = Employee::getByLogin($this->userlogin);
        if ($e instanceof Employee) {
            return $e->emp_name;
        } else {
            return $this->userlogin;
        }
    }

    public function getOption($key) {
        return $this->_options[$key];
    }

    public function setOption($key, $value) {
        $this->_options[$key] = $value;
    }

}
