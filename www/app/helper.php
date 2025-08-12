<?php

namespace App;

use App\Entity\User;
use ZCL\DB\DB as DB;

/**
 * Вспомагательный  класс  для  работы  с  бизнес-данными
 */
class Helper
{
    public const STAT_HIT_SHOP = 1;     //посещение  онлайн  каталога
    public const STAT_ORDER_SHOP = 2;     //заказы  в  онлайн каталоге
    public const STAT_VIEW_ITEM = 3;     //просмотр товара
    public const STAT_PROMO = 4;     //промо код
    public const STAT_NEW_SHOP = 5;     //уникальнных  посетителей
    public const STAT_CARD_SHOP = 6;     //позиций в  корзине
    public const STAT_DOC_ISEDITED = 7;     //редактируется документ

    private static $meta = array(); //кеширует метаданные

    /**
     * Выполняет  логин  в  систему
     *
     * @param mixed $login
     * @param mixed $password
     */
    public static function login($login, $password = null) {

        $user = User::getFirst("  userlogin=  " . User::qstr($login));

        if($user == null) {
            return null;
        }

        if($user->disabled == 1) {
            return null;
        }


        if($user->userpass == $password) {
            return $user;
        }
        if(strlen($password) > 0) {
            $b = password_verify($password, $user->userpass);
            return $b ? $user : null;
        }
        return null;
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

    public static function logout() {

        System::clean();
        System::getSession()->clean();

        setcookie("remember", '', 0);
        System::setUser(new \App\Entity\User());
        $_SESSION['user_id'] = 0;
        $_SESSION['userlogin'] = 'Гiсть';

        Application::Redirect("\\App\\Pages\\UserLogin");


    }

    public static function generateMenu($meta_type) {
        $dir = '';
        $conn = \ZDB\DB::getConnect();
        $rows = $conn->Execute("select *  from metadata where meta_type= {$meta_type} and disabled <> 1 order  by  description ");
        $menu = array();
        $groups = array();
        $user = System::getUser();
        $arraymenu = array("groups" => array(), "items" => array());

        $aclview = explode(',', $user->aclview ?? '');
        foreach($rows as $meta_object) {
            $meta_id = $meta_object['meta_id'];

            if(!in_array($meta_id, $aclview) && $user->rolename != 'admins') {
                continue;
            }

            if(strlen($meta_object['menugroup']) == 0) {
                $menu[$meta_id] = $meta_object;
            } else {
                if(!isset($groups[$meta_object['menugroup']])) {
                    $groups[$meta_object['menugroup']] = array();
                }
                $groups[$meta_object['menugroup']][$meta_id] = $meta_object;
            }
        }
        switch($meta_type) {
            case 1:
                $dir = "Pages/Doc";
                break;
            case 2:
                $dir = "Pages/Report";
                break;
            case 3:
                $dir = "Pages/Register";
                break;
            case 4:
                $dir = "Pages/Reference";
                break;
            case 5:
                $dir = "Pages/Service";
                break;
        }


        foreach($menu as $item) {

            $arraymenu['items'][] = array('name' => $item['description'], 'link' => "/index.php?p=App/{$dir}/{$item['meta_name']}");
        }
        $i = 1;
        foreach($groups as $gname => $group) {

            $items = array();

            foreach($group as $item) {

                $items[] = array('name' => $item['description'], 'link' => "/index.php?p=App/{$dir}/{$item['meta_name']}");
            }


            $arraymenu['groups'][] = array('grname' => $gname, 'items' => $items);
        }

        return $arraymenu;
    }

    public static function generateSmartMenu() {

        $conn = \ZDB\DB::getConnect();
        $user = System::getUser();
        $smartmenu = $user->smartmenu;

        if(strlen($smartmenu) == 0) {
            return "";
        }

        $rows = $conn->Execute("select *  from  metadata  where disabled <> 1 and  meta_id in ({$smartmenu})   ");

        $textmenu = "";
        $aclview = explode(',', $user->aclview ?? '');

        foreach($rows as $item) {

            if(!in_array($item['meta_id'], $aclview) && $user->rolename != 'admins') {
                continue;
            }
            $icon = '';
            $dir = '';

            switch((int)$item['meta_type']) {
                case 1:
                    $dir = "Pages/Doc";
                    $icon = "<i class=\"nav-icon fa fa-file\"></i>";
                    break;
                case 2:
                    $dir = "Pages/Report";
                    $icon = "<i class=\"nav-icon fa fa-chart-bar\"></i>";
                    break;
                case 3:
                    $dir = "Pages/Register";
                    $icon = "<i class=\"nav-icon fa fa-list\"></i>";
                    break;
                case 4:
                    $dir = "Pages/Reference";
                    $icon = "<i class=\"nav-icon fa fa-book\"></i>";
                    break;
                case 5:
                    $dir = "Pages/Service";
                    $icon = "<i class=\"nav-icon fas fa-project-diagram\"></i>";
                    break;
            }

            $textmenu .= " <a class=\"btn btn-sm btn-outline-primary mb-1  \" href=\"/index.php?p=App/{$dir}/{$item['meta_name']}\">{$icon} {$item['description']}</a> ";
        }
        $role = \App\Entity\UserRole::load($user->role_id);

        $mod = self::modulesMetaData($role);
        $smartmenu = explode(',', $smartmenu);
        foreach($mod as $p) {
            if(in_array($p->meta_id, $smartmenu)) {
                $textmenu .= " <a class=\"btn btn-sm btn-outline-primary mb-1 mr-2\" href=\"/index.php?p=App/Modules{$p->meta_name}\">  <i class=\"nav-icon fa fa-puzzle-piece\"></i> {$p->description}</a> ";
            }
        }
        return $textmenu;
    }

    //метаданные   модулей
    public static function modulesMetaData($role) {

        $modules = \App\System::getOptions("modules");

        $mdata = array();
        if(($modules['note'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'note') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10000, 'meta_name' => "/Note/Pages/Main", 'meta_type' => 6, 'description' => "База знань"));
            }
        }


