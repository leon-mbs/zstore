<?php

namespace App;

use \App\Application as App;
use \App\Helper as H;
use \App\Entity\User;

/**
 * Класс  для  управления доступом к метаобьектам
 */
class ACL
{
    private static $_metas     = array();
    private static $_metasdesc = array();

    private static function load() {
        if (count(self::$_metas) > 0) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $rows = $conn->Execute("select * from metadata ");
        foreach ($rows as $row) {
            self::$_metas[$row['meta_type'] . '_' . $row['meta_name']] = $row['meta_id'];
            self::$_metasdesc[$row['meta_name']] = $row['description'];
        }
    }

    //проверка  на  доступ  к  отчету
    public static function checkShowReport($rep, $showerror = true) {

        if (System::getUser()->rolename == 'admins') {
            return true;
        }

        self::load();

        $meta_id = self::$_metas['2_' . $rep];
        $aclview = explode(',', System::getUser()->aclview);

        if (in_array($meta_id, $aclview)) {
            return true;
        }

        if ($showerror == true) {
            System::setErrorMsg("Немає права перегляду звіту ". self::$_metasdesc[$rep]);
            App::RedirectError();
        }
        return false;
    }

    //проверка  на  доступ  к  справочнику
    public static function checkShowRef($ref) {
        if (System::getUser()->rolename == 'admins') {
            return true;
        }

        self::load();

        $meta_id = self::$_metas['4_' . $ref];
        $aclview = explode(',', System::getUser()->aclview);

        if (in_array($meta_id, $aclview)) {
            return true;
        }

        System::setErrorMsg("Немає права доступу до довідника ". self::$_metasdesc[$ref]);

        App::RedirectError();
        return false;
    }

    //проверка  на  доступ  к   редактированю справочника
    public static function checkEditRef($ref, $showerror = true) {
        if (System::getUser()->rolename == 'admins') {
            return true;
        }

        self::load();

        $meta_id = self::$_metas['4_' . $ref];
        $acledit = explode(',', System::getUser()->acledit);

        if (in_array($meta_id, $acledit)) {
            return true;
        }
        if ($showerror == true) {
            System::setErrorMsg("Немає права доступу до довідника ". self::$_metasdesc[$ref]);
        }
        return false;
    }

    //проверка  на  доступ  к   удалению из справочника
    public static function checkDelRef($ref, $showerror = true) {
        if (System::getUser()->rolename == 'admins') {
            return true;
        }

        self::load();

        $meta_id = self::$_metas['4_' . $ref];
        $acldelete = explode(',', System::getUser()->acldelete);

        if (in_array($meta_id, $acldelete)) {
            return true;
        }
        if ($showerror == true) {
            System::setErrorMsg("Немає права видалення із довідника " . self::$_metasdesc[$ref]);
        }
        return false;
    }

    //проверка  на  доступ  к  журналу
    public static function checkShowReg($reg, $showerror = true) {
        if (System::getUser()->rolename == 'admins') {
            return true;
        }

        self::load();

        $meta_id = self::$_metas['3_' . $reg];
        $aclview = explode(',', System::getUser()->aclview);

        if (in_array($meta_id, $aclview)) {
            return true;
        }

        if ($showerror == true) {
            System::setErrorMsg("Немає права перегляду журналу  " . self::$_metasdesc[$reg]);
            App::RedirectError();
        }
        return false;
    }

    //проверка  на  доступ  к просмотру документа
    public static function checkShowDoc($doc, $inreg = false, $showerror = true,$user_id=0) {
        $user = System::getUser();
        if($user_id >0) {
            $user = User::load($user_id);
        }
        if ($user->rolename == 'admins') {
            return true;
        }

        self::load();
        //для существующих документов
        if ($user->onlymy == 1 && $doc->document_id > 0) {

            if ($user->user_id != $doc->user_id) {
                if ($showerror == true) {
                    System::setErrorMsg("Немає права перегляду документа  " . self::$_metasdesc[$doc->meta_name]);
                }
                if ($inreg == false) {
                    App::RedirectError();
                }
                return false;
            }
        }


        $aclview = explode(',', $user->aclview);

        if (in_array($doc->meta_id, $aclview)) {
            return true;
        }


        if ($showerror == true) {
            System::setErrorMsg("Немає права перегляду документа ".  self::$_metasdesc[$doc->meta_name]);

            if ($inreg == false) {
                App::RedirectError();
            }
        }
        return false;
    }

