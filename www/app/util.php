<?php

namespace App;

use App\Helper as H;
use Symfony\Polyfill\Mbstring\Mbstring;
use Symfony\Polyfill\Uuid\Uuid;

/**
 * Класс   со  вспомагательными   функциями
 */
class Util
{

  /**
  * генерация  комманд для  чекового  принтера
  *    
  * @param mixed $template
  * @return mixed
  */
  public  static function generateESCPOS($template){
      
      
      return  [];
  }  
    
  /**
  * генерация QR кода  *   
  * @param mixed $data
  * @param mixed $size
  * @param mixed $margin
  */
  public static function generateQR($data,$size,$margin=5){
       $v = phpversion() ;
       if(strpos($v,'7.2')===0){
            $qrCode = new \Endroid\QrCode\QrCode($data);
            $qrCode->setSize($size);
            $qrCode->setMargin($margin);
           
          //  $qrCode->setEncoding('UTF-8'); 
       
            $dataUri = 'data:image/png;base64,' . base64_encode($qrCode->writeString()) ;            
               
            return $dataUri;
           
       }
        $writer = new \Endroid\QrCode\Writer\PngWriter();
 
      
        $qrCode = new \Endroid\QrCode\QrCode($data);
        $qrCode->setSize($size);
        $qrCode->setMargin($margin);
       // $qrCode->setWriterByName('png');

        $result = $writer->write($qrCode );
     
        $dataUri = $result->getDataUri();
       
        return $dataUri;
  }  
  
    /**
     * генерация  GUID
     *
     */
    public static function guid() {

        $uuid = Uuid::uuid_create(Uuid::UUID_TYPE_RANDOM);

        return $uuid;
    }

    /**
     * возвращает первые  буквы
     */
    public static function getLabelName($name) {
        $name = preg_replace('|\s+|', ' ', $name);
        $name = Mbstring::mb_strtoupper($name, 'UTF-8');

        $w = explode(' ', $name);
        $lb = Mbstring::mb_substr($w[0], 0, 1, 'UTF-8');
        if (count($w) > 1) {
            $lb .= Mbstring::mb_substr($w[1], 0, 1, 'UTF-8');
        }

        return $lb;
    }

    /**
     * Вставляет пробелы  между символами строки
     *
     * @param mixed $data
     */
    public static function addSpaces($string) {
        $_data = "";
        $strlen = mb_strlen($string);
        while($strlen) {
            $_data .= (" " . mb_substr($string, 0, 1, 'UTF-8'));;
            $string = mb_substr($string, 1, $strlen, 'UTF-8');
            $strlen = mb_strlen($string, 'UTF-8');
        }

        return trim($_data);
    }

    /**
     * вовращает  список  лет
     */
    public static function getYears() {
        $list = array();
        for ($i = 2020; $i <= 2030; $i++)
            $list[$i] = $i;
        return $list;
    }

    /**
     * вовращает  список  месяцев
     */
    public static function getMonth() {
        $list = array();
        $list[1] = H::l('january');
        $list[2] = H::l('february');
        $list[3] = H::l('march');
        $list[4] = H::l('april');
        $list[5] = H::l('may');
        $list[6] = H::l('june');
        $list[7] = H::l('july');
        $list[8] = H::l('august');
        $list[9] = H::l('september');
        $list[10] = H::l('october');
        $list[11] = H::l('november');
        $list[12] = H::l('december');
        return $list;
    }

 
 

    public static function money2str_ua($number) {

        return money2str_ua($number, M2S_KOPS_MANDATORY + M2S_KOPS_DIGITS + M2S_KOPS_SHORT);
    }

    /**
     *     Преобразование  первого  символа   в   верхний  регистр
     *
     * @param mixed $str
     */
    public static function ucfirst($str) {

        return mb_ucfirst($str);
    }

