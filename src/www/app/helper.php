<?php

namespace App;

use \App\Entity\User;
use \App\System;
use \ZCL\DB\DB as DB;

/**
 * Вспомагательный  класс  для  работы  с  бизнес-данными
 */
class Helper
{

    private static $meta = array(); //кеширует метаданные

    /**
     * Выполняет  логин  в  системму
     *
     * @param mixed $login
     * @param mixed $password
     * @return  boolean
     */

    public static function login($login, $password = null) {

        $user = User::findOne("  userlogin=  " . User::qstr($login));

        if ($user == null)
            return false;
        if ($user->userpass == $password)
            return $user;
        if (strlen($password) > 0) {
            $b = password_verify($password, $user->userpass);
            return $b ? $user : false;
        }
        return false;
    }

    /**
     * Проверка  существования логина
     *
     * @param mixed $login
     */
    public static function existsLogin($login) {
        $list = User::find("  userlogin= " . User::qstr($login));

        return count($list) > 0;
    }

    public static function generateMenu($meta_type) {
        $conn = \ZDB\DB::getConnect();
        $rows = $conn->Execute("select *  from metadata where meta_type= {$meta_type} and disabled <> 1 order  by  description ");
        $menu = array();
        $groups = array();
        $textmenu = "";
        $aclview = explode(',', System::getUser()->aclview);
        foreach ($rows as $meta_object) {
            $meta_id = $meta_object['meta_id'];

            if (!in_array($meta_id, $aclview) && System::getUser()->acltype == 2)
                continue;

            if (strlen($meta_object['menugroup']) == 0) {
                $menu[$meta_id] = $meta_object;
            } else {
                if (!isset($groups[$meta_object['menugroup']])) {
                    $groups[$meta_object['menugroup']] = array();
                }
                $groups[$meta_object['menugroup']][$meta_id] = $meta_object;
            }
            if ($meta_object->smart == 1) {
                
            }
        }
        switch ($meta_type) {
            case 1 :
                $dir = "Pages/Doc";
                break;
            case 2 :
                $dir = "Pages/Report";
                break;
            case 3 :
                $dir = "Pages/Register";
                break;
            case 4 :
                $dir = "Pages/Reference";
                break;
            case 5 :
                $dir = "Shop/Pages";
                break;
        }
        $textmenu = "";

        foreach ($menu as $item) {
            $textmenu .= "<li><a class=\"dropdown-item\" href=\"/?p=App/{$dir}/{$item['meta_name']}\">{$item['description']}</a></li>";
        }
        foreach ($groups as $gname => $group) {
            $textmenu .= "<li  ><a class=\"dropdown-item  dropdown-toggle\"     href=\"#\">$gname 
             
            </a>
            <ul class=\"dropdown-menu\">";

            foreach ($group as $item) {
                $textmenu .= "<li ><a class=\"dropdown-item\"   href=\"/?p=App/{$dir}/{$item['meta_name']}\">{$item['description']}</a></li>";
            }
            $textmenu .= "</ul></li>";
        }

        return $textmenu;
    }

    public static function generateSmartMenu() {
        $conn = \ZDB\DB::getConnect();

        $rows = $conn->Execute("select *  from  metadata where smartmenu =1 ");
        $textmenu = "";
        $aclview = explode(',', System::getUser()->aclview);

        foreach ($rows as $item) {

            if (!in_array($item['meta_id'], $aclview) && System::getUser()->acltype == 2)
                continue;


            switch ((int) $item['meta_type']) {
                case 1 :
                    $dir = "Pages/Doc";
                    break;
                case 2 :
                    $dir = "Pages/Report";
                    break;
                case 3 :
                    $dir = "Pages/Register";
                    break;
                case 4 :
                    $dir = "Pages/Reference";
                    break;
                case 5 :
                    $dir = "Shop/Pages";
                    break;
            }

            $textmenu .= " <a class=\"btn btn-sm btn-outline-primary mr-2\" href=\"/?p=App/{$dir}/{$item['meta_name']}\">{$item['description']}</a> ";
        }

        return $textmenu;
    }

    public static function loadEmail($template, $keys = array()) {
        global $logger;

        $templatepath = _ROOT . 'templates/email/' . $template . '.tpl';
        if (file_exists(strtolower($templatepath)) == false) {

            $logger->error($templatepath . " is wrong");
            return "";
        }
        $template = @file_get_contents(strtolower($templatepath));

        $m = new \Mustache_Engine();
        $template = $m->render($template, $keys);


        return $template;
    }

