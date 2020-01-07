<?php

namespace App;

use \App\System;
use \App\Application as App;

/**
 * Класс  для  управления доступом к метаобьектам
 */
class ACL {

    private static $_metas = array();

    private static function load() {
        if (count(self::$_metas) > 0)
            return;

        $conn = \ZDB\DB::getConnect();
        $rows = $conn->Execute("select * from metadata ");
        foreach ($rows as $row) {
            self::$_metas[$row['meta_type'] . '_' . $row['meta_name']] = $row['meta_id'];
        }
    }

    //проверка  на  доступ  к  отчету
    public static function checkShowReport($rep) {
        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['2_' . $rep];
        $aclview = explode(',', System::getUser()->aclview);



        if (in_array($meta_id, $aclview)) {
            return true;
        }

        System::setErrorMsg('Нет права  просмотра данного отчета');
        App::RedirectHome();
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

        System::setErrorMsg('Нет права  просмотра данного справочника');
        App::RedirectHome();
        return false;
    }

    //проверка  на  доступ  к   редактированю справочника
    public static function checkEditRef($ref) {
        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['4_' . $ref];
        $acledit = explode(',', System::getUser()->acledit);

        if (in_array($meta_id, $acledit)) {
            return true;
        }

        System::setErrorMsg('Нет права  изменения данного справочника');
        App::RedirectHome();
        return false;
    }

    //проверка  на  доступ  к  журналу 
    public static function checkShowReg($reg) {
        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['3_' . $reg];
        $aclview = explode(',', System::getUser()->aclview);

        if (in_array($meta_id, $aclview)) {
            return true;
        }

        System::setErrorMsg('Нет права  просмотра данного   журнала');
        App::RedirectHome();
        return false;
    }

    //проверка  на  доступ  к просмотру документа
    public static function checkShowDoc($doc, $inreg = false) {
        $user = System::getUser();
        if ($user->acltype != 2)
            return true;

      //  self::load();



        //для существующих документов
        if ($user->onlymy == 1 && $doc->document_id > 0) {

            if ($user->user_id != $doc->user_id) {
                System::setErrorMsg('Нет права  просмотра данного  документа');
                if ($inreg == false)
                    App::RedirectHome();
                return false;
            }
        }


        $aclview = explode(',', $user->aclview);

        if (in_array($doc->meta_id, $aclview)) {
            return true;
        }

        System::setErrorMsg('Нет права  просмотра данного   документа');
        if ($inreg == false)
            App::RedirectHome();
        return false;
    }

    //проверка  на  доступ  к   редактированию документа
    public static function checkEditDoc($doc, $inreg = false) {
        $user = System::getUser();
        if ($user->acltype != 2)
            return true;

       // self::load();



        if ($user->onlymy == 1 && $doc->document_id > 0) {
            if ($user->user_id != $doc->user_id) {
                System::setErrorMsg('Нет права  изменения данного   документа');
                if ($inreg == false)
                    App::RedirectHome();
                return false;
            }
        }


        $acledit = explode(',', $user->acledit);

        if (in_array($doc->meta_id, $acledit)) {
            return true;
        }

        System::setErrorMsg('Нет права   изменения данного  документа');
        if ($inreg == false)
            App::RedirectHome();
        return false;
    }

   //проверка  на  доступ  к  сервисным станицам
    public static function checkShowSer($ser) {
        if (System::getUser()->acltype != 2)
            return true;

        self::load();

        $meta_id = self::$_metas['5_' . $ser];
        $aclview = explode(',', System::getUser()->aclview);



        if (in_array($meta_id, $aclview)) {
            return true;
        }

        System::setErrorMsg('Нет права  просмотра страницы');
        App::RedirectHome();
        return false;
    }
    
    
/**
    * возвращает ограничение  для  ресурсов  по филиалам
    * 
    */
    public static function getBranchConstraint(){
         $options = \App\System::getOptions('common');
         if($options['usebranch'] != 1)  return  '';
         
         $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
         if($id>0)  return "branch_id in ({$id})";         
         
         $user = \App\System::getUser() ;
         if($user->username=='admin')return '';
         
         if(strlen($user->aclbranch)==0) return  '1=2';//нет доступа  ни  к  одному филиалу
         return "branch_id in ({$user->aclbranch})";
    }    
    
    /**
    * проверяет  что  выбран  конкретный текущий филиал
    * и возвраает его значение
    * 
    */
    public static function checkCurrentBranch(){
         $options = \App\System::getOptions('common');
         if($options['usebranch'] != 1)  return  0;
         $id = \App\Session::getSession()->branch_id;
         if($id>0)  return  $id;
         \App\System::setErrorMsg('Не выбран текущий филиал');
         \App\Application::RedirectHome() ;
         
        
    }
    
    
    
    /**
    * Возвращает  список складов для подстьановки  в запрос по текущим  филиалам
    * 
    */
     public static function getStoreBranchConstraint(){
         $options = \App\System::getOptions('common');
         if($options['usebranch'] != 1)  return  '';
         
         $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
         if($id>0) {
             return  "select stacl.store_id  from stores stacl where stacl.branch_id={$id} " ;
         }
           
         $user = \App\System::getUser() ;
         if($user->username=='admin')return '';
         
         if(strlen($user->aclbranch)==0) return " (0)";//нет доступа  ни  к  одному филиалу
          return  "select stacl.store_id  from stores stacl where branch_id in {$user->aclbranch} " ;
  
    }    
    /**
    * Возвращает  список филиалов для подстьановки  в запрос по текущим  филиалам
    * 
    */
     public static function getBranchListConstraint(){
         $options = \App\System::getOptions('common');
         if($options['usebranch'] != 1)  return  '';
         
         $id = \App\Session::getSession()->branch_id; //если  выбран  конкретный
         if($id>0) {
             return  "{$id}" ;
         }
         
                  
         
         $user = \App\System::getUser() ;
         if($user->username=='admin')return '';
         
         if(strlen($user->aclbranch)==0) return " (0)";//нет доступа  ни  к  одному филиалу
         return    "{$user->aclbranch}" ;
  
    }    
       
}