    //многобайтовая версия   
    public static function mb_split($str, $len = 1) {

        $arr = [];
        $length = mb_strlen($str, 'UTF-8');

        for ($i = 0; $i < $length; $i += $len) {

            $arr[] = mb_substr($str, $i, $len, 'UTF-8');
        }

        return $arr;
    }

    //очистка  номера  телефона
    public static function handlePhone($tel) {
        $tel = str_replace(' ', '', $tel);
        $tel = preg_replace("/[^0-9.]/", "", $tel);
        
        $phonel = System::getOption("common", 'phonel');
        if($phonel==12 && strlen($tel)==10) {
            $tel = '38'.$tel ;
        }
        
        return $tel;
    }

    //генерация слуяайного цвета
    public static function genColor() {
        $color = dechex(rand(0x000000, 0xFFFFFF));
        return $color;
    }

    // возвращает  прошлые месяцы  с  названиями  и датами начала  и конца
    public static function genPastMonths($num) {
        $mlist = Util::getMonth();

        $list = array();

        $dt = new \App\DateTime();
        $dt->subMonth(1);
        for ($i = 1; $i <= $num; $i++) {
            $mon[] = $mlist[$dt->month];
            $to = $dt->endOfMonth()->getTimestamp();
            $from = $dt->startOfMonth()->getTimestamp();
            $list[] = array('number' => $dt->monthNumber(), 'name' => $mlist[$dt->monthNumber()], 'start' => $from, 'end' => $to);
            $dt = $dt->subMonth(1);
        }
        $list = array_reverse($list);

        return $list;
    }

//массив  в  обьекты для  фронта 
    public static function     tokv(array $a){
        $r = array();
        foreach($a as $k=>$v){
           $r[]=array('key'=>$k,'value'=>$v) ;
        }
        return  $r;           
    }    
    
}



define('M2S_KOPS_DIGITS', 0x01);    // digital copecks
define('M2S_KOPS_MANDATORY', 0x02);    // mandatory copecks
define('M2S_KOPS_SHORT', 0x04);    // shorten copecks
                                                      
function money2str_rugr($money, $options = 0) {
             
    $money = preg_replace('/[\,\-\=]/', '.', $money);

    $numbers_m = array('', 'один', 'два', 'три', 'четыре', "пять", 'шесть', 'семь',
        'восемь', "девять", 'десять', 'одиннадцать', 'двенадцать', 'тринадцать',
        'четырнадцать', "пятнадцать", 'шестнадцать', 'семнадцать', 'восемьнадцать',
        "девятнадцать", 'двадцать', 30  => 'тридцать', 40 => 'сорок', 50 => "пятьдесят",
                                    60  => 'шестдесят', 70 => 'семьдесят', 80 => 'восемьдесят', 90 => "девяносто",
                                    100 => 'сто', 200 => 'двести', 300 => 'триста', 400 => 'четыреста',
                                    500 => "пятьсот", 600 => 'шестьсот', 700 => 'семьсот', 800 => 'восемьсот',
                                    900 => "девятьсот");

    $numbers_f = array('', 'одна', 'две');

    $units_ru = array(
        (($options & M2S_KOPS_SHORT) ? array('коп.', 'коп.', 'коп.') : array('копейка', 'копейки', 'копеек')),
        array('гривна', 'гривны', 'гривен'),
        array('тысяча', 'тысячи', 'тысяч'),
        array('миллион', 'миллиона', 'миллионов')
    );

    $ret = '';

// enumerating digit groups from left to right, from trillions to copecks
// $i == 0 means we deal with copecks, $i == 1 for roubles,
// $i == 2 for thousands etc.
    for ($i = sizeof($units_ru) - 1; $i >= 0; $i--) {

// each group contais 3 digits, except copecks, containing of 2 digits
        $grp = ($i != 0) ? dec_digits_group($money, $i - 1, 3) :
            dec_digits_group($money, -1, 2);

// process the group if not empty
        if ($grp != 0) {

// digital copecks
            if ($i == 0 && ($options & M2S_KOPS_DIGITS)) {
                $ret .= sprintf('%02d', $grp) . ' ';
                $dig = $grp;

// the main case
            } else {
                for ($j = 2; $j >= 0; $j--) {
                    $dig = dec_digits_group($grp, $j);
                    if ($dig != 0) {

// 10 to 19 is a special case
                        if ($j == 1 && $dig == 1) {
                            $dig = dec_digits_group($grp, 0, 2);
                            $ret .= $numbers_m[$dig] . ' ';
                            break;
                        } // thousands and copecks are Feminine gender in Russian
                        elseif (($i == 2 || $i == 0) && $j == 0 && ($dig == 1 || $dig == 2)) {
                            $ret .= $numbers_f[$dig] . ' ';
                        } // the main case
                        else {
                            $ret .= $numbers_m[(int)($dig * pow(10, $j))] . ' ';
                        }
                    }
                }
            }
            $ret .= $units_ru[$i][sk_plural_form($dig)] . ' ';
        } // roubles should be named in case of empty roubles group too
        elseif ($i == 1 && $ret != '') {
            $ret .= $units_ru[1][2] . ' ';
        } // mandatory copecks
        elseif ($i == 0 && ($options & M2S_KOPS_MANDATORY)) {
            $ret .= (($options & M2S_KOPS_DIGITS) ? '00' : 'ноль') .
                ' ' . $units_ru[0][2];
        }
    }

    return trim($ret);
}