    /**
     * возвращает описание  мета-обьекта
     *
     * @param mixed $metaname
     */
    public static function getMetaNotes($metaname) {
        $conn = DB::getConnect();
        $sql = "select notes from  metadata where meta_name = '{$metaname}' ";
        return $conn->GetOne($sql);
    }

    public static function sendLetter($template, $email, $subject = "") {


        $_config = parse_ini_file(_ROOT . 'config/config.ini', true);


        $mail = new \PHPMailer();
        $mail->setFrom($_config['common']['emailfrom'], 'Биржа jobber');
        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->msgHTML($template);
        $mail->CharSet = "UTF-8";
        $mail->IsHTML(true);


        $mail->send();
        /*

          $from_name = '=?utf-8?B?' . base64_encode("Биржа jobber") . '?=';
          $subject = '=?utf-8?B?' . base64_encode($subject) . '?=';
          mail(
          $email,
          $subject,
          $template,
          "From: " . $from_name." <{$_config['common']['emailfrom']}>\r\n".
          "Content-type: text/html; charset=\"utf-8\""
          );
         */
    }

    /**
     * Запись  файла   в БД
     *
     * @param mixed $file
     * @param mixed $itemid ID  объекта
     * @param mixed $itemtype тип  объекта (документ - 0 )
     */
    public static function addFile($file, $itemid, $comment, $itemtype = 0) {
        $conn = DB::getConnect();
        $filename = $file['name'];

        $comment = $conn->qstr($comment);
        $filename = $conn->qstr($filename);
        $sql = "insert  into files (item_id,filename,description,item_type) values ({$itemid},{$filename},{$comment},{$itemtype}) ";
        $conn->Execute($sql);
        $id = $conn->Insert_ID();

        $data = file_get_contents($file['tmp_name']);
        $data = $conn->qstr($data);
        $sql = "insert  into filesdata (file_id,filedata) values ({$id},{$data}) ";
        $conn->Execute($sql);
    }

    /**
     * список  файдов  пррепленных  к  объекту
     *
     * @param mixed $item_id
     * @param mixed $item_type
     */
    public static function getFileList($item_id, $item_type = 0) {
        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute("select * from files where item_id={$item_id} and item_type={$item_type} ");
        $list = array();
        foreach ($rs as $row) {
            $item = new \App\DataItem();
            $item->file_id = $row['file_id'];
            $item->filename = $row['filename'];
            $item->description = $row['description'];


            $list[] = $item;
        }

        return $list;
    }

    /**
     * удаление  файла
     *
     * @param mixed $file_id
     */
    public static function deleteFile($file_id) {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete  from  files  where  file_id={$file_id}");
        $conn->Execute("delete  from  filesdata  where  file_id={$file_id}");
    }

    /**
     * Возвращает  файл  и  его  содержимое
     *
     * @param mixed $file_id
     */
    public static function loadFile($file_id) {
        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute("select filename,filedata from files join filesdata on files.file_id = filesdata.file_id  where files.file_id={$file_id}  ");
        foreach ($rs as $row) {
            return $row;
        }

        return null;
    }

    /**
     * возварщает список  документов
     *
     * @param mixed $id
     */
    public static function getDocTypes() {
        $conn = \ZDB\DB::getConnect();
        $groups = array();

        $rs = $conn->Execute('SELECT description,meta_id FROM   metadata where meta_type = 1 order by description');
        foreach ($rs as $row) {
            $groups[$row['meta_id']] = $row['description'];
        }
        return $groups;
    }

    /**
     * возварщает запись  метаданных
     *
     * @param mixed $id
     */
    public static function getMetaType($id) {
        if (is_array(self::$meta[$id]) == false) {
            $conn = DB::getConnect();
            $sql = "select * from   metadata where meta_id = " . $id;
            self::$meta[$id] = $conn->GetRow($sql);
        }

        return self::$meta[$id];
    }

    /**
     * логгирование
     * 
     * @param mixed $msg
     */
    public static function log($msg) {
        global $logger;
        $logger->debug($msg);
    }

    /**
     * Возвращает склад  по  умолчанию
     * 
     */
    public static function getDefStore() {
        $common = System::getOptions("common");
        if ($common['defstore'] > 0) {
            return $common['defstore'];
        }

        \App\System::setErrorMsg('Не настроен склад  по  умолчанию');
        \App\Application::RedirectHome();
    }

    public static function fqty($qty) {
        if(strlen($qty)==0) return '';         
        $digit = 0;
        $common = System::getOptions("common");
        if ($common['qtydigits'] > 0) {
            $digit = $common['qtydigits'];
        }
        if ($digit == 0) {
            return round($qty);
        } else {
            return number_format($qty, $digit, '.', '');
        }
    }

}