    //проверка  на  доступ  к   редактированию документа
    public static function checkEditDoc($doc, $inreg = false, $showerror = true,$user_id=0) {
        $user = System::getUser();
        if($user_id >0) {
            $user = User::load($user_id);
        }
        if ($user->rolename == 'admins') {
            return true;
        }
  

        self::load();

        if ($user->onlymy == 1 && $doc->document_id > 0) {
            if ($user->user_id != $doc->user_id) {
                if ($showerror == true) {
                    System::setErrorMsg("Немає права редагування документа " . self::$_metasdesc[$doc->meta_name]);
                }
                if ($inreg == false) {
                    App::RedirectError();
                }
                return false;
            }
        }


        $acledit = explode(',', $user->acledit);

        if (in_array($doc->meta_id, $acledit)) {
            return true;
        }

        if ($showerror == true) {

            System::setErrorMsg("Немає права редагування документа  ". self::$_metasdesc[$doc->meta_name]);
            if ($inreg == false) {
                App::RedirectError();
            }
        }

        return false;
    }

    //проверка  на  доступ  к   удалению документа
    public static function checkDelDoc($doc, $inreg = false, $showerror = true,$user_id=0) {
        $user = System::getUser();
        if($user_id >0) {
            $user = User::load($user_id);
        }
        if ($user->rolename == 'admins') {
            return true;
        }

        self::load();

        if ($user->onlymy == 1 && $doc->document_id > 0) {
            if ($user->user_id != $doc->user_id) {
                if ($showerror == true) {
                    System::setErrorMsg("Немає права видалення документа " . self::$_metasdesc[$doc->meta_name]);
                }
                if ($inreg == false) {
                    App::RedirectError();
                }
                return false;
            }
        }


        $acldelete = explode(',', $user->acldelete);

        if (in_array($doc->meta_id, $acldelete)) {
            return true;
        }

        if ($showerror == true) {

            System::setErrorMsg("Немає права видалення документа " . self::$_metasdesc[$doc->meta_name]);
            if ($inreg == false) {
                App::RedirectError();
            }
        }

        return false;
    }

    /**
     * проверка  на  доступ  к   утверждению и выполнению документа.
     *
     * @param mixed $doc документ
     * @param mixed $inreg в жернале - если нет перебрасывать на  домашнюю страницу
     * @param mixed $showerror показывать  сообщение  об ошибке иначе просто  вернуть  false
     */
    public static function checkExeDoc($doc, $inreg = false, $showerror = true,$user_id=0) {
        $user = System::getUser();
        if($user_id >0) {
            $user = User::load($user_id);
        }
        if ($user->rolename == 'admins') {
            return true;
        }

        self::load();

        $aclexe = explode(',', $user->aclexe);

        if (in_array($doc->meta_id, $aclexe)) {
            return true;
        }
        if ($showerror == true) {
            System::setErrorMsg("Немає права проведення документа " . self::$_metasdesc[$doc->meta_name]);
            if ($inreg == false) {
                App::RedirectError();
            }
        }

        return false;
    }

    /**
     * проверка  на  доступ  к смене  статуса документа.
     *
     * @param mixed $doc документ
     * @param mixed $showerror показывать  сообщение  об ошибке иначе просто  вернуть  false
     */
    public static function checkChangeStateDoc($doc, $inreg = true, $showerror = true,$user_id=0) {
        $user = System::getUser();
        if($user_id >0) {
            $user = User::load($user_id);
        }
        if ($user->rolename == 'admins') {
            return true;
        }

        self::load();

        $aclstate = explode(',', $user->aclstate);

        if (in_array($doc->meta_id, $aclstate)) {
            return true;
        }
        if ($showerror == true) {
            System::setErrorMsg("Немає права зміни статусу документа " . self::$_metasdesc[$doc->meta_name]);
            if ($inreg == false) {
                App::RedirectError();
            }
        }

        return false;
    }

    /**
     * проверка  на  доступ  к отмене документа.
     *
     * @param mixed $doc документ
     * @param mixed $showerror показывать  сообщение  об ошибке иначе просто  вернуть  false
     */
    public static function checkCancelDoc($doc, $inreg = true, $showerror = true,$user_id=0) {
        $user = System::getUser();
        if($user_id >0) {
            $user = User::load($user_id);
        }
        if ($user->rolename == 'admins') {
            return true;
        }

        self::load();

        $aclcancel = explode(',', $user->aclcancel);

        if (in_array($doc->meta_id, $aclcancel)) {
            return true;
        }
        if ($showerror == true) {
            System::setErrorMsg("Немає права відміни документа ". self::$_metasdesc[$doc->meta_name]);
            if ($inreg == false) {
                App::RedirectError();
            }
        }

        return false;
    }

    //проверка  на  доступ  к  сервисным станицам
    public static function checkShowSer($ser, $showerror = true) {
        if (System::getUser()->rolename == 'admins') {
            return true;
        }

        self::load();

        $meta_id = self::$_metas['5_' . $ser];
        $aclview = explode(',', System::getUser()->aclview);

        if (in_array($meta_id, $aclview)) {
            return true;
        }
        if ($showerror == true) {
            System::setErrorMsg("Немає права доступу до сторінки " . self::$_metasdesc[$ser]);

            App::RedirectError();
        }
        return false;
    }