function money2str_ru($money, $options = 0) {

    $money = preg_replace('/[\,\-\=]/', '.', $money);

    $numbers_m = array('', 'один', 'два', 'три', 'четыре', "пять", 'шесть', 'семь',
        'восемь', "девять", 'десять', 'одиннадцать', 'двенадцать', 'тринадцать',
        'четырнадцать', "пятнадцать", 'шестнадцать', 'семнадцать', 'восемьнадцать',
        "девятнадцать", 'двадцать', 30  => 'тридцать', 40 => 'сорок', 50 => "пятьдесят",
                                    60  => 'шестдесят', 70 => 'семьдесят', 80 => 'восемьдесят', 90 => "девяносто",
                                    100 => 'сто', 200 => 'двести', 300 => 'триста', 400 => 'четыреста',
                                    500 => "пятьсот", 600 => 'шестьсот', 700 => 'семьсот', 800 => 'восемьсот',
                                    900 => "девятьсот");

    $numbers_f = array('', 'одна', 'две');

    $units_ru = array(
        (($options & M2S_KOPS_SHORT) ? array('коп.', 'коп.', 'коп.') : array('копейка', 'копейки', 'копеек')),
        array('рубль', 'рубля', 'рублей'),
        array('тысяча', 'тысячи', 'тысяч'),
        array('миллион', 'миллиона', 'миллионов')
    );

    $ret = '';

// enumerating digit groups from left to right, from trillions to copecks
// $i == 0 means we deal with copecks, $i == 1 for roubles,
// $i == 2 for thousands etc.
    for ($i = sizeof($units_ru) - 1; $i >= 0; $i--) {

// each group contais 3 digits, except copecks, containing of 2 digits
        $grp = ($i != 0) ? dec_digits_group($money, $i - 1, 3) :
            dec_digits_group($money, -1, 2);

// process the group if not empty
        if ($grp != 0) {

// digital copecks
            if ($i == 0 && ($options & M2S_KOPS_DIGITS)) {
                $ret .= sprintf('%02d', $grp) . ' ';
                $dig = $grp;

// the main case
            } else {
                for ($j = 2; $j >= 0; $j--) {
                    $dig = dec_digits_group($grp, $j);
                    if ($dig != 0) {

// 10 to 19 is a special case
                        if ($j == 1 && $dig == 1) {
                            $dig = dec_digits_group($grp, 0, 2);
                            $ret .= $numbers_m[$dig] . ' ';
                            break;
                        } // thousands and copecks are Feminine gender in Russian
                        elseif (($i == 2 || $i == 0) && $j == 0 && ($dig == 1 || $dig == 2)) {
                            $ret .= $numbers_f[$dig] . ' ';
                        } // the main case
                        else {
                            $ret .= $numbers_m[(int)($dig * pow(10, $j))] . ' ';
                        }
                    }
                }
            }
            $ret .= $units_ru[$i][sk_plural_form($dig)] . ' ';
        } // roubles should be named in case of empty roubles group too
        elseif ($i == 1 && $ret != '') {
            $ret .= $units_ru[1][2] . ' ';
        } // mandatory copecks
        elseif ($i == 0 && ($options & M2S_KOPS_MANDATORY)) {
            $ret .= (($options & M2S_KOPS_DIGITS) ? '00' : 'ноль') .
                ' ' . $units_ru[0][2];
        }
    }

    return trim($ret);
}

