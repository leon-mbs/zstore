<?php

namespace App;

use \App\System;
use \App\Application as App;

/**
 * Класс  для  управления доступом к метаобьектам
 */
class ACL {

    private static $_metas = array();
    private static $_metasdesc = array();

    private static function load() {
        if (count(self::$_metas) > 0)
            return;

        $conn = \ZDB\DB::getConnect();
        $rows = $conn->Execute("select * from metadata ");
        foreach ($rows as $row) {
            self::$_metas[$row['meta_type'] . '_' . $row['meta_name']] = $row['meta_id'];
            self::$_metasdesc[$row['meta_name']] = $row['description'];
        }
    }

    //проверка  на  доступ  к  отчету
    public static function checkShowReport($rep, $showerror = true) {

        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['2_' . $rep];
        $aclview = explode(',', System::getUser()->aclview);

        if (in_array($meta_id, $aclview)) {
            return true;
        }

        if ($showerror == true) {
            System::setErrorMsg('Нет права  просмотра   отчета ' . self::$_metasdesc[$rep]);
            App::RedirectHome();
        }
        return false;
    }

    //проверка  на  доступ  к  справочнику 
    public static function checkShowRef($ref) {
        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['4_' . $ref];
        $aclview = explode(',', System::getUser()->aclview);

        if (in_array($meta_id, $aclview)) {
            return true;
        }

        System::setErrorMsg('Нет права  просмотра   справочника ' . self::$_metasdesc[$ref]);
        App::RedirectHome();
        return false;
    }

    //проверка  на  доступ  к   редактированю справочника
    public static function checkEditRef($ref, $showerror = true) {
        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['4_' . $ref];
        $acledit = explode(',', System::getUser()->acledit);

        if (in_array($meta_id, $acledit)) {
            return true;
        }
        if ($showerror == true) {
            System::setErrorMsg('Нет права  изменения   справочника ' . self::$_metasdesc[$ref]);
            App::RedirectHome();
        }
        return false;
    }

    //проверка  на  доступ  к  журналу 
    public static function checkShowReg($reg, $showerror = true) {
        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['3_' . $reg];
        $aclview = explode(',', System::getUser()->aclview);

        if (in_array($meta_id, $aclview)) {
            return true;
        }

        if ($showerror == true) {
            System::setErrorMsg('Нет права  просмотра данного   журнала ' . self::$_metasdesc[$reg]);
            App::RedirectHome();
        }
        return false;
    }

    //проверка  на  доступ  к просмотру документа
    public static function checkShowDoc($doc, $inreg = false, $showerror = true) {
        $user = System::getUser();
        if ($user->acltype != 2)
            return true;

        self::load();
        //для существующих документов
        if ($user->onlymy == 1 && $doc->document_id > 0) {

            if ($user->user_id != $doc->user_id) {
                System::setErrorMsg('Нет права  просмотра    документа ' . self::$_metasdesc[$doc]);
                if ($inreg == false)
                    App::RedirectHome();
                return false;
            }
        }


        $aclview = explode(',', $user->aclview);

        if (in_array($doc->meta_id, $aclview)) {
            return true;
        }


        if ($showerror == true) {
            System::setErrorMsg('Нет права  просмотра   документа ' . self::$_metasdesc[$doc]);
            if ($inreg == false)
                App::RedirectHome();
        }
        return false;
    }

    //проверка  на  доступ  к   редактированию документа
    public static function checkEditDoc($doc, $inreg = false, $showerror = true) {
        $user = System::getUser();
        if ($user->acltype != 2)
            return true;

        self::load();


        if ($user->onlymy == 1 && $doc->document_id > 0) {
            if ($user->user_id != $doc->user_id) {
                System::setErrorMsg('Нет права  изменения   документа ' . self::$_metasdesc[$doc]);
                if ($inreg == false)
                    App::RedirectHome();
                return false;
            }
        }


        $acledit = explode(',', $user->acledit);

        if (in_array($doc->meta_id, $acledit)) {
            return true;
        }

        if ($showerror == true) {

            System::setErrorMsg('Нет права   изменения    документа ' . self::$_metasdesc[$doc->meta_id]);
            if ($inreg == false)
                App::RedirectHome();
        }

        return false;
    }

    /**
     * проверка  на  доступ  к   утверждению и выполнению документа.
     * 
     * @param mixed $doc  документ
     * @param mixed $inreg  в жернале - если нет перебрасывать на  домашнюю страницу
     * @param mixed $showerror показывать  сообщение  об ошибке иначе просто  вернуть  false
     */
    public static function checkExeDoc($doc, $inreg = false, $showerror = true) {
        $user = System::getUser();
        if ($user->acltype != 2)
            return true;

        self::load();

        $aclexe = explode(',', $user->aclexe);

        if (in_array($doc->meta_id, $aclexe)) {
            return true;
        }
        if ($showerror == true) {
            System::setErrorMsg('Нет права  выполнения документа ' . self::$_metasdesc[$doc]);
            if ($inreg == false)
                App::RedirectHome();
        }

        return false;
    }