        if(($modules['shop'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'shop') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10002, 'meta_name' => "/Shop/Pages/Admin/ProductList", 'meta_type' => 6, 'description' => "Товари в онлайн каталозі"));
            }
        }


        if(($modules['wc'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'wc') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10009, 'meta_name' => "/WC/Orders", 'meta_type' => 6, 'description' => "Замовлення (WC)"));
            }
        }
        if(($modules['wc'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'wc') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10010, 'meta_name' => "/WC/Items", 'meta_type' => 6, 'description' => "Товари (WC)"));
            }
        }

        if(($modules['promua'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'promua') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10015, 'meta_name' => "/PU/Orders", 'meta_type' => 6, 'description' => "Замовлення (PU)"));
            }
        }

        if(($modules['issue'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'issue') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10014, 'meta_name' => "/Issue/Pages/IssueList", 'meta_type' => 6, 'description' => "Завдання (Проекти)"));
            }
        }
        if(($modules['issue'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'issue') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10017, 'meta_name' => "/Issue/Pages/ProjectList", 'meta_type' => 6, 'description' => "Проекти",));
            }
        }

        if(($modules['ocstore'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'ocstore') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10005, 'meta_name' => "/OCStore/Orders", 'meta_type' => 6, 'description' => "Замовлення (Опенкарт)"));
            }
        }
        if(($modules['ocstore'] ?? 0) == 1) {
            if($role->rolename == 'admins' || strpos($role->modules, 'ocstore') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10018, 'meta_name' => "/OCStore/Items", 'meta_type' => 6, 'description' => "Товари (Опенкарт)"));
            }
        }
      
        return $mdata;
    }

    public static function loadEmail($template, $keys = array()) {
        global $logger;

        $templatepath = _ROOT . 'templates/email/' . $template . '.tpl';
        if(file_exists($templatepath) == false) {

            $logger->error($templatepath . " is wrong");
            return "";
        }

        $template = @file_get_contents($templatepath);

        $m = new \Mustache_Engine();
        $template = $m->render($template, $keys);

        return $template;
    }

    public static function sendLetter($emailto, $text, $subject = "") {
        global $_config;

   

        $emailfrom = $_config['smtp']['emailfrom'];
        if(strlen($emailfrom) == 0) {
            $emailfrom = $_config['smtp']['user'];

        }

        try {

            $mail = new \PHPMailer\PHPMailer\PHPMailer();

            if($_config['smtp']['usesmtp'] == true) {
                $mail->isSMTP();
                $mail->Host = $_config['smtp']['host'];
                $mail->Port = $_config['smtp']['port'];
                $mail->Username = $_config['smtp']['user'];
                $mail->Password = $_config['smtp']['pass'];
                $mail->SMTPAuth = true;
                if($_config['smtp']['tls'] == true) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
            }


            $mail->setFrom($emailfrom);
            $mail->addAddress($emailto);
            $mail->Subject = $subject;
            $mail->msgHTML($text);
            $mail->CharSet = "UTF-8";
            $mail->IsHTML(true);
            //  $d = $mail->send() ;
            if($mail->send() === false) {
                System::setErrorMsg($mail->ErrorInfo);
                self::logerror($mail->ErrorInfo) ;
            } else {
                //  System::setSuccessMsg('E-mail відправлено');
            }
        } catch(\Exception $e) {
            System::setErrorMsg($e->getMessage());
            self::logerror($e->getMessage()) ;
        }

        /*
          $from_name = '=?utf-8?B?' . base64_encode("Онлайн каталог") . '?=';
          $subject = '=?utf-8?B?' . base64_encode($subject) . '?=';
          mail(
          $emailto,
          $subject,
          $text,
          "From: " . $from_name." <{$_config['smtp']['emailfrom']}>\r\n".
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

        if(strpos($filename, '.pdf') > 0) {
            $mime = "application/pdf";
        }

        $data = file_get_contents($file['tmp_name']);
       
        if(strlen($data) > (1024*1024*4) ) {
           // throw new \Exception('Розмір файлу більше 4M');
           return 0;
        }        
        
        $comment = $conn->qstr($comment);
        $filename = $conn->qstr($filename);
        $sql = "insert  into files (item_id,filename,description,item_type,mime) values ({$itemid},{$filename},{$comment},{$itemtype},'{$mime}') ";
        $conn->Execute($sql);
        $id = $conn->Insert_ID();

     

        $data = $conn->qstr($data);
        $sql = "insert  into filesdata (file_id,filedata) values ({$id},{$data}) ";
        $conn->Execute($sql);
        return $id;
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
        foreach($rs as $row) {
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
        foreach($rs as $row) {
            return $row;
        }

        return null;
    }

    /**
     * возварщает список  документов
     *
 
     */
    public static function getDocTypes() {
        $conn = \ZDB\DB::getConnect();
        $groups = array();

        $rs = $conn->Execute('SELECT description,meta_id FROM   metadata where meta_type = 1 order by description');
        foreach($rs as $row) {
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
        if(is_array(self::$meta[$id] ?? null) == false) {
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
    public static function logdebug($msg) {
        global $logger;
        $logger->debug($msg);
    }
    /**
     * логгирование
     *
     * @param mixed $msg
     */
    public static function log($msg) {
        global $logger;
        $logger->info($msg);
    }

    /**
     * логгирование    ошибок
     *
     * @param mixed $msg
     */
    public static function logerror($msg) {
        global $logger;
        $logger->error($msg);
    }

 

    /**
     * Возвращает склад  по  умолчанию
     *
     */
    public static function getDefStore() {
        $user = System::getUser();
        if($user->defstore > 0) {
            return $user->defstore;
        }
        $st = \App\Entity\Store::getList();
        if(count($st) > 0) {
            $keys = array_keys($st);
            return $keys[0];
        }
        return 0;
    }

    /**
     * Возвращает тип оплаты  по  умолчанию
     *
     */
    public static function getDefPayType() {
        $user = System::getUser();
   
        return intval($user->defpaytype);
      

     
    }
   /**
     * Возвращает расчетный счет  по  умолчанию
     *
     */
    public static function getDefMF() {
        $user = System::getUser();
        if($user->defmf > 0) {
            return $user->defmf;
        }

        $st = \App\Entity\MoneyFund::getList();
        if(count($st) > 0) {
            $keys = array_keys($st);
            return $keys[0];
        }
        return 0;
    }

    /**
     * источники  продаж
     *
     */
    public static function getSaleSources() {
        $common = System::getOptions("common");
        if(!is_array($common)) {
            $common = array();
        }
        $salesourceslist = $common['salesources'] ??'';
        if(is_array($salesourceslist) == false) {
            $salesourceslist = array();
        }
        $slist = array();
        foreach($salesourceslist as $s) {
            $slist[$s->id] = $s->name;
        }
        return $slist;
    }

    /**
     * Возвращает источник продаж  по  умолчанию
     *
     */
    public static function getDefSaleSource() {
        $user = System::getUser();
        if($user->defsalesource > 0) {
            return $user->defsalesource;
        }

        $slist = Helper::getSaleSources();

        if(count($slist) > 0) {
            $keys = array_keys($slist);
            return $keys[0];
        }
        return 0;
    }

    /**
     * Возвращает первый тип  цен  как  по  умолчанию
     *
     */
    public static function getDefPriceType() {

        $pt = \App\Entity\Item::getPriceTypeList();
        if(count($pt) > 0) {
            $keys = array_keys($pt);
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
        if(strlen('' . $qty) == 0) {
            return '';
        }
        if(is_numeric($qty) && abs($qty) < 0.0005) {
            $qty = 0;
        }
        $qty = str_replace(',', '.', $qty);
        $qty = preg_replace("/[^0-9\.\-]/", "", $qty);

        $common = System::getOptions("common");
        if($common['qtydigits'] > 0) {
            return number_format(doubleval($qty), $common['qtydigits'], '.', '');
        } else {
            return intval($qty);
        }
    }

    /**
     * форматирование  сумм  c  одной   цифрой  после  зарятой
     * например  для  сккидок
     * @param mixed $am
     * @return mixed
     */
    public static function fa1($am) {
        if(strlen($am) == 0) {
            return '';
        }
        if(is_numeric($am) && abs($am) < 0.005) {
            $am = 0;
        }

        $am = str_replace(',', '.', $am);

        $am = preg_replace("/[^0-9\.\-]/", "", $am);
        $am = trim($am);


        $am = doubleval($am);
        return @number_format($am, 1, '.', '');

    }

    /**
     * форматирование  сумм    с копейками
     *
     * @param mixed $am
     * @return mixed
     */
    public static function fa($am) {
        if(strlen($am ?? '') == 0) {
            return '';
        }
        if(is_numeric($am) && abs($am) < 0.005) {
            $am = 0;
        }
        $am = str_replace(',', '.', $am);
        $am = preg_replace("/[^0-9\.\-]/", "", $am);
        $am = trim($am);

        $am = doubleval($am);

        $common = System::getOptions("common");
        if($common['amdigits'] == 1) {
            return number_format($am, 2, '.', '');
        }
        if($common['amdigits'] == 5) {
            $am = round($am * 20) / 20;
            return number_format($am, 2, '.', '');
        }
        if($common['amdigits'] == 10) {
            $am = round($am * 10) / 10;
            return number_format($am, 2, '.', '');
        }

        return round($am);
    }

    /**
     * форматирование  сумм  c нулями  при  продаже
     *
     * @param mixed $am
     * @return mixed
     */
    public static function fasell($am) {
        $common = \App\System::getOptions("common");
        $ret = self::fa($am); 
        if ($common['sellcheck'] !=1   ) { 
            return $ret;
        }
        

        $ret = doubleval($ret);
        $ret = number_format($ret, 2, '.', '');

        return $ret;

    }

    /**
     * форматирование дат
     *
     * @return mixed
     */
    public static function fd($date) {
        if($date > 0) {
            $dateformat = System::getOption("common", 'dateformat');
            if(strlen($dateformat) == 0) {
                $dateformat = 'd.m.Y';
            }

            return date($dateformat, $date);
        }

        return '';
    }

    /**
     * форматирование  даты и времени
     *
     * @param mixed $date
     * @return mixed
     */
    public static function fdt($date, $seconds = false) {
        if($date > 0) {
            $dateformat = System::getOption("common", 'dateformat');
            if(strlen($dateformat) == 0) {
                $dateformat = 'd.m.Y';
            }

            return $seconds ? date($dateformat . ' H:i:s', $date) : date($dateformat . ' H:i', $date);
        }

        return '';
    }

    /**
     * форматирование  времени
     * @param mixed $date
     * @return mixed
     */
    public static function ft($date) {
        return date(' H:i', $date);
    }

    /**
     * возвращает  данные  фирмы.  Учитывает  филиал  если  задан
     */
    public static function getFirmData(  $branch_id = 0) {
        
         
        $data = System::getOptions("firm");
 

        if($branch_id > 0) {
            $branch = \App\Entity\Branch::load($branch_id);

            if(strlen($branch->address) > 0) {
                $data['address'] = $branch->address;
            }
            if(strlen($branch->phone) > 0) {
                $data['phone'] = $branch->phone;
            }
        }

        $user = System::getUser() ;
        if(strlen($user->payname ??'')>0)   $data['firm_name']  = $user->payname;
        if(strlen($user->address ??'')>0)   $data['address']  = $user->address;
        if(strlen($user->tin ??'')>0)   $data['tin']  = $user->tin;
         
        return $data;
    }

    /**
     * возвращает размер при пагинации
     *
     * @param mixed $pagesize
     * @return mixed
     */
    public static function getPG($pagesize = 0) {


        if($pagesize > 0) {
            return $pagesize;
        }
        $user = \App\System::getUser();
        if($user->pagesize > 0) {
            return $user->pagesize;
        }
        return 25;
    }

    /**
     * длина  номера  телефона
     *
     */
    public static function PhoneL() {

        $phonel = System::getOption("common", 'phonel');
        if($phonel > 0) {
            return $phonel;
        }
        return 10;
    }


    /**
     * список валют
     *
     */
    public static function getValList() {
        $val = \App\System::getOptions("val");
        if(!is_array($val['vallist'])) {
            $val['vallist'] = array();
        }
        $list = array();
        foreach($val['vallist'] as $v) {
            $list[$v->code] = $v->name;
        }

        return $list;
    }

    /**
     * название  валюты
     *
     * @param mixed $vn
     * @return mixed
     */
    public static function getValName($vn) {
        if($vn == 'Гривня') {
            return 'UAH';
        }
        if($vn == 'Долар') {
            return 'USD';
        }
        if($vn == 'Євро') {
            return 'EUR';
        }
        if($vn == 'Рубль') {
            return 'RUB';
        }
        if($vn == 'Лей') {
            return 'MDL';
        }
    }

    public static function exportXML($xml, $filename) {
        header("Content-type: text/xml");
        header("Content-Disposition: attachment;Filename={$filename}");
        header("Content-Transfer-Encoding: binary");

        echo $xml;
        die;
    }

    public static function exportExcel($data, $header, $filename) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        foreach($header as $k => $v) {

            $sheet->setCellValue($k, $v);
            $sheet->getStyle($k)->applyFromArray([
                'font' => [
                    'bold' => true
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'wrapText' => false,
                ]
            ]);
        }

        foreach($data as $k => $v) {

            if(is_array($v)) {
                $c = $sheet->getCell($k);
                $style = $sheet->getStyle($k);
                if($v['format'] == 'date') {
                    $v['value'] = date('d/m/Y', $v['value']);
                    $c->setValue($v['value']);
                    $style->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
                } else {
                    if($v['format'] == 'number') {
                        $c->setValueExplicit($v['value'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    } else {
                        $c->setValueExplicit($v['value'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }
                if($v['bold'] == true) {
                    $style->getFont()->setBold(true);
                }
                if($v['align'] == 'right') {
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
            } else {
                //  $sheet->setCellValue($k, $v );
                $c = $sheet->getCell($k);
                $c->setValue($v);
                $c->setValueExplicit($v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        /*
          $sheet->getStyle('A1')->applyFromArray([
          'font' => [
          'name' => 'Arial',
          'bold' => true,
          'italic' => false,
          'underline' => Font::UNDERLINE_DOUBLE,
          'strikethrough' => false,
          'color' => [
          'rgb' => '808080'
          ]
          ],
          'borders' => [
          'allBorders' => [
          'borderStyle' => Border::BORDER_THIN,
          'color' => [
          'rgb' => '808080'
          ]
          ],
          ],
          'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_CENTER,
          'vertical' => Alignment::VERTICAL_CENTER,
          'wrapText' => true,
          ]
          ]);

         */
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        die;
    }


    public static function exportExcelFromCSV($csvfile) {

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

        $spreadsheet = $reader->loadSpreadsheetFromString(file_get_contents($csvfile));

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . "fromcsv.xlsx" . '"');
        $writer->save('php://output');
        die;


    }


    /**
     * Получение  данных с  таблицы ключ-значение
     *
     * @param mixed $key
     * @return mixed
     */
    public static function getKeyVal($key, $def = "") {
        if(strlen($key) == 0) {
            return;
        }
        $conn = \ZDB\DB::getConnect();

        $ret = $conn->GetOne("select vald from  keyval  where  keyd=" . $conn->qstr($key));

        if(strlen($ret) == 0) {
            $ret = "";
        }

        if($ret == '' && $def != '') {
            $ret = $def;
        }

        return $ret;
    }

    public static function getKeyValInt($key, $def = 0): int {

        $ret = intval(self::getKeyVal($key));
        if($ret == 0 && $def != 0) {
            $ret = $def;
        }
        return $ret;
    }

    public static function getKeyValBool($key): bool {

        $ret = self::getKeyVal($key);
        if($ret == true || $ret == "true" || $ret == "TRUE" || $ret == 1 || $ret == "1") {
            return true;
        }

        return false;
    }


    /**
     * Вставка  данных в  таблицу ключ-значение
     *
     * @param mixed $key
     * @param mixed $data
     * @return mixed
     */
    public static function setKeyVal($key, $data = null) {
        if(strlen($key) == 0) {
            return;
        }
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete  from  keyval  where  keyd=" . $conn->qstr($key));
        if($data === null) {
            return;
        }
        $conn->Execute("insert into keyval  (  keyd,vald)  values (" . $conn->qstr($key) . "," . $conn->qstr($data) . ")");


    }


    /**
     * Вставка  данных  в  таблицу  статистики
     *
     * @param int $cat
     * @param int $key
     * @param int $data
     * @return mixed
     */
    public static function insertstat(int $cat, int $key, int $data) {
        if($cat == 0) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $dt = $conn->DBTimeStamp(time());
        $conn->Execute("insert into stats  ( category, keyd,vald,dt)  values ({$cat},{$key},{$data},{$dt})");


    }


    /**
     * Печать  этикеток     
     *
     * @param array $items  ТМЦ
     * @param mixed $pqty  явное  указание  количества копий
     * @param array $tags  дополнительные поля
     */
    public static function printItems(array $items, $pqty = 0, array $tags = []) {
        $user = \App\System::getUser();

        $printer = \App\System::getOptions('printer');

        $prturn = \App\System::getUser()->prturn;

        $htmls = "";
        $rows = [];
        
        
        if($user->prtypelabel == 0) {
            $report = new \App\Report('item_tag.tpl');
        }
        if($user->prtypelabel == 1) {
            $report = new \App\Report('item_tag_ps.tpl');
        }
        if($user->prtypelabel == 2) {
            $report = new \App\Report('item_tag_ts.tpl');
        }
        foreach($items as $item) {
            if(intval($item->item_id) == 0) {
                continue;
            }
            $header = [];
            $header['turn'] = '';
            if($prturn == 1) {
                $header['turn'] = 'transform: rotate(90deg);';
            }
            if($prturn == 2) {
                $header['turn'] = 'transform: rotate(-90deg);';
            }


            if(strlen($item->shortname) > 0) {
                $header['name'] = $item->shortname;
            } else {
                $header['name'] = $item->itemname;
            }

            $header['name'] = str_replace("'", "`", $header['name']);
            $header['description'] = str_replace("'", "`", $item->description);

            $header['docnumber'] = $tags['docnumber'] ?? "";

            $header['isprice'] = $printer['pprice'] == 1;
            $header['isarticle'] = $printer['pcode'] == 1;
            $header['isbarcode'] = false;
            $header['isqrcode'] = false;
            $header['isweight'] = $item->isweight ==1 && $item->quantity > 0 ;


            $header['article'] = $item->item_code;
            $header['garterm'] = $item->warranty;
            $header['country'] = $item->country;
            $header['brand'] = $item->manufacturer;
            $header['notes'] = $item->notes;
            $header['quantity'] = $item->quantity;


            if(strlen($item->url) > 0 && $printer['pqrcode'] == 1) {
               
                if($user->prtypelabel == 0) {
                    $writer = new \Endroid\QrCode\Writer\PngWriter();

                    $qrCode = new \Endroid\QrCode\QrCode($item->url);

                    $qrCode->setSize(500);
                    $qrCode->setMargin(5);

                    $result = $writer->write($qrCode);

                    $dataUri = $result->getDataUri();
                    $header['qrcodeattr'] = "src=\"{$dataUri}\"  ";
                }
                $header['qrcode'] = $item->url;
                $header['isqrcode'] = true;

            }


            if($printer['pbarcode'] == 1) {

                $barcode = $item->bar_code;
                if(strlen($barcode) == 0) {
                    $barcode = $item->item_code;
                }   
                $header['barcode'] = $barcode;
                $header['isbarcode'] = true;                 
                
                if(strlen($barcode) > 0) {
                   if($user->prtypelabel == 0) {
                        try{
                            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                            $da = " src=\"data:image/png;base64," . base64_encode($generator->getBarcode($barcode, $printer['barcodetype'])) . "\"";
                            $header['barcodeattr'] = $da;
                            $header['barcodewide'] = \App\Util::addSpaces($barcode);
                         
                        } catch (\Throwable $e) {
                           Helper::logerror("barcode: ".$e->getMessage()) ;
                        }
                   }
                }
            }

            $header['price'] = self::fa($item->getPrice($printer['pricetype']));
            if(doubleval($item->price) > 0) {
                $header['price'] = self::fa($item->price);  //по  документу
            }


            $qty = intval($item->getQuantity());


            $printqty = intval($item->printqty);
            if($printqty == 5) { //не печатать
               continue;
            }

            if($printqty == 0) {
                $printqty = 1;
            }

            if($printqty == 1) {
                $qty = 1;
            }
            if($printqty == 2) {
                $qty = 2;
            }
            if($printqty == 3) ;
            if($printqty == 4) {
                if($qty > 10) {
                    $qty = 10;
                }
            }
            if(intval($item->quantity) > 0) {
                $qty = intval($item->quantity);  //по  документу
            }
            if($pqty > 0) {
                $qty = $pqty;
            }
            if($item->isweight ==1) {
                $qty = 1;  //весовой товар
            }
            
            
            //кастомные поля
            foreach($item->getcf() as $cf){
             //  $v=  str_replace("\"", "`", $v);
             //  $v=  str_replace("'", "`", $v);
               $header['cf_'.$cf->code]  = $cf->val; 
            }
            
            if($user->prtypelabel == 2) {
                $header['name'] = str_replace("\"", "`", $header['name']);
                $header['description'] = str_replace("\"", "`", $header['description']);
                $header['qrcode'] = str_replace("\"", "`", $header['qrcode']);
                $header['brand'] = str_replace("\"", "`", $header['brand']);

                if($user->pwsymlabel > 0) {
                    $header['name'] = mb_substr($header['name'], 0, $user->pwsymlabel);
                }


                $text = $report->generate($header, false);

                $r = explode("\n", $text);

                for($i = 0; $i < intval($qty); $i++) {

                    foreach($r as $row) {
                        $row = str_replace("\n", "", $row);
                        $row = str_replace("\r", "", $row);
                        $row = trim($row);
                        if($row != "") {
                           $rows[] = $row;  
                        }
                    }
                }

            } else {
                for($i = 0; $i < intval($qty); $i++) {
                    $htmls = $htmls . $report->generate($header);
                }
            }           
         

        }
        

        if($user->prtypelabel == 2) {
            return $rows;
        } else {
            $htmls = str_replace("\'", "", $htmls);
            return $htmls;
        }
    }


  
    //"соль" для  шифрования
    public static function getSalt() {
        $salt = self::getKeyVal('salt');
        if(strlen($salt ?? '') == 0) {
            $salt = '' . rand(1000, 999999);
            self::setKeyVal('salt', $salt);
        }
        return $salt;
    }

    /**
     * шифрование по паролю
     *
     * @param mixed $data
     * @param mixed $password
     */
    public static function encrypt($data, $password) {

        // Storingthe cipher method
        $ciphering = "AES-128-CTR";

        // Using OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;

        // Non-NULL Initialization Vector for encryption
        $iv = substr(md5($password), $iv_length);


        // Using openssl_encrypt() function to encrypt the data
        $encryption = openssl_encrypt($data, $ciphering, $password, $options, $iv);

        return $encryption;

    }

    /**
     * дешифрование
     *
     * @param mixed $data
     * @param mixed $password
     */
    public static function decrypt($data, $password) {
        // Storingthe cipher method
        $ciphering = "AES-128-CTR";

        // Using OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;

        // Non-NULL Initialization Vector for encryption
        $iv = substr(md5($password), $iv_length);

        $decryption = openssl_decrypt($data, $ciphering, $password, $options, $iv);

        return $decryption;
    }


    /**
     * проверка  новой версии
     * @deprecated
     */
    public static function checkVer() {

        $phpv = phpversion();
        $conn = \ZDB\DB::getConnect();

        $nocache = "?t=" . time() . "&s=" . Helper::getSalt() . '&phpv=' . $phpv . '_' . \App\System::CURR_VERSION;

        $v = @file_get_contents("https://zippy.com.ua/checkver.php" . $nocache);
        $v = @json_decode($v, true);
        if(!is_array($v)) {
            $v = @file_get_contents("https://zippy.com.ua/version.json" . $nocache);
            $v = @json_decode($v, true);

        }
        if(strlen($v['version']) > 0) {
            $c = str_replace("v", "", \App\System::CURR_VERSION);
            $n = str_replace("v", "", $v['version']);

            $ca = explode('.', $c);
            $na = explode('.', $n);

            if($na[0] > $ca[0] || $na[1] > $ca[1] || $na[2] > $ca[2]) {
                return $v['version'];
            }

        }

        return '';
    }

    /**
     * выполняет перенос  данных на  новой  версии
     *
     */
    public static function migration() {
        global $logger;
        $conn = \ZDB\DB::getConnect();

        $vdb=\App\System::getOptions('version',true ) ;
        $common=\App\System::getOptions('common' ) ;
     
        $migrationbonus = \App\Helper::getKeyVal('migrationbonus'); 
        if($migrationbonus != "done" &&version_compare($vdb,'6.11.0')>=0  )    {
            Helper::log("Міграція бонус");
            $conn->BeginTrans();
            try {
                $conn->Execute("delete from custacc where optype=1 ");

                $conn->Execute("INSERT INTO custacc (amount,document_id,customer_id,optype,createdon) 
                                  SELECT bonus,document_id, customer_id,1,paydate FROM paylist_view WHERE  paytype=1001 AND  customer_id IS NOT null;     ");


                \App\Helper::setKeyVal('migrationbonus', "done");
                $conn->CommitTrans();

            } catch(\Throwable $ee) {
                
                $conn->RollbackTrans();
                System::setErrorMsg($ee->getMessage());
                $logger->error($ee->getMessage());
                return;
            }


        }


        $migrationbalans = \App\Helper::getKeyVal('migrationbalans'); //6.11.2
        if($migrationbalans != "done" && version_compare($vdb,'6.11.0')>=0) {
            Helper::log("Міграція баланс");
            //  + контрагента (active)  - наш кредитовый  долг
            //  - контрагента (passive)  - наш дебетовый  долг
            $conn->BeginTrans();
            try {
                $conn->Execute("delete from custacc where optype=2 or optype=3 ");

                $sql = "SELECT
                          COALESCE(SUM((CASE WHEN (d.meta_name IN ('InvoiceCust', 'GoodsReceipt', 'IncomeService')) THEN d.payed WHEN ((d.meta_name = 'OutcomeMoney') AND
                              (d.content LIKE '%<detail>2</detail>%')) THEN d.payed WHEN (d.meta_name = 'RetCustIssue') THEN d.payamount ELSE 0 END)), 0) AS s_passive,
                          COALESCE(SUM((CASE WHEN (d.meta_name IN ('IncomeService', 'GoodsReceipt')) THEN d.payamount WHEN ((d.meta_name = 'IncomeMoney') AND
                              (d.content LIKE '%<detail>2</detail>%')) THEN d.payed WHEN (d.meta_name = 'RetCustIssue') THEN d.payed ELSE 0 END)), 0) AS s_active,
                          COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsIssue', 'TTN', 'PosCheck', 'OrderFood', 'ServiceAct')) THEN d.payamount WHEN ((d.meta_name = 'OutcomeMoney') AND
                              (d.content LIKE '%<detail>1</detail>%')) THEN d.payed WHEN (d.meta_name = 'ReturnIssue') THEN d.payed ELSE 0 END)), 0) AS b_passive,
                          COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct')) THEN d.payed WHEN ((d.meta_name = 'IncomeMoney') AND
                              (d.content LIKE '%<detail>1</detail>%')) THEN d.payed WHEN (d.meta_name = 'ReturnIssue') THEN d.payamount ELSE 0 END)), 0) AS b_active,
                          d.customer_id , d.document_id
                        FROM documents_view d
                        WHERE d.state NOT IN (0, 1, 2, 3, 15, 8, 17)
                        AND d.customer_id > 0 
                      
                        GROUP BY d.customer_id,d.document_id order  by d.document_id";

                foreach($conn->Execute($sql) as $row) {
                    $s_active = doubleval($row['s_active']);
                    $s_passive = doubleval($row['s_passive']);
                    $b_active = doubleval($row['b_active']);
                    $b_passive = doubleval($row['b_passive']);

                    //  if($s_active != $s_passive) {
                    if($s_active > 0) {
                        $conn->Execute("insert into custacc (customer_id,document_id,optype,amount) values ({$row['customer_id']},{$row['document_id']},3,{$s_active})  ");
                    }
                    if($s_passive > 0) {
                        $s_passive = 0 - $s_passive;
                        $conn->Execute("insert into custacc (customer_id,document_id,optype,amount) values ({$row['customer_id']},{$row['document_id']},3,{$s_passive})  ");
                    }
                    // }
                    //  if($b_active != $b_passive) {

                    if($b_active > 0) {
                        $conn->Execute("insert into custacc (customer_id,document_id,optype,amount) values ({$row['customer_id']},{$row['document_id']},2,{$b_active})  ");
                    }
                    if($b_passive > 0) {
                        $b_passive = 0 - $b_passive;
                        $conn->Execute("insert into custacc (customer_id,document_id,optype,amount) values ({$row['customer_id']},{$row['document_id']},2,{$b_passive})  ");
                    }
                    //  }


                }

                \App\Helper::setKeyVal('migrationbalans', "done");
                $conn->CommitTrans();

            } catch(\Throwable $ee) {
              
                $conn->RollbackTrans();
                System::setErrorMsg($ee->getMessage());
                $logger->error($ee->getMessage());
                return;
            }
        }
       
        $migration6118 = \App\Helper::getKeyVal('migration6118'); 
        if($migration6118 != "done"  ) {
            Helper::log("Міграція 6118");
         
            \App\Helper::setKeyVal('migration6118', "done");           
        
            try {
          
                 
                 $w=  $conn->GetOne("select count(*) from metadata where meta_name='SalaryList' ");
                 if(intval($w)==0){
                      $conn->Execute("INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Зарплата', 'SalaryList', 'Каса та платежі', 0) ");
                 }
              
               
       
                 $w=  $conn->Execute("SHOW INDEXES FROM   documents ");
                           
                 foreach($w as $e){
                     if($e['Key_name']=='unuqnumber'){
                          $conn->Execute("ALTER TABLE documents DROP INDEX `unuqnumber` ");
                     }             
      
                 }
              
                       
            } catch(\Throwable $ee) {
         
                $logger->error($ee->getMessage());
               
            }           
           
        }
        
        
        $migration12 = \App\Helper::getKeyVal('migration12');  
        if($migration12 != "done" && version_compare($vdb,'6.12.0')>=0) {
            Helper::log("Міграція 12");
         
            \App\Helper::setKeyVal('migration12', "done");           
         
        
            try {
          
              foreach( \App\Entity\PromoCode::find("" ) as $p) {
                  $p->enddate = $p->dateto ; 
                  $p->save();
              }   
              foreach( \App\Entity\Equipment::find("" ) as $e) {
                  $e->invnumber = $e->code ; 
                  $e->pa_id = $e->pa_id_old ; 
                  $e->emp_id = $e->emp_id_old ; 
                  $e->invnumber = $e->code ; 
               
                  $e->save();
              }   
                     
                       
            } catch(\Throwable $ee) {
         
                $logger->error($ee->getMessage());
               
            }  
        }
            
        $migration6150 = \App\Helper::getKeyVal('migration6150'); 
        if($migration6150 != "done" && version_compare($vdb,'6.15.0')>=0  ) {
        //    Helper::log("Міграція 6150");
         
            $cnt= intval($conn->GetOne("select count(*) from documents_view where state > 4 and meta_name='OrderFood' ") );
            if($cnt > 0){
               $common['usefood'] = 1;
               System::setOptions("common",$common) ;
            }
            $cnt= intval($conn->GetOne("select count(*) from documents_view where state > 4 and meta_name in('ProdReceipt', 'ProdIssue') ") );
            if($cnt > 0){
               $common['useprod'] = 1;
               System::setOptions("common",$common) ;
            }
            Session::getSession()->menu = [];     
         
            \App\Helper::setKeyVal('migration6150', "done");           
        
       
        }       
    }


}
