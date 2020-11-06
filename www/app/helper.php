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


            $arraymenu['groups'][] = array('grname' => $gname, 'items' => $items);
        }

        return $arraymenu;
    }

    public static function generateSmartMenu() {
        $conn = \ZDB\DB::getConnect();

        $smartmenu = System::getUser()->smartmenu;

        if (strlen($smartmenu) == 0) {
            return "";
        }

        $rows = $conn->Execute("select *  from  metadata  where meta_id in ({$smartmenu})   ");

        $textmenu = "";
        $aclview = explode(',', System::getUser()->aclview);

        foreach ($rows as $item) {

            if (!in_array($item['meta_id'], $aclview) && System::getUser()->rolename != 'admins') {
                continue;
            }


            switch ((int)$item['meta_type']) {
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

        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->setFrom($emailfrom);
        $mail->addAddress($emailto);
        $mail->Subject = $subject;
        $mail->msgHTML($template);
        $mail->CharSet = "UTF-8";
        $mail->IsHTML(true);
        // $mail->AddAttachment($_SERVER['DOCUMENT_ROOT'].'/facturen/test.pdf', $name = 'test',  $encoding = 'base64', $type = 'application/pdf');
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
     * Возвращает компанию  по  умолчанию
     *
     */
    public static function getDefFirm() {
        $user = System::getUser();
        if ($user->deffirm > 0) {
            return $user->deffirm;
        }
        $st = \App\Entity\Firm::getList();

        if (count($st) >0) {
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
        if (count($st) >0) {
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
        if (count($st) >0) {
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
        if (strlen($qty) == 0) {
            return '';
        }

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
     * возвращает  данные  фирмы.  Учитывает  филиал  если  задан
     */
    public static function getFirmData($firm_id = 0, $branch_id = 0) {
        $data = array();
        if ($firm_id > 0) {
            $firm = \App\Entity\Firm::load($firm_id);
            if ($firm == null) {
                $firm = \App\Entity\Firm::load(self::getDefFirm());
            }
            if($firm!=null)$data = $firm->getData();
        } else {
            $firm = \App\Entity\Firm::load(self::getDefFirm());
            if($firm!=null)$data = $firm->getData();
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
     * Возвращает языковую метку
     *
     * @param mixed $label
     * @param mixed $p1
     * @param mixed $p2
     */
    public static function l($label, $p1 = "", $p2 = "") {
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
            $text = sprintf($text, $p1, $p2);
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
        if (false) {
            $totalstr = \App\Util::money2str_ru($amount);
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
            $list['valuan'] = 'Гривна';
        }
        if ($val['valusd'] > 0 && $val['valusd'] != 1) {
            $list['valusd'] = 'Доллар';
        }
        if ($val['valeuro'] > 0 && $val['valeuro'] != 1) {
            $list['valeuro'] = 'Евро';
        }
        if ($val['valrub'] > 0 && $val['valrub'] != 1) {
            $list['valrub'] = 'Рубль';
        }

        return $list;
    }


    //фукции  для  фискализации
   public static  function guid(){
 
        if (function_exists('com_create_guid') === true)
            return trim(com_create_guid(), '{}');

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        
    }
    
    public static function sign($data,$cid){
        $c = \App\Entity\Firm::load($cid);
        
    
        $ap = explode(':',$c->pposerv)  ;
  
  
        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT => $ap[1],
            CURLOPT_URL =>  "{$ap[0]}:{$ap[1]}/sign",
            CURLOPT_POST => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => $data
        ]);

        $return = json_decode(curl_exec($request));

        if(curl_errno($request) > 0)
           {
             
               
             throw new  \Exception('Curl error: ' . curl_error($request)) ;
             
           }  
         

        curl_close($request);

        return $return;
    }
 
    public static function decrypt($data,$cid){
        $c = \App\Entity\Firm::load($cid);
        
    
        $ap = explode(':',$c->pposerv)  ;
  
  
        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT => $ap[1],
            CURLOPT_URL =>  "{$ap[0]}:{$ap[1]}/decrypt",
            CURLOPT_POST => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => $data
        ]);

        $return =  (curl_exec($request));

        if(curl_errno($request) > 0)
           {
             System::setErrorMsg('Curl error: ' . curl_error($request)) ; 
               
             return false;
             
           }           

        curl_close($request);

        return $return;
    }
 
    public  static  function send($data,$type,$cid,$encrypted=false){
     
        $signed = Helper::sign($data,$cid);
        if($signed->success==true){
            
            
            
            $request = curl_init();

            $data =  base64_decode($signed->data) ;
             
            curl_setopt_array($request, [
                CURLOPT_URL =>  "http://80.91.165.208:8609/fs/{$type}",
                CURLOPT_POST => true,
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => array('Content-Type: application/octet-stream', "Content-Length: ".strlen($data)),
                CURLOPT_ENCODING => "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_VERBOSE => 1,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POSTFIELDS => $data
            ]);

            $return = curl_exec($request);
            
          if(curl_errno($request) > 0)
           {
             throw new  \Exception('Curl error: ' . curl_error($request)) ;
             
           }      
           if(strpos($return,'помилки') >0)
           {
             throw new  \Exception($return) ;
             
           }  
            curl_close($request);            
            
            if($encrypted) {
                        $return = base64_encode($return) ;
                        $decrypted =    Helper::decrypt($return,$cid); 
                        $decrypted = json_decode($decrypted) ;
                        if($decrypted->success==true){
                             return  base64_decode($decrypted->data)  ;
                        
                        }  
                        else{
                          return  false;  
                        }

            }  else {
                return   ($return)  ;
            }
            
           
              
           
            
         }  else {
             
            return false;     
         } 
     
        
        
        
    }
     
    public static function  OnZ($cid,$posid ) {
        
    }
    
    public static function  shift($cid,$posid,$open) {
        $pos = \App\Entity\Pos::load($posid) ;
       
        $branch_id= \App\Session::getSession()->branch_id;  
        $firm = Helper::getFirmData($cid,$branch_id);
        $branch = \App\Entity\Branch::load($branch_id);
                
        $header = array( );
        $header['doctype'] = $open==true ?100:101 ;
        $header['firmname'] = $firm['firmname']  ;
        $header['inn'] = $firm['inn'];
        $header['edrpou'] =  $firm['tin'];
        $header['address'] = $firm['address']  ; 
        $header['branchname'] = strlen($branch->branch_name)>0 ?  $branch->branch_name : $firm['firmname']  ;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscalnumber ;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc;
        $header['username'] =   \App\System::getUser()->username  ;
        $header['guid'] = Helper::guid();
   
       
        $report = new \App\Report('shift.xml');
        
        $xml = $report->generate($header);
 
    //     $file =  "z://home/local.zstore//www//upload//test2.xml";
    //    @unlink($file);
    //   file_put_contents($file,$xml);
        $xml = mb_convert_encoding($xml , "windows-1251","utf-8"  )  ;       
      //  $xml =          iconv($xml,"utf-8","windows-1251") ;
       //  $xml =    \Symfony\Polyfill\Mbstring\Mbstring::mb_convert_encoding($xml,"windows-1251",'UTF-8')   ;
                

       return Helper::send($xml,'doc',$cid,true);   

        
    }    

    
}