    /**
     * возвращает ограничение  для  ресурсов  по филиалам
     */
    public static function getBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1) {
            return '';
        }
        $user = \App\System::getUser();

        $id = \App\System::getBranch(); //если  выбран  конкретный
        if ($id > 0) {

            return "branch_id in (0,{$id})";
        }


        if ($user->rolename == 'admins') {
            return '';
        }

        if (strlen($user->aclbranch) == 0) {
            return "branch_id in (0 )";
        } //нет доступа  ни  к  одному филиалу


        return "branch_id in (0,{$user->aclbranch})";
    }

    /**
     * проверяет  что  выбран  конкретный текущий филиал
     * и возвраает его значение
     *
     */
    public static function checkCurrentBranch() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1) {
            return 0;
        }
        $id = \App\System::getBranch();
        if ($id > 0) {
            return $id;
        }
        \App\System::setErrorMsg('Для створення документу має бути вибрана філія');
        \App\Application::Redirect("\\App\\Pages\\Blank");
    }

    public static function getCurrentBranch() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1) {
            return 0;
        }
        $id = \App\System::getBranch();
        if ($id > 0) {
            return $id;
        }
        return 0;
    }


    /**
     * Возвращает  список складов для подстановки  в запрос по текущим  филиалам
     *
     */
    public static function getStoreBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1) {
            return '';
        }

        $id = \App\System::getBranch(); //если  выбран  конкретный

        if ($id > 0) {
            return "select stacl.store_id  from stores stacl where stacl.branch_id={$id} ";
        }

        $user = \App\System::getUser();
        if ($user->rolename == 'admins') {
            return '';
        }

        if (strlen($user->aclbranch) == 0) {
            return " (0)";
        } //нет доступа  ни  к  одному филиалу
        return "select stacl.store_id  from stores stacl where branch_id in ({$user->aclbranch} ) ";
    }

    /**
     * Возвращает  список касс для подстановки  в запрос по текущим  филиалам
     *
     */
    public static function getMFBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1) {
            return '';
        }

        $id = \App\System::getBranch(); //если  выбран  конкретный
        if ($id > 0) {
            return "select stacl.mf_id  from mfund stacl where stacl.branch_id={$id} ";
        }

        $user = \App\System::getUser();
        if ($user->rolename == 'admins') {
            return '';
        }

        if (strlen($user->aclbranch) == 0) {
            return " (0)";
        } //нет доступа  ни  к  одному филиалу
        return "select stacl.mf_id  from mfund stacl where branch_id in ({$user->aclbranch}) ";
    }

    /**
     * Возвращает  список сотрудников для подстьановки  в запрос по текущим  филиалам
     *
     */
    public static function getEmpBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1) {
            return '';
        }

        $id = \App\System::getBranch(); //если  выбран  конкретный
        if ($id > 0) {
            return "select stacl.employee_id  from employees stacl where stacl.branch_id={$id} ";
        }

        $user = \App\System::getUser();
        if ($user->rolename == 'admins') {
            return '';
        }

        if (strlen($user->aclbranch) == 0) {
            return " (0)";
        } //нет доступа  ни  к  одному филиалу
        return "select stacl.employee_id  from employees stacl where branch_id in ({$user->aclbranch}) ";
    }

    /**
     * Возвращает  список документы для подстановки  в запрос по текущим  филиалам
     *
     */
    public static function getDocBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1) {
            return '';
        }

        $id = \App\System::getBranch(); //если  выбран  конкретный
        if ($id > 0) {
            return "select stacl.document_id  from documents stacl where stacl.branch_id={$id} ";
        }

        $user = \App\System::getUser();
        if ($user->rolename == 'admins') {
            return '';
        }

        if (strlen($user->aclbranch) == 0) {
            return " (0)";
        } //нет доступа  ни  к  одному филиалу
        return "select stacl.document_id  from documents stacl where branch_id in ({$user->aclbranch})";
    }

    /**
     * Возвращает  список филиалов для подстановки  в запрос  в  виде  списка  цифр  например в  нативные  sql запросы
     *
     */
    public static function getBranchIDsConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1) {
            return '';
        }

        $id = \App\System::getBranch(); //если  выбран  конкретный
        if ($id > 0) {
            return "{$id}";
        }


        $user = \App\System::getUser();
        if ($user->rolename == 'admins') {
            return '';
        }

        if (strlen($user->aclbranch) == 0) {
            return "0";
        } //нет доступа  ни  к  одному филиалу
        return "{$user->aclbranch}";
    }

}