function money2str_us($money, $options = 0) {

    $money = preg_replace('/[\,\-\=]/', '.', $money);

    $numbers_m = array('', 'один', 'два', 'три', 'четыре', "пять", 'шесть', 'семь',
        'восемь', "девять", 'десять', 'одиннадцать', 'двенадцать', 'тринадцать',
        'четырнадцать', "пятнадцать", 'шестнадцать', 'семнадцать', 'восемьнадцать',
        "девятнадцать", 'двадцать', 30  => 'тридцать', 40 => 'сорок', 50 => "пятьдесят",
                                    60  => 'шестдесят', 70 => 'семьдесят', 80 => 'восемьдесят', 90 => "девяносто",
                                    100 => 'сто', 200 => 'двести', 300 => 'триста', 400 => 'четыреста',
                                    500 => "пятьсот", 600 => 'шестьсот', 700 => 'семьсот', 800 => 'восемьсот',
                                    900 => "девятьсот");

    $numbers_f = array('', 'один', 'два');

    $units_ru = array(
        (($options & M2S_KOPS_SHORT) ? array('c.', 'c.', 'c.') : array('цент', 'цента', 'центов')),
        array('Доллар', 'доллара', 'долларов'),
        array('тысяча', 'тысячи', 'тысяч'),
        array('миллион', 'миллиона', 'миллионов')
    );

    $ret = '';

// enumerating digit groups from left to right, from trillions to copecks
// $i == 0 means we deal with copecks, $i == 1 for roubles,
// $i == 2 for thousands etc.
    for ($i = sizeof($units_ru) - 1; $i >= 0; $i--) {

// each group contais 3 digits, except copecks, containing of 2 digits
        $grp = ($i != 0) ? dec_digits_group($money, $i - 1, 3) :
            dec_digits_group($money, -1, 2);

// process the group if not empty
        if ($grp != 0) {

// digital copecks
            if ($i == 0 && ($options & M2S_KOPS_DIGITS)) {
                $ret .= sprintf('%02d', $grp) . ' ';
                $dig = $grp;

// the main case
            } else {
                for ($j = 2; $j >= 0; $j--) {
                    $dig = dec_digits_group($grp, $j);
                    if ($dig != 0) {

// 10 to 19 is a special case
                        if ($j == 1 && $dig == 1) {
                            $dig = dec_digits_group($grp, 0, 2);
                            $ret .= $numbers_m[$dig] . ' ';
                            break;
                        } // thousands and copecks are Feminine gender in Russian
                        elseif (($i == 2 || $i == 0) && $j == 0 && ($dig == 1 || $dig == 2)) {
                            $ret .= $numbers_f[$dig] . ' ';
                        } // the main case
                        else {
                            $ret .= $numbers_m[(int)($dig * pow(10, $j))] . ' ';
                        }
                    }
                }
            }
            $ret .= $units_ru[$i][sk_plural_form($dig)] . ' ';
        } // roubles should be named in case of empty roubles group too
        elseif ($i == 1 && $ret != '') {
            $ret .= $units_ru[1][2] . ' ';
        } // mandatory copecks
        elseif ($i == 0 && ($options & M2S_KOPS_MANDATORY)) {
            $ret .= (($options & M2S_KOPS_DIGITS) ? '00' : 'ноль') .
                ' ' . $units_ru[0][2];
        }
    }

    return trim($ret);
}

function money2str_eu($money, $options = 0) {

    $money = preg_replace('/[\,\-\=]/', '.', $money);

    $numbers_m = array('', 'один', 'два', 'три', 'четыре', "пять", 'шесть', 'семь',
        'восемь', "девять", 'десять', 'одиннадцать', 'двенадцать', 'тринадцать',
        'четырнадцать', "пятнадцать", 'шестнадцать', 'семнадцать', 'восемьнадцать',
        "девятнадцать", 'двадцать', 30  => 'тридцать', 40 => 'сорок', 50 => "пятьдесят",
                                    60  => 'шестдесят', 70 => 'семьдесят', 80 => 'восемьдесят', 90 => "девяносто",
                                    100 => 'сто', 200 => 'двести', 300 => 'триста', 400 => 'четыреста',
                                    500 => "пятьсот", 600 => 'шестьсот', 700 => 'семьсот', 800 => 'восемьсот',
                                    900 => "девятьсот");

    $numbers_f = array('', 'один', 'два');

    $units_ru = array(
        (($options & M2S_KOPS_SHORT) ? array('c.', 'c.', 'c.') : array('цент', 'цента', 'центов')),
        array('Евро', 'Евро', 'Евро'),
        array('тысяча', 'тысячи', 'тысяч'),
        array('миллион', 'миллиона', 'миллионов')
    );

    $ret = '';

// enumerating digit groups from left to right, from trillions to copecks
// $i == 0 means we deal with copecks, $i == 1 for roubles,
// $i == 2 for thousands etc.
    for ($i = sizeof($units_ru) - 1; $i >= 0; $i--) {

// each group contais 3 digits, except copecks, containing of 2 digits
        $grp = ($i != 0) ? dec_digits_group($money, $i - 1, 3) :
            dec_digits_group($money, -1, 2);

// process the group if not empty
        if ($grp != 0) {

// digital copecks
            if ($i == 0 && ($options & M2S_KOPS_DIGITS)) {
                $ret .= sprintf('%02d', $grp) . ' ';
                $dig = $grp;

// the main case
            } else {
                for ($j = 2; $j >= 0; $j--) {
                    $dig = dec_digits_group($grp, $j);
                    if ($dig != 0) {

// 10 to 19 is a special case
                        if ($j == 1 && $dig == 1) {
                            $dig = dec_digits_group($grp, 0, 2);
                            $ret .= $numbers_m[$dig] . ' ';
                            break;
                        } // thousands and copecks are Feminine gender in Russian
                        elseif (($i == 2 || $i == 0) && $j == 0 && ($dig == 1 || $dig == 2)) {
                            $ret .= $numbers_f[$dig] . ' ';
                        } // the main case
                        else {
                            $ret .= $numbers_m[(int)($dig * pow(10, $j))] . ' ';
                        }
                    }
                }
            }
            $ret .= $units_ru[$i][sk_plural_form($dig)] . ' ';
        } // roubles should be named in case of empty roubles group too
        elseif ($i == 1 && $ret != '') {
            $ret .= $units_ru[1][2] . ' ';
        } // mandatory copecks
        elseif ($i == 0 && ($options & M2S_KOPS_MANDATORY)) {
            $ret .= (($options & M2S_KOPS_DIGITS) ? '00' : 'ноль') .
                ' ' . $units_ru[0][2];
        }
    }

    return trim($ret);
}

