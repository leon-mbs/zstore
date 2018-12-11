<?php

namespace App;

/**
 * Класс   со  вспомагательными   функциями
 */
class Util
{

    /**
     * Вставляет пробелы  между символами строки
     *
     * @param mixed $data
     */
    public static function addSpaces($string) {
        $_data = "";
        $strlen = mb_strlen($string);
        while ($strlen) {
            $_data .= (" " . mb_substr($string, 0, 1, 'UTF-8'));
            ;
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
        for ($i = 2016; $i <= 2030; $i++)
            $list[$i] = $i;
        return $list;
    }

    /**
     * вовращает  список  месяцев
     */
    public static function getMonth() {
        $list = array();
        $list[1] = "Январь";
        $list[2] = "Февраль";
        $list[3] = "Март";
        $list[4] = "Апрель";
        $list[5] = "Май";
        $list[6] = "Июнь";
        $list[7] = "Июль";
        $list[8] = "Август";
        $list[9] = "Сентябрь";
        $list[10] = "Октябрь";
        $list[11] = "Ноябрь";
        $list[12] = "Декабрь";
        return $list;
    }

    /**
     * Выводит  сумму  прописью
     *
     * @param mixed $number
     */
    public static function money2str($number) {

        return money2str_ru($number, M2S_KOPS_MANDATORY + M2S_KOPS_DIGITS + M2S_KOPS_SHORT);
    }

    /**
     *     Преобразование  первого  символа   в   верхний  регистр
     *
     * @param mixed $str
     */
    public static function ucfirst($str) {

        return mb_ucfirst($str);
    }

}

// Convert digital Russian currency representation
// (Russian rubles and copecks) to the verbal one
// Copyright 2008 Sergey Kurakin
// Licensed under LGPL version 3 or later

define('M2S_KOPS_DIGITS', 0x01);    // digital copecks
define('M2S_KOPS_MANDATORY', 0x02);    // mandatory copecks
define('M2S_KOPS_SHORT', 0x04);    // shorten copecks

function money2str_ru($money, $options = 0) {

    $money = preg_replace('/[\,\-\=]/', '.', $money);

    $numbers_m = array('', 'одна', 'две', 'три', 'четыре', "пять", 'шесть', 'семь',
        'восемь', "девять", 'десять', 'одиннадцать', 'двенадцать', 'тринадцать',
        'четырнадцать', "пятнадцать", 'шестнадцат', 'семнадцать', 'восемьнадцать',
        "девятнадцать", 'двадцат', 30 => 'тридцать', 40 => 'сорок', 50 => "пятьдесят",
        60 => 'шестдесят', 70 => 'семьдесят', 80 => 'восемьдесят', 90 => "девяносто",
        100 => 'сто', 200 => 'двести', 300 => 'триста', 400 => 'четыриста',
        500 => "пятьсот", 600 => 'шестьсот', 700 => 'семьсот', 800 => 'восемьсот',
        900 => "девятьсот");

    $numbers_f = array('', 'одна', 'дві');

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
            } else
                for ($j = 2; $j >= 0; $j--) {
                    $dig = dec_digits_group($grp, $j);
                    if ($dig != 0) {

// 10 to 19 is a special case
                        if ($j == 1 && $dig == 1) {
                            $dig = dec_digits_group($grp, 0, 2);
                            $ret .= $numbers_m[$dig] . ' ';
                            break;
                        } // thousands and copecks are Feminine gender in Russian
                        elseif (($i == 2 || $i == 0) && $j == 0 && ($dig == 1 || $dig == 2))
                            $ret .= $numbers_f[$dig] . ' ';

// the main case
                        else
                            $ret .= $numbers_m[(int) ($dig * pow(10, $j))] . ' ';
                    }
                }
            $ret .= $units_ru[$i][sk_plural_form($dig)] . ' ';
        } // roubles should be named in case of empty roubles group too
        elseif ($i == 1 && $ret != '')
            $ret .= $units_ru[1][2] . ' ';

// mandatory copecks
        elseif ($i == 0 && ($options & M2S_KOPS_MANDATORY))
            $ret .= (($options & M2S_KOPS_DIGITS) ? '00' : 'ноль') .
                    ' ' . $units_ru[0][2];
    }

    return trim($ret);
}

// service function to select the group of digits
function dec_digits_group($number, $power, $digits = 1) {
    return (int) bcmod(bcdiv($number, bcpow(10, $power * $digits, 8)), bcpow(10, $digits, 8));
}

// service function to get plural form for the number
function sk_plural_form($d) {
    $d = $d % 100;
    if ($d > 20)
        $d = $d % 10;
    if ($d == 1)
        return 0;
    elseif ($d > 0 && $d < 5)
        return 1;
    else
        return 2;
}

/*
  function num2str($num)
  {
  $nul = 'ноль';
  $ten = array(
  array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
  array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
  );
  $a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
  $tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
  $hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
  $unit = array(// Units
  array('копейка', 'копейки', 'копеек', 1),
  array('рубль', 'рубля', 'рублей', 0),
  array('тысяча', 'тысячи', 'тысяч', 1),
  array('миллион', 'миллиона', 'миллионов', 0),
  array('миллиард', 'милиарда', 'миллиардов', 0),
  );
  //
  list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
  $out = array();
  if (intval($rub) > 0) {
  foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
  if (!intval($v))
  continue;
  $uk = sizeof($unit) - $uk - 1; // unit key
  $gender = $unit[$uk][3];
  list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
  // mega-logic
  $out[] = $hundred[$i1]; # 1xx-9xx
  if ($i2 > 1)
  $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];# 20-99
  else
  $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];# 10-19 | 1-9
  // units without rub & kop
  if ($uk > 1)
  $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
  } //foreach
  }
  else
  $out[] = $nul;
  $out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
  $out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
  return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
  }


  function morph($n, $f1, $f2, $f5)
  {
  $n = abs(intval($n)) % 100;
  if ($n > 10 && $n < 20)
  return $f5;
  $n = $n % 10;
  if ($n > 1 && $n < 5)
  return $f2;
  if ($n == 1)
  return $f1;
  return $f5;
  }
 */


if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {

    function mb_ucfirst($string) {
        $string = mb_ereg_replace("^[\ ]+", "", $string);
        $string = mb_strtoupper(mb_substr($string, 0, 1, "UTF-8"), "UTF-8") . mb_substr($string, 1, mb_strlen($string), "UTF-8");
        return $string;
    }

}