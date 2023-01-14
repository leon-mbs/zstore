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
        $this->defsalesource = 0;
        $this->deffirm = 0;
        $this->hidesidebar = 0;
        $this->usemobileprinter = 0;
        $this->pagesize = 25;
        $this->createdon = time();
        $this->mainpage = '\App\Pages\Main';
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
        $this->lastactive = strtotime($this->lastactive);

        //доступы  уровня  роли
        $acl = @unserialize($this->roleacl);
        if (!is_array($acl)) {
            $acl = array();
        }

        //доступы  уровня  пользователя
        $acluser = @unserialize($this->acl);
        if (is_array($acluser)) {
            foreach ($acluser as $k => $v) {
                $acl[$k] = $v;
            }
        }


        $this->noshowpartion = $acl['noshowpartion'];
        $this->showotherstores = $acl['showotherstores'];

        $this->aclview = $acl['aclview'];
        $this->acledit = $acl['acledit'];
        $this->aclexe = $acl['aclexe'];
        $this->aclcancel = $acl['aclcancel'];
        $this->aclstate = $acl['aclstate'];
        $this->acldelete = $acl['acldelete'];

        $this->widgets = $acl['widgets'];
        $this->modules = $acl['modules'];
        $this->smartmenu = $acl['smartmenu'];

        $this->aclbranch = $acl['aclbranch'];
        $this->onlymy = $acl['onlymy'];
        $this->hidemenu = $acl['hidemenu'];

        $options = @unserialize($this->options);
        if (!is_array($options)) {
            $options = array();
        }

        $this->deffirm = (int)$options['deffirm'];
        $this->defstore = (int)$options['defstore'];
        $this->defmf = (int)$options['defmf'];
        $this->defsalesource = (int)$options['defsalesource'];
        $this->pagesize = (int)$options['pagesize'];
        $this->phone = (string)$options['phone'];
        $this->viber = (string)$options['viber'];

        $this->darkmode = (int)$options['darkmode'];
        $this->emailnotify = (int)$options['emailnotify'];
        $this->hidesidebar = (int)$options['hidesidebar'];
        $this->usemobileprinter = (int)$options['usemobileprinter'];
        $this->prtype = (int)$options['prtype'];
        $this->pwsym = (int)$options['pwsym'];
        $this->pserver = $options['pserver'];
        $this->mainpage = $options['mainpage'];
        $this->favs = $options['favs'];
        $this->chat_id = $options['chat_id'];

        parent::afterLoad();
    }

    /**
     * @see Entity
     *
     */
    protected function beforeSave() {
        parent::beforeSave();

        $acl = array();

        $acl['aclbranch'] = $this->aclbranch;
        $acl['onlymy'] = $this->onlymy;
        $acl['hidemenu'] = $this->hidemenu;

        $this->acl = serialize($acl);

        $options = array();

        $options['defstore'] = $this->defstore;
        $options['deffirm'] = $this->deffirm;

        $options['defmf'] = $this->defmf;
        $options['defsalesource'] = $this->defsalesource;
        $options['pagesize'] = $this->pagesize;
        $options['hidesidebar'] = $this->hidesidebar;
        $options['darkmode'] = $this->darkmode;
        $options['emailnotify'] = $this->emailnotify;
        $options['usemobileprinter'] = $this->usemobileprinter;
        $options['pserver'] = $this->pserver;
        $options['prtype'] = $this->prtype;
        $options['pwsym'] = $this->pwsym;
        $options['mainpage'] = $this->mainpage;
        $options['phone'] = $this->phone;
        $options['viber'] = $this->viber;
        $options['favs'] = $this->favs   ;
        $options['chat_id'] = $this->chat_id   ;

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
     * @param mixed $hash
     */
    public static function getByHash($hash) {
        //$conn = \ZDB\DB::getConnect();
        $arr = User::find('hash=' . User::qstr($hash));
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

    //список  пользователей  доступных в  филиале
    public static function getByBranch($branch_id) {
        $users = array();

        foreach (User::find('disabled <> 1', 'username') as $u) {
            if ($u->userrole == 'admins' || $branch_id == 0) {
                $users[$u->user_id] = $u->username;
                continue;
            }
            $br = explode(',', $u->aclbranch);
            if (in_array($branch_id, $br)) {
                $users[$u->user_id] = $u->username;
            }

        }
        return $users;
    }

}