function money2str_ua($money, $options = 0) {

    $money = preg_replace('/[\,\-\=]/', '.', $money);

    $numbers_m = array('', 'одна', 'двi', 'три', 'чотири', "п'ять", 'шiсть', 'сiм',
        'вiciм', "дев'ять", 'десять', 'одинадцять', 'дванадцять', 'тринадцять',
        'чотирнадцять', "п'ятнадцять", 'шiстнадцять', 'сiмнадцять', 'вiсiмнадцять',
        "дев'ятнадцять", 'двадцять', 30  => 'тридцять', 40 => 'сорок', 50 => "п'ятдесять",
                                     60  => 'шiстдесять', 70 => 'сiмдесять', 80 => 'вiсiмдесять', 90 => "дев'яносто",
                                     100 => 'сто', 200 => 'двiстi', 300 => 'триста', 400 => 'чотириста',
                                     500 => "п'ятсот", 600 => 'шiстьсот', 700 => 'сiмсот', 800 => 'вiсiмсот',
                                     900 => "дев'ятьсот");

    $numbers_f = array('', 'одна', 'дві');

    $units_ru = array(
        (($options & M2S_KOPS_SHORT) ? array('коп.', 'коп.', 'коп.') : array('копiйка', 'копiйки', 'копiйок')),
        array('гривня', 'гривнi', 'гривень'),
        array('тисяча', 'тисячi', 'тисяч'),
        array('мiльйон', 'мiльйона', 'мiльйонiв')
    );

    $ret = '';

// enumerating digit groups from left to right, from trillions to copecks
// $i == 0 means we deal with copecks, $i == 1 for roubles,
// $i == 2 for thousands etc.
    for ($i = sizeof($units_ru) - 1; $i >= 0; $i--) {

// each group contais 3 digits, except copecks, containing of 2 digits
        $grp = ($i != 0) ? dec_digits_group($money, $i - 1, 3) :
            dec_digits_group($money, -1, 2);

// process the group if not empty
        if ($grp != 0) {

// digital copecks
            if ($i == 0 && ($options & M2S_KOPS_DIGITS)) {
                $ret .= sprintf('%02d', $grp) . ' ';
                $dig = $grp;

// the main case
            } else {
                for ($j = 2; $j >= 0; $j--) {
                    $dig = dec_digits_group($grp, $j);
                    if ($dig != 0) {

// 10 to 19 is a special case
                        if ($j == 1 && $dig == 1) {
                            $dig = dec_digits_group($grp, 0, 2);
                            $ret .= $numbers_m[$dig] . ' ';
                            break;
                        } // thousands and copecks are Feminine gender in Russian
                        elseif (($i == 2 || $i == 0) && $j == 0 && ($dig == 1 || $dig == 2)) {
                            $ret .= $numbers_f[$dig] . ' ';
                        } // the main case
                        else {
                            $ret .= $numbers_m[(int)($dig * pow(10, $j))] . ' ';
                        }
                    }
                }
            }
            $ret .= $units_ru[$i][sk_plural_form($dig)] . ' ';
        } // roubles should be named in case of empty roubles group too
        elseif ($i == 1 && $ret != '') {
            $ret .= $units_ru[1][2] . ' ';
        } // mandatory copecks
        elseif ($i == 0 && ($options & M2S_KOPS_MANDATORY)) {
            $ret .= (($options & M2S_KOPS_DIGITS) ? '00' : 'ноль') .
                ' ' . $units_ru[0][2];
        }
    }

    return trim($ret);
}

// service function to select the group of digits
function dec_digits_group($number, $power, $digits = 1) {
                 
    if (function_exists('gmp_init') && $power >0 ) {
       return   gmp_intval( gmp_mod(gmp_div((int)$number, gmp_pow(10,(int) $power * $digits )), gmp_pow(10, (int)$digits )));
    }
    return    intval(   ( $number/pow(10, $power * $digits) ) % pow(10,    $digits) ) ;
    
   // return (int)bcmod(bcdiv($number, bcpow(10, $power * $digits, 8)), bcpow(10, $digits, 8));
}

// service function to get plural form for the number
function sk_plural_form($d) {    
    $d = $d % 100;
    if ($d > 20) {
        $d = $d % 10;
    }
    if ($d == 1) {
        return 0;
    } elseif ($d > 0 && $d < 5) {
        return 1;
    } else {
        return 2;
    }
}


