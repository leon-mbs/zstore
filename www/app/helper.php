<?php

namespace App;

use \App\Entity\User;
use \App\System;
use \App\Session;
use \ZCL\DB\DB as DB;

/**
 * Вспомагательный  класс  для  работы  с  бизнес-данными
 */
class Helper {

    private static $meta = array(); //кеширует метаданные

    /**
     * Выполняет  логин  в  системму
     *
     * @param mixed $login
     * @param mixed $password
     * @return  boolean
     */
    public static function login($login, $password = null) {

        $user = User::getFirst("  userlogin=  " . User::qstr($login));

        if ($user == null)
            return false;

        if ($user->disabled == 1)
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

        $arraymenu = array("groups" => array(), "items" => array());

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
                $dir = "Pages/Service";
                break;
        }


        foreach ($menu as $item) {

            $arraymenu['items'][] = array('name' => $item['description'], 'link' => "/index.php?p=App/{$dir}/{$item['meta_name']}");
        }
        $i = 1;
        foreach ($groups as $gname => $group) {

            $items = array();

            foreach ($group as $item) {

                $items[] = array('name' => $item['description'], 'link' => "/index.php?p=App/{$dir}/{$item['meta_name']}");
            }
            $textmenu .= "</ul></li>";

            $arraymenu['groups'][] = array('grname' => $gname, 'items' => $items);
        }