    //проверка  на  доступ  к  сервисным станицам
    public static function checkShowSer($ser, $showerror = true) {
        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['5_' . $ser];
        $aclview = explode(',', System::getUser()->aclview);



        if (in_array($meta_id, $aclview)) {
            return true;
        }
        if ($showerror == true) {

            System::setErrorMsg('Нет права  просмотра страницы ' . self::$_metasdesc[$ser]);
            App::RedirectHome();
        }
        return false;
    }

    /**
     * возвращает ограничение  для  ресурсов  по филиалам
     * 
     */
    public static function getBranchConstraint($nul=false) {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1)
            return '';

        $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
        if ($id > 0) {
             if($nul==true) {
                 return "branch_id in (0,{$id})";
             } else{
                 return "branch_id in ({$id})";
             }
        }
            

        $user = \App\System::getUser();
        if ($user->username == 'admin')
            return '';

        if (strlen($user->aclbranch) == 0)
            return '1=2'; //нет доступа  ни  к  одному филиалу
 
             if($nul==true) {
                 return "branch_id in (0,{$user->aclbranch})";
             } else{
                 return "branch_id in ({$user->aclbranch})";
             }

        
    }
   /**
     * проверяет  что  выбран  конкретный текущий филиал
     * и возвраает его значение
     * 
     */
    public static function checkCurrentBranch() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1)
            return 0;
        $id = \App\Session::getSession()->branch_id;
        if ($id > 0)
            return $id;
        \App\System::setErrorMsg(\App\Helper::l('selectbranch'));
        \App\Application::RedirectHome();
    }

    /**
     * Возвращает  список складов для подстьановки  в запрос по текущим  филиалам
     * 
     */
    public static function getStoreBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1)
            return '';

        $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
        if ($id > 0) {
            return "select stacl.store_id  from stores stacl where stacl.branch_id={$id} ";
        }

        $user = \App\System::getUser();
        if ($user->username == 'admin')
            return '';

        if (strlen($user->aclbranch) == 0)
            return " (0)"; //нет доступа  ни  к  одному филиалу
        return "select stacl.store_id  from stores stacl where branch_id in {$user->aclbranch} ";
    }

    /**
     * Возвращает  список касс для подстьановки  в запрос по текущим  филиалам
     * 
     */
    public static function getMFBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1)
            return '';

        $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
        if ($id > 0) {
            return "select stacl.mf_id  from mfund stacl where stacl.branch_id={$id} ";
        }

        $user = \App\System::getUser();
        if ($user->username == 'admin')
            return '';

        if (strlen($user->aclbranch) == 0)
            return " (0)"; //нет доступа  ни  к  одному филиалу
        return "select stacl.mf_id  from mfund stacl where branch_id in {$user->aclbranch} ";
    }

    /**
     * Возвращает  список сотрудников для подстьановки  в запрос по текущим  филиалам
     * 
     */
    public static function getEmpBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1)
            return '';

        $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
        if ($id > 0) {
            return "select stacl.employee_id  from employees stacl where stacl.branch_id={$id} ";
        }

        $user = \App\System::getUser();
        if ($user->username == 'admin')
            return '';

        if (strlen($user->aclbranch) == 0)
            return " (0)"; //нет доступа  ни  к  одному филиалу
        return "select stacl.employee_id  from employees stacl where branch_id in {$user->aclbranch} ";
    }

    /**
     * Возвращает  список документы для подстьановки  в запрос по текущим  филиалам
     * 
     */
    public static function getDocBranchConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1)
            return '';

        $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
        if ($id > 0) {
            return "select stacl.document_id  from documents stacl where stacl.branch_id={$id} ";
        }

        $user = \App\System::getUser();
        if ($user->username == 'admin')
            return '';

        if (strlen($user->aclbranch) == 0)
            return " (0)"; //нет доступа  ни  к  одному филиалу
        return "select stacl.document_id  from documents stacl where branch_id in {$user->aclbranch} ";
    }

    /**
     * Возвращает  список филиалов для подстановки  в запрос по текущим  филиалам
     * 
     */
    public static function getBranchListConstraint() {
        $options = \App\System::getOptions('common');
        if ($options['usebranch'] != 1)
            return '';

        $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
        if ($id > 0) {
            return "{$id}";
        }



        $user = \App\System::getUser();
        if ($user->username == 'admin')
            return '';

        if (strlen($user->aclbranch) == 0)
            return " (0)"; //нет доступа  ни  к  одному филиалу
        return "{$user->aclbranch}";
    }

}
