<?php

namespace App;

use App\Entity\User;
use ZCL\DB\DB as DB;

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

        $user = User::getFirst("  userlogin=  " . User::qstr($login));

        if ($user == null) {
            return false;
        }

        if ($user->disabled == 1) {
            return false;
        }


        if ($user->userpass == $password) {
            return $user;
        }
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

    public static function logout() {

        setcookie("remember", '', 0);
        System::setUser(new \App\Entity\User());
        $_SESSION['user_id'] = 0;
        $_SESSION['userlogin'] = 'Гость';

        Application::Redirect("\\App\\Pages\\UserLogin");


    }

    public static function generateMenu($meta_type) {

        $conn = \ZDB\DB::getConnect();
        $rows = $conn->Execute("select *  from metadata where meta_type= {$meta_type} and disabled <> 1 order  by  description ");
        $menu = array();
        $groups = array();
        $user = System::getUser();
        $arraymenu = array("groups" => array(), "items" => array());

        $aclview = explode(',', $user->aclview);
        foreach ($rows as $meta_object) {
            $meta_id = $meta_object['meta_id'];

            if (!in_array($meta_id, $aclview) && $user->rolename != 'admins') {
                continue;
            }

            if (strlen($meta_object['menugroup']) == 0) {
                $menu[$meta_id] = $meta_object;
            } else {
                if (!isset($groups[$meta_object['menugroup']])) {
                    $groups[$meta_object['menugroup']] = array();
                }
                $groups[$meta_object['menugroup']][$meta_id] = $meta_object;
            }
        }
        switch($meta_type) {
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


            $arraymenu['groups'][] = array('grname' => $gname, 'items' => $items);
        }

        return $arraymenu;
    }

    public static function generateSmartMenu() {

        $conn = \ZDB\DB::getConnect();
        $user = System::getUser();
        $smartmenu = $user->smartmenu;

        if (strlen($smartmenu) == 0) {
            return "";
        }

        $rows = $conn->Execute("select *  from  metadata  where disabled <> 1 and  meta_id in ({$smartmenu})   ");

        $textmenu = "";
        $aclview = explode(',', $user->aclview);

        foreach ($rows as $item) {

            if (!in_array($item['meta_id'], $aclview) && $user->rolename != 'admins') {
                continue;
            }
            $icon = '';

            switch((int)$item['meta_type']) {
                case 1 :
                    $dir = "Pages/Doc";
                    $icon = "<i class=\"nav-icon fa fa-file\"></i>";
                    break;
                case 2 :
                    $dir = "Pages/Report";
                    $icon = "<i class=\"nav-icon fa fa-chart-bar\"></i>";
                    break;
                case 3 :
                    $dir = "Pages/Register";
                    $icon = "<i class=\"nav-icon fa fa-list\"></i>";
                    break;
                case 4 :
                    $dir = "Pages/Reference";
                    $icon = "<i class=\"nav-icon fa fa-book\"></i>";
                    break;
                case 5 :
                    $dir = "Pages/Service";
                    $icon = "<i class=\"nav-icon fas fa-project-diagrame\"></i>";
                    break;
            }

            $textmenu .= " <a class=\"btn btn-sm btn-outline-primary mb-1 mr-2\" href=\"/index.php?p=App/{$dir}/{$item['meta_name']}\">{$icon} {$item['description']}</a> ";
        }
        $role = \App\Entity\UserRole::load($user->role_id);

        $mod = self::modulesMetaData($role);
        $smartmenu = explode(',', $smartmenu);
        foreach ($mod as $p) {
            if (in_array($p->meta_id, $smartmenu)) {
                $textmenu .= " <a class=\"btn btn-sm btn-outline-primary mr-2\" href=\"/index.php?p=App/Modules{$p->meta_name}\">  <i class=\"nav-icon fa fa-puzzle-piece\"></i> {$p->description}</a> ";
            }
        }
        return $textmenu;
    }

    //метаданные   модулей
    public static function modulesMetaData($role) {
        global $_config;

        $mdata = array();
        if ($_config['modules']['note'] == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'note') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10000, 'meta_name' => "/Note/Pages/Main", 'meta_type' => 6, 'description' => self::l('modnotemain')));
            }
        }


        if ($_config['modules']['shop'] == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'shop') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10002, 'meta_name' => "/Shop/Pages/ProductList", 'meta_type' => 6, 'description' => self::l('modshopprlist')));
            }
        }


        if ($_config['modules']['ocstore'] == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'ocstore') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10005, 'meta_name' => "/OCStore/Orders", 'meta_type' => 6, 'description' => self::l('modocstoreorders')));
            }
        }
        if ($_config['modules']['ocstore'] == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'ocstore') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10006, 'meta_name' => "/OCStore/Items", 'meta_type' => 6, 'description' => self::l('modocstoreitems')));
            }
        }
        if ($_config['modules']['wc'] == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'wc') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10009, 'meta_name' => "/WC/Orders", 'meta_type' => 6, 'description' => self::l('modwcorders')));
            }
        }
        if ($_config['modules']['wc'] == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'wc') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10010, 'meta_name' => "/WC/Items", 'meta_type' => 6, 'description' => self::l('modwcitems')));
            }
        }

        if ($_config['modules']['issue'] == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'issue') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10014, 'meta_name' => "/Issue/Pages/IssueList", 'meta_type' => 6, 'description' => self::l('modissueslist')));
            }
        }
        if ($_config['modules']['tecdoc'] == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'tecdoc') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10101, 'meta_name' => "/Tecdoc/Search", 'meta_type' => 6, 'description' => self::l('modtecdocsearch')));
            }
        }
         
        return $mdata;
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
        global $_config;
        if (strlen($emailfrom) == 0) {
            $emailfrom = $_config['smtp']['user'];
        }

        try {

            $mail = new \PHPMailer\PHPMailer\PHPMailer();

            if ($_config['smtp']['usesmtp'] == true) {
                $mail->isSMTP();
                $mail->Host = $_config['smtp']['host'];
                $mail->Port = $_config['smtp']['port'];
                $mail->Username = $_config['smtp']['user'];
                $mail->Password = $_config['smtp']['pass'];
                $mail->SMTPAuth = true;
                if ($_config['smtp']['tls'] == true) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
            }


            $mail->setFrom($emailfrom);
            $mail->addAddress($emailto);
            $mail->Subject = $subject;
            $mail->msgHTML($template);
            $mail->CharSet = "UTF-8";
            $mail->IsHTML(true);
            if ($mail->send() === false) {
                System::setErrorMsg($mail->ErrorInfo);
            } else {
                //  System::setSuccessMsg(Helper::l('email_sent'));
            }
        } catch(\Exception $e) {
            System::setErrorMsg($e->getMessage());
        }

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
     * логгирование    ошибок
     *
     * @param mixed $msg
     */
    public static function logerror($msg) {
        global $logger;
        $logger->error($msg);
    }

    /**
     * Возвращает компанию  по  умолчанию
     *
     */
    public static function getDefFirm() {
        $user = System::getUser();
        if ($user->deffirm > 0) {
            return $user->deffirm;
        }
        $st = \App\Entity\Firm::getList();

        if (count($st) > 0) {
            $keys = array_keys($st);
            return $keys[0];
        }
        return 0;
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
        if (count($st) > 0) {
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
        if (count($st) > 0) {
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
        if (!is_array($common)) {
            $common = array();
        }
        $salesourceslist = $common['salesources'];
        if (is_array($salesourceslist) == false) {
            $salesourceslist = array();
        }
        $slist = array();
        foreach ($salesourceslist as $s) {
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
        if ($user->defsalesource > 0) {
            return $user->defsalesource;
        }

        $slist = Helper::getSaleSources();

        if (count($slist) > 0) {
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
        if (count($pt) > 0) {
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
        if (strlen($qty) == 0) {
            return '';
        }
        $qty = str_replace(',', '.', $qty);

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
        if (strlen($am) == 0) {
            return '';
        }
        $am = str_replace(',', '.', $am);
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
     * форматирование дат
     *
     * @param timestamp $date
     * @return mixed
     */
    public static function fd($date) {
        if ($date > 0) {
            $dateformat = System::getOption("common", 'dateformat');
            if (strlen($dateformat) == 0) {
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
    public static function fdt($date) {
        if ($date > 0) {
            $dateformat = System::getOption("common", 'dateformat');
            if (strlen($dateformat) == 0) {
                $dateformat = 'd.m.Y';
            }

            return date($dateformat . ' H:i', $date);
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
    public static function getFirmData($firm_id = 0, $branch_id = 0) {
        $data = array();
        if ($firm_id > 0) {
            $firm = \App\Entity\Firm::load($firm_id);
            if ($firm == null) {
                $firm = \App\Entity\Firm::load(self::getDefFirm());
            }
            if ($firm != null) {
                $data = $firm->getData();
            }
        } else {
            $firm = \App\Entity\Firm::load(self::getDefFirm());
            if ($firm != null) {
                $data = $firm->getData();
            }
        }

        if ($branch_id > 0) {
            $branch = \App\Entity\Branch::load($branch_id);

            if (strlen($branch->address) > 0) {
                $data['address'] = $branch->address;
            }
            if (strlen($branch->phone) > 0) {
                $data['phone'] = $branch->phone;
            }
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
     * длина  номера  телефона
     *
     */
    public static function PhoneL() {

        $phonel = System::getOption("common", 'phonel');
        if ($phonel > 0) {
            return $phonel;
        }
        return 10;
    }

    /**
     * Возвращает языковую метку
     *
     * @param mixed $label
     * @param mixed $p1
     * @param mixed $p2
     */
    public static function l($label, $p1 = "", $p2 = "", $p3 = "") {
        global $_config;

        $label = trim($label);
        if (strlen($label) == 0) {
            return '';
        }

        $labels = System::getCache('labels');
        if ($labels == null) {
            $lang = $_config['common']['lang'];
            $filename = _ROOT . 'templates/lang.json';
            if ($lang == 'ua') {
                $filename = _ROOT . 'templates_ua/lang.json';
            }
            $file = @file_get_contents($filename);

            if (strlen($file) == 0) {
                echo "Не найден файл локализации  " . $filename;
                die;
            }
            $labels = @json_decode($file, true);
            if ($labels == null) {
                echo "Неверный файл локализации  " . $filename;
                die;
            }
            System::setCache('labels', $labels);
        }
        if (isset($labels[$label])) {
            $text = $labels[$label];
            $text = sprintf($text, $p1, $p2, $p3);
            $text = str_replace("\'", "`", $text);
            return $text;
        } else {
            return $label;
        }
    }

    /**
     * Сумма прописью
     *
     */
    public static function sumstr($amount) {
        global $_config;
        $curr = \App\System::getOption('common', 'curr');

        $totalstr = \App\Util::money2str_rugr($amount);
        if ($curr == 'ru') {
            $totalstr = \App\Util::money2str_ru($amount);
        }
        if ($curr == 'eu') {
            $totalstr = \App\Util::money2str_eu($amount);
        }
        if ($curr == 'us') {
            $totalstr = \App\Util::money2str_us($amount);
        }


        if ($_config['common']['lang'] == 'ua') {
            $totalstr = \App\Util::money2str_ua($amount);
        }


        return $totalstr;
    }

    public static function getValList() {
        $val = \App\System::getOptions("val");
        $list = array();
        if ($val['valuan'] > 0 && $val['valuan'] != 1) {
            $list['valuan'] = self::l('valuan');
        }
        if ($val['valusd'] > 0 && $val['valusd'] != 1) {
            $list['valusd'] = self::l('valusd');
        }
        if ($val['valeuro'] > 0 && $val['valeuro'] != 1) {
            $list['valeuro'] = self::l('valeuro');
        }
        if ($val['valrub'] > 0 && $val['valrub'] != 1) {
            $list['valrub'] = self::l('valrub');
        }
        if ($val['valmdl'] > 0 && $val['valmdl'] != 1) {
            $list['valmdl'] = self::l('valmdl');
        }

        return $list;
    }

    public static function getValName($vn) {
        if ($vn == 'valuan') {
            return 'UAH';
        }
        if ($vn == 'valusd') {
            return 'USD';
        }
        if ($vn == 'valeuro') {
            return 'EUR';
        }
        if ($vn == 'valrub') {
            return 'RUB';
        }
        if ($vn == 'valmdl') {
            return 'MDL';
        }
    }

    public static function exportExcel($data, $header, $filename) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        foreach ($header as $k => $v) {

            $sheet->setCellValue($k, $v);
            $sheet->getStyle($k)->applyFromArray([
                'font'      => [
                    'bold' => true
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'wrapText'   => false,
                ]
            ]);
        }

        foreach ($data as $k => $v) {

            if (is_array($v)) {
                $c = $sheet->getCell($k);
                $style = $sheet->getStyle($k);
                if ($v['format'] == 'date') {
                    $v['value'] = date('d/m/Y', $v['value']);
                    $c->setValue($v['value']);
                    $style->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
                } else {
                    if ($v['format'] == 'number') {
                        $c->setValueExplicit($v['value'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    } else {
                        $c->setValueExplicit($v['value'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }
                if ($v['bold'] == true) {
                    $style->getFont()->setBold(true);
                }
                if ($v['align'] == 'right') {
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);;
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

}