        return $arraymenu;
    }

    public static function generateSmartMenu() {
        $conn = \ZDB\DB::getConnect();

        $smartmenu = System::getUser()->smartmenu;

        if (strlen($smartmenu) == 0)
            return "";

        $rows = $conn->Execute("select *  from  metadata  where meta_id in ({$smartmenu})   ");

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
                    $dir = "Pages/Service";
                    break;
            }

            $textmenu .= " <a class=\"btn btn-sm btn-outline-primary mr-2\" href=\"/index.php?p=App/{$dir}/{$item['meta_name']}\">{$item['description']}</a> ";
        }

        return $textmenu;
    }

    public static function loadEmail($template, $keys = array()) {
        global $logger;

        $templatepath = _ROOT . 'templates/email/' . $template . '.tpl';
        if (file_exists($templatepath) == false) {

            $logger->error($templatepath . " is wrong");
            return "";
        }



        $template = @file_get_contents($templatepath);

        $m = new \Mustache_Engine();
        $template = $m->render($template, $keys);


        return $template;
    }

    public static function sendLetter($template, $emailfrom, $emailto, $subject = "") {




        $mail = new \PHPMailer();
        $mail->setFrom($emailfrom, 'Онлайн каталог');
        $mail->addAddress($emailto);
        $mail->Subject = $subject;
        $mail->msgHTML($template);
        $mail->CharSet = "UTF-8";
        $mail->IsHTML(true);


        $mail->send();

        /*
          $from_name = '=?utf-8?B?' . base64_encode("Онлайн каталог") . '?=';
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
        $imagedata = getimagesize($file["tmp_name"]);
        $mime = is_array($imagedata) ? $imagedata['mime'] : "";

        if (strpos($filename, '.pdf') > 0) {
            $mime = "application/pdf";
        }

        $comment = $conn->qstr($comment);
        $filename = $conn->qstr($filename);
        $sql = "insert  into files (item_id,filename,description,item_type,mime) values ({$itemid},{$filename},{$comment},{$itemtype},'{$mime}') ";
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
            $item->mime = $row['mime'];


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
        $rs = $conn->Execute("select filename,filedata,mime from files join filesdata on files.file_id = filesdata.file_id  where files.file_id={$file_id}  ");
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
        $user = System::getUser();
        if ($user->defstore > 0) {
            return $user->defstore;
        }
        $st = \App\Entity\Store::getList();
        if (count($st) == 1) {
            $keys = array_keys($st);
            return $keys[0];
        }
        return 0;
    }

    /**
     * Возвращает расчетный счет  по  умолчанию
     * 
     */
    public static function getDefMF() {
        $user = System::getUser();
        if ($user->defmf > 0) {
            return $user->defmf;
        }

        $st = \App\Entity\MoneyFund::getList();
        if (count($st) == 1) {
            $keys = array_keys($st);
            return $keys[0];
        }
        return 0;
    }

    /**
     * Форматирование количества
     * 
     * @param mixed $qty
     * @return mixed
     */
    public static function fqty($qty) {
        if (strlen($qty) == 0)
            return '';

        $common = System::getOptions("common");
        if ($common['qtydigits'] > 0) {
            return number_format($qty, $common['qtydigits'], '.', '');
        } else {
            return round($qty);
        }
    }

    /**
     * форматирование  сумм    с копейками
     * 
     * @param mixed $am
     * @return mixed
     */
    public static function fa($am) {
        if (strlen($am) == 0)
            return '';

        $common = System::getOptions("common");
        if ($common['amdigits'] == 1) {
            return number_format($am, 2, '.', '');
        }
        if ($common['amdigits'] == 5) {
            $am = round($am * 20) / 20;
            return number_format($am, 2, '.', '');
        }
        if ($common['amdigits'] == 10) {
            $am = round($am * 10) / 10;
            return number_format($am, 2, '.', '');
        }

        return round($am);
    }

    /**
     * возвращает  данные  фирмы.  Учитывает  филиал  если  задан
     */
    public static function getFirmData($id = 0) {

        $data = \App\System::getOptions("firm");
        if ($id > 0) {
            $branch = \App\Entity\Branch::load($id);
            if (strlen($branch->shopname) > 0)
                $data['shopname'] = $branch->shop_name;
            if (strlen($branch->address) > 0)
                $data['address'] = $branch->address;
            if (strlen($branch->phone) > 0)
                $data['phone'] = $branch->phone;
        }

        return $data;
    }

    /**
     * возвращает размер при пагинации
     * 
     * @param mixed $pagesize
     * @return mixed
     */
    public static function getPG($pagesize = 0) {


        if ($pagesize > 0) {
            return $pagesize;
        }
        $user = \App\System::getUser();
        if ($user->pagesize > 0) {
            return $user->pagesize;
        }
        return 25;
    }

    /**
    * Возвращает языковую метку
    * 
    * @param mixed $label    
    * @param mixed $p1      
    * @param mixed $p2
    */
    public static function l($label,$p1="",$p2="") {
         global $_config; 

         $label = trim($label);
         $labels = System::getCache('labels') ;
         if($labels==null){
            $lang = $_config['common']['lang'];
            $filename=_ROOT.'templates/lang.json' ;
            if($lang=='ua')$filename=_ROOT.'templates_ua/lang.json';
            $file = @file_get_contents($filename);
            
            if(strlen($file)==0) {
                echo "Не найден файл локализации  " .$filename;
                die; 
            }
            $labels=@json_decode( $file,true)  ;
            if($labels==null) {
                echo "Неверный файл локализации  " .$filename;
                die; 
            }
            System::setCache('labels',$labels) ;
            
         }
         if(isset($labels[$label])) {
            $text =  $labels[$label] ;
            $text = sprintf($text,$p1,$p2);
            return $text;
             
         }   else {
             return $label;
         }
         
         
         
    }
    
    /**
    * Сумма прописью
    * 
    */
    public static function sumstr($amount){
       global $_config; 
       $curr = \App\System::getOption('common','curr') ;
 
        $totalstr = \App\Util::money2str_rugr($amount);
        if($curr =='ru')$totalstr = \App\Util::money2str_ru($amount);
        if(false)$totalstr= \App\Util::money2str_ru($this->amount);
        
        if($_config['common']['lang']=='ua')  $totalstr= \App\Util::money2str_ua($amount);
      
      
      
      
      return $totalstr;
    }
}
