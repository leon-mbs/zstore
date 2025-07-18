<?php

namespace App\Modules\PPO;

use App\Helper as H;
use PPOLib\PPO as PPO;

/**
 * Вспомагательный  класс для  фискализации
 */
class PPOHelper
{
    //  const DPI = "http://fs.tax.gov.ua:8609/fs/";
    public const FORM_NAL     =  "Готівка";
    public const FORM_CARD    =  "Банківська карта";
    public const FORM_CREDIT  =  "В кредит";
    public const FORM_PREPAID =  "Передоплата";
    public const FORM_BONUS   =  "Бонуси";

    /**
     * Отправка  данных  в  налоговую
     *
     * @param mixed $data
     * @param mixed $type cmd (команда) или  doc (документ)
     * @param \App\Entity\Pos $pos pos терминал
     * @param mixed $onlysign только  наложить  подпись
     */
    public static function send($data, $type, \App\Entity\Pos $pos, $onlysign = false) {

        try {

           
            $serhost = $pos->ppohost;
            $serport = $pos->ppoport;
            $usessl = $pos->ppousessl;
            $password = $pos->ppopassword;
            $keydata = $pos->ppokey;
            $certdata = $pos->ppocert;
            $isjks = $pos->ppoisjks;

    
            $key = @unserialize(@base64_decode($pos->ppokey));
            $cert = @unserialize(@base64_decode($pos->ppocert));

            if ($key == null || $cert == null) {
                $msg = "Не завантажений ключ або сертифікат";
                $msg = str_replace("'", "\"", $msg) ;
                return array('success' => false, 'data' => $msg);
            }

            $signed = PPO::sign($data, $key, $cert);

      
            if($onlysign == true) {
                return array('success' => true, 'signed' => $signed);
            }
            $return = PPO::send($signed, $type);


            if (strpos($return, 'Номер документа повинен дорівнювати') > 0) {
                $arr = explode(' ', $return);
                if ($arr[count($arr) - 1] > 0) {
                    return array('success' => false, 'doclocnumber' => $arr[count($arr) - 1], 'data' => $return);

                }
            }
            if (strpos($return, 'помилки') > 0) {

                return array('success' => false, 'data' => $return);
            }

            if ($type == "cmd") {
                return array('success' => true, 'data' => $return);
            }

            if ($type == "doc") {


                
               $decrypted = PPO::decrypt($return, true);

             

                if (substr($decrypted, 0, 5) == "<?xml") {
                    $xml = $decrypted;
                    // $xml = mb_convert_encoding($xml , "utf-8", "windows-1251" )  ;

                    $xml = simplexml_load_string($xml);
                    $errorcode = (string)($xml->ERRORCODE[0]);
                    $taxnum = (string)($xml->ORDERTAXNUM[0]);
                    $taxnumloc = (string)($xml->ORDERNUM[0]);

                    if ($errorcode == '0' && $taxnum > 0) {   //следующий номер  документа
                        return array('success' => true, 'docnumber' => $taxnum, 'doclocnumber' => $taxnumloc, 'data' => $return);
                    }
                    return array('success' => false, 'data' => $errorcode);
                }

                return array('success' => true, 'data' => $decrypted);
            }


        } catch(\Exception  $ee) {
            return array('success' => false, 'data' => $ee->getMessage());

        }


    }

    /**
     * закрытие и открытие  смены
     *
     * @param mixed $posid pos терминал
     * @param mixed $open true- открыть,  false - закрыть
     */
    public static function shift($posid, $open) {

        $pos = \App\Entity\Pos::load($posid);

        $firm = \App\Helper::getFirmData( );

        if(strlen($pos->firmname ??'')=='') {
            $pos->firmname = $firm['firm_name']  ;
        }
        if(strlen($pos->inn ??'')=='') {
            $pos->inn = $firm['inn']  ;
        }
        if(strlen($pos->tin ??'')=='') {
            $pos->tin = $firm['tin']  ;
        } 

        
        $header = array();
        $header['doctype'] = $open == true ? 100 : 101;
        $header['firmname'] = $pos->firmname;
        $header['inn'] = strlen($pos->inn) > 0 ? $pos->inn : false;
        $header['tin'] = strlen($pos->tin) > 0 ? $pos->tin : false; 
        $header['address'] = $pos->address;
        $header['testing'] = $pos->testing==1;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = self::getCashier();
        $header['guid'] = \App\Util::guid();

        $report = new \App\Report('shift.xml');

        $xml = $report->generate($header);

        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");
          
        if($firm== null){
            return array('success' => false, 'data' => 'Не вказана  компанiя в POS термiналi');
        }
        
        
        return self::send($xml, 'doc', $pos);
    }

    /**
     * отправка  z-отчета
     *
     * @param mixed $posid pos  терминал
     * @param mixed $stat данные  оплат
     * @param mixed $rstat данные  оплат по возврату
     */
    public static function zform($posid, $stat, $rstat) {
        $pos = \App\Entity\Pos::load($posid);

        $firm = \App\Helper::getFirmData( );

        if(strlen($pos->firmname ??'')=='') {
            $pos->firmname = $firm['firm_name']  ;
        }
        if(strlen($pos->inn ??'')=='') {
            $pos->inn = $firm['inn']  ;
        }
        if(strlen($pos->tin ??'')=='') {
            $pos->tin = $firm['tin']  ;
        }

        $header = array();
        //    $header['doctype'] = $open == true ? 100 : 101;
        $header['firmname'] = $pos->firmname;
        $header['inn'] = strlen($pos->inn) > 0 ? $pos->inn : false;
        $header['tin'] = $pos->tin;
        $header['address'] = $pos->address;
        $header['testing'] = $pos->testing==1;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = self::getCashier();
        $header['guid'] = \App\Util::guid();
        $header['pays'] = array();
        $header['paysr'] = array();
        $amount = 0;
        $amountr = 0;
        $cnt = $stat['cnt'];
        $cntr = $rstat['cnt'];

        //реализация

        $n = 1;

        if ($stat['amount0'] > 0) {
            $header['pays'][] = array(
                'formname' => self::FORM_NAL,
                'formcode' => 0,
                'sum'      => number_format($stat['amount0'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amount = $amount + $stat['amount0'];
            $n++;
        }
        if ($stat['amount1'] > 0) {
            $header['pays'][] = array(
                'formname' => self::FORM_CARD,
                'formcode' => 1,
                'sum'      => number_format($stat['amount1'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amount = $amount + $stat['amount1'];
            $n++;
        }
        if ($stat['amount2'] > 0) {
            $header['pays'][] = array(
                'formname' => self::FORM_CREDIT,
                'formcode' => 2,
                'sum'      => number_format($stat['amount2'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amount = $amount + $stat['amount2'];
            $n++;
        }
        if ($stat['amount3'] > 0) {
            $header['pays'][] = array(
                'formname' => self::FORM_PREPAID,
                'formcode' => 3,
                'sum'      => number_format($stat['amount3'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amount = $amount + $stat['amount3'];
            $n++;
        }

       \App\System::getSession()->shiftclose = "Продажа: каса ". \App\Helper::fa($stat['amount0']). ", банк ". \App\Helper::fa($stat['amount1']) ." Поаернення: каса ". \App\Helper::fa($stat['amount2']). ", банк ". \App\Helper::fa($stat['amount3'] );
    
        //возврат

        $n = 1;

        if ($rstat['amount0'] > 0) {
            $header['paysr'][] = array(
                'formname' => self::FORM_NAL,
                'formcode' => 0,
                'sum'      => number_format($rstat['amount0'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amountr = $amountr + $rstat['amount0'];
            $n++;
        }
        if ($rstat['amount1'] > 0) {
            $header['paysr'][] = array(
                'formname' => self::FORM_CARD,
                'formcode' => 1,
                'sum'      => number_format($rstat['amount1'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amountr = $amountr + $rstat['amount1'];
            $n++;
        }


        $header['amount'] = number_format($amount, 2, '.', '');
        $header['amountr'] = number_format($amountr, 2, '.', '');
        $header['cnt'] = $cnt;
        $header['cntr'] = $cntr;
        $header['isreal'] = $cnt > 0;
        $header['isret'] = $cntr > 0;

        $report = new \App\Report('zform.xml');

        $xml = $report->generate($header);
        $_xml = $xml;
        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");
     
        $ret =  self::send($xml, 'doc', $pos);
        if($ret['success']==true) {
            $r = new ZRecord();
            $r->createdon = time();
            $r->amount = $amount;
            $r->fndoc = $ret['docnumber'];
            $r->fnpos = $pos->fiscalnumber;
            $r->ramount = $amountr;
            $r->cnt = $cnt;
            $r->rcnt = $cntr;
            $r->sentxml =  $_xml;
            $r->taxanswer =  $ret['data'];
            $r->save();
        }
        return  $ret;
    }


    /**
     * отправка  чека
     *
     * @param mixed $doc

     */
    public static function check($doc ) {
        $common = \App\System::getOptions('common');
 

        $pos = \App\Entity\Pos::load($doc->headerdata['pos']);
        $firm = \App\Helper::getFirmData( );

        if(strlen($pos->firmname ??'')=='') {
            $pos->firmname = $firm['firm_name']  ;
        }
        if(strlen($pos->inn ??'')=='') {
            $pos->inn = $firm['inn']  ;
        }
        if(strlen($pos->tin ??'')=='') {
            $pos->tin = $firm['tin']  ;
        }
        
        
        $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);

        $header = array();
        //  $header['doctype'] = $doctype;
        //     $header['docsubtype'] = $docsubtype;
        $header['firmname'] = $pos->firmname;
        $header['inn'] = strlen($pos->inn) > 0 ? $pos->inn : false;
        $header['tin'] = $pos->tin;
        $header['testing'] = $pos->testing==1;
        $header['address'] = $pos->address;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = self::getCashier($doc);
        $header['guid'] = \App\Util::guid();
        $header['amount'] = 0;
 

     //   $header['comment'] = strlen($common["checkslogan"] ??'') >0 ? $common["checkslogan"]  :  false;
        $header['comment'] = false;

        $header['details'] = array();
        $n = 1;
      
     //   $header['amount'] = $doc->payamount;
        
        
        
         //общая  скидка
        $discsum =    $doc->amount  -  $doc->payamount  ;

         $disc=1;
        if($discsum >0 ) {
           $disc = 1 - ($discsum/$doc->amount);
     
        }
          
        foreach ($doc->unpackDetails('detaildata') as $item) {
            $item->price = round($item->price * $disc *100)/100 ;
            $header['details'][] = array(
                'num'   => "ROWNUM=\"{$n}\"",
                'name'  => $item->itemname,
                'qty'   => number_format($item->quantity, 3, '.', ''),
                'price' => number_format($item->price    , 2, '.', ''),
                'cost'  => number_format($item->quantity * $item->price  , 2, '.', '')
            );
            $n++;


        }
        foreach ($doc->unpackDetails('services') as $item) {
            $item->price = round($item->price * $disc *100)/100 ;
            $header['details'][] = array(
                'num'   => "ROWNUM=\"{$n}\"",
                'name'  => $item->service_name,
                'qty'   => number_format($item->quantity, 3, '.', ''),
                'price' => number_format($item->price  , 2, '.', ''),
                'cost'  => number_format($item->quantity * $item->price , 2, '.', '')
            );
            $n++;

        }
        
        $sum=0;
        foreach($header['details'] as $p ) {
           $sum += doubleval( number_format($p['price'] * $p['qty'] , 2, '.', ''));
         
        }        
        
          
       
         // к  оплате
        $payamount  =    doubleval($doc->payamount) - doubleval($doc->headerdata['prepaid']);
        // оплачено
        $payed  =    doubleval($doc->headerdata['payed']) + doubleval($doc->headerdata['payedcard']);
       
        $amount0 = 0;
        $amount1 = 0;
        $amount2 = 0;
        $amount3 = 0;
        $header['pays'] = array();
        $n = 1;

 

  //$doc->headerdata['payed']   += 0.03;
        if($doc->headerdata['payment']  >0) {
            if ($mf->beznal == 1) {
                $pay = array(
                    'formname' => self::FORM_CARD,
                    'formcode' => 1,
                    'paysum'   => number_format($payed, 2, '.', ''),
                    'payed'    => number_format($payed, 2, '.', ''),
                    'rest'     => false,                    
                    'num'      => "ROWNUM=\"{$n}\""
                );
                // в долг
                if ($payed < $doc->payamount) {
                    $pay['paysum'] = number_format($payamount, 2, '.', '');
                    $pay['payed'] = number_format($payed, 2, '.', '');
                }
                $header['pays'][] = $pay;
                $n++;
                $amount1 = $pay['paysum'];
            } else {

                $pay = array(
                    'formname' => self::FORM_NAL,
                    'formcode' => 0,
                    'paysum'   => number_format($payed, 2, '.', ''),
                    'payed'    => number_format($payed, 2, '.', ''),
                    'rest'     => false,
                    'num'      => "ROWNUM=\"{$n}\""
                );
                //сдача
                if ($doc->headerdata["exchange"] > 0) {
                  //  $pay['rest'] = number_format($doc->headerdata["exchange"], 2, '.', '');
                    $pay['rest'] = number_format($payed- $doc->headerdata["exchange"], 2, '.', '');
                }
                // в долг
                if ($payed < $doc->payamount) {
                    $pay['paysum'] = number_format($payamount, 2, '.', '');
                    $pay['payed'] = number_format($payed, 2, '.', '');
                }

                $header['pays'][] = $pay;
                $n++;
                $amount0 = $pay['paysum'];
            }
        } else {
            if($doc->headerdata['mfnal']  >0 && $doc->headerdata['payed'] > 0) {
                $pay = array(
                     'formname' => self::FORM_NAL,
                     'formcode' => 0,
                     'paysum'   => number_format($doc->headerdata['payed'], 2, '.', ''),
                     'payed'    => number_format($doc->headerdata['payed'], 2, '.', ''),
                     'rest'     => false,
                     'num'      => "ROWNUM=\"{$n}\""
                 );
                //сдача
                if ($doc->headerdata["exchange"] > 0) {
                    $pay['rest'] = number_format($doc->headerdata["exchange"], 2, '.', '');
                    $pay['paysum'] = number_format($doc->headerdata['payed'] - $doc->headerdata["exchange"], 2, '.', '');
                }

                $header['pays'][] = $pay;
                $n++;
                $amount0 = $pay['paysum'];

            }
            if($doc->headerdata['mfbeznal']  >0 && $doc->headerdata['payedcard'] > 0) {
                $pay = array(
                    'formname' => self::FORM_CARD,
                    'formcode' => 1,
                    'paysum'   => number_format($doc->headerdata['payedcard'], 2, '.', ''),
                    'payed'    => number_format($doc->headerdata['payedcard'], 2, '.', ''),
                    'rest'     => false,
                    'num'      => "ROWNUM=\"{$n}\""
                );

                $header['pays'][] = $pay;
                $n++;
                $amount1 = $pay['paysum'];
            }

        }

        // в долг
        if ($payed < $doc->payamount) {
            $pay = array(
                'formname' => self::FORM_CREDIT,
                'formcode' => 2,
                'paysum'   => number_format($payamount - $payed, 2, '.', ''),
                'payed'    => number_format($payamount - $payed, 2, '.', ''),
                'rest'     => false,
                'num'      => "ROWNUM=\"{$n}\""
            );
            $header['pays'][] = $pay;
            $n++;

            $amount2 = $pay['paysum'];
        }
        // предоплата
        if ($doc->headerdata['prepaid']>0) {
            $pay = array(
                'formname' => self::FORM_PREPAID,
                'formcode' => 3,
                'paysum'   => number_format($doc->headerdata['prepaid'], 2, '.', ''),
                'payed'    => number_format($doc->headerdata['prepaid'], 2, '.', ''),
                'rest'     => false,
                'num'      => "ROWNUM=\"{$n}\""
            );
            $header['pays'][] = $pay;
            $n++;

            $amount3 = $pay['paysum'];
            $header['amount'] += $doc->headerdata['prepaid'];
        }
      
        
        $header['pay'] = count($header['pays']) > 0;
     
        $sumpay=0;
        foreach($header['pays'] as $p ) {
           $sumpay += $p['paysum'] ;
        }
        
          
        $header['disc']   = false;
        if ($discsum > 0) {
          //  $header['disc'] = number_format($discsum, 2, '.', '');
          //  $sumpay  += $header['disc'];
        }        

        $header['amount'] = number_format($sumpay, 2, '.', '');
        $header['rnd']  =  false;
        $header['nrnd']  =  false;
        if(floatval($sum) !=floatval($sumpay) )  {
           $header['rnd']  = number_format( $sum-$sumpay        , 2, '.', '');
           $header['nrnd']  = number_format( $sum  , 2, '.', '');
           
        }
        
        $report = new \App\Report('check.xml');

        $xml = $report->generate($header);
     
        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");
      
        $ret = self::send($xml, 'doc', $pos);
        if ($ret['success'] == true) {
            
            self::insertStat($pos->pos_id, 1, $amount0, $amount1, $amount2, $amount3, $doc->document_number, $ret['docnumber']);
        }
        $doc->headerdata["fiscdts"] = "&date=".date('Ymd')."&time={$header['time']}&sum={$header['amount']}";

        $ret['fiscalamount']=  $header['amount'];
 
        $ret['fiscaltest']=  $pos->testing==1 ;

        return $ret;
    }

    /**
     * отправка  доплаты
     *
     * @param mixed $doc
     */
    public static function checkpay($doc, $pos_id, $payed, $payment) {


        $pos = \App\Entity\Pos::load($pos_id);
        $firm = \App\Helper::getFirmData( );

        if(strlen($pos->firmname ??'')=='') {
            $pos->firmname = $firm['firm_name']  ;
        }
        if(strlen($pos->inn ??'')=='') {
            $pos->inn = $firm['inn']  ;
        }
        if(strlen($pos->tin ??'')=='') {
            $pos->tin = $firm['tin']  ;
        }
        $mf = \App\Entity\MoneyFund::load($payment);

        $header = array();

        $header['firmname'] = $pos->firmname ;
        $header['inn'] = strlen($pos->inn) > 0 ? $pos->inn : false;
        $header['tin'] = $pos->tin;
        $header['address'] = $pos->address;
        $header['testing'] = $pos->testing==1;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = self::getCashier($doc);
        $header['guid'] = \App\Util::guid();

        $amount0 = 0;
        $amount1 = 0;
        $amount2 = 0;
        $amount3 = 0;


        if ($mf->beznal == 1) {
            $header['formname'] = self::FORM_CARD;
            $header['formcode'] = 1;
            $amount1 = number_format($payed, 2, '.', '');
        } else {
            $header['formname'] = self::FORM_NAL;
            $header['formcode'] = 0;
            $amount0 = number_format($payed, 2, '.', '');
        }
        //  $header['amount'] = number_format($doc->amount, 2, '.', '');
        $header['pay'] = $payed > 0;
        $header['paysum'] = number_format($payed, 2, '.', '');
        $header['payed'] = number_format($payed, 2, '.', '');

        $header['parentcheck'] = $doc->document_number;
        $header['amount'] = number_format($payed, 2, '.', '');

        $report = new \App\Report('checkpay.xml');

        $xml = $report->generate($header);

        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");
     
        $ret = self::send($xml, 'doc', $pos);
        if ($ret['success'] == true) {


            self::insertStat($pos->pos_id, 2, $amount0, $amount1, $amount2, $amount3, $doc->document_number);
        }

        return $ret;
    }

    /**
     * отправка  возвратного чека
     *
     * @param mixed $doc
     */
    public static function checkback($doc) {


        $pos = \App\Entity\Pos::load($doc->headerdata['pos']);
        $firm = \App\Helper::getFirmData( );

        if(strlen($pos->firmname ??'')=='') {
            $pos->firmname = $firm['firm_name']  ;
        }
        if(strlen($pos->inn ??'')=='') {
            $pos->inn = $firm['inn']  ;
        }
        if(strlen($pos->tin ??'')=='') {
            $pos->tin = $firm['tin']  ;
        }
        $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);

        $header = array();
  
        $header['firmname'] = $pos->firmname ;
        $header['inn'] = strlen($pos->inn) > 0 ? $pos->inn : false;
        $header['tin'] = $pos->tin;
        $header['address'] = $pos->address;
        $header['testing'] = $pos->testing==1;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumberback'] = $doc->headerdata["docnumberback"];
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $user = \App\Entity\User::load($doc->user_id);
        $header['username'] = self::getCashier($doc);
        $header['guid'] = \App\Util::guid();
        $amount0 = 0;
        $amount1 = 0;
        $amount2 = 0;
        $amount3 = 0;
         //общая  скидка
        $discsum =    $doc->amount  -  $doc->payamount  ;

         $disc=1;
        if($discsum >0 ) {
           $disc = 1 - ($discsum/$doc->amount);
     
        }

        if ($mf->beznal == 1) {
            $header['formname'] = self::FORM_CARD;
            $header['formcode'] = 1;
            $amount1 = number_format($doc->payed, 2, '.', '');
        } else {
            $header['formname'] = self::FORM_NAL;
            $header['formcode'] = 0;
            $amount0 = number_format($doc->payed, 2, '.', '');
        }

        $header['paysum'] = number_format($doc->payed, 2, '.', '');
        $header['payed'] = number_format($doc->payed, 2, '.', '');

        $header['details'] = array();

        $n = 1;
        $header['amount'] = 0;
        foreach ($doc->unpackDetails('detaildata') as $item) {
            $item->price = round($item->price * $disc *100)/100 ;
             
            $header['details'][] = array(
                'num'   => "ROWNUM=\"{$n}\"",
                'name'  => $item->itemname,
                'qty'   => number_format($item->quantity, 3, '.', ''),
                'price' => number_format($item->price, 2, '.', ''),
                'cost'  => number_format($item->quantity * $item->price, 2, '.', '')
            );
            $n++;
            $header['amount'] = $header['amount'] + doubleval( number_format($item->quantity * $item->price, 2, '.', '') );
        }


        $header['amount'] = number_format($header['amount'], 2, '.', '');

        $report = new \App\Report('checkback.xml');

        $xml = $report->generate($header);
      
        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");


        $ret = self::send($xml, 'doc', $pos);
        if ($ret['success'] == true) {


            self::insertStat($pos->pos_id, 3, $amount0, $amount1, $amount2, $amount3, $doc->document_number);
        }
        $ret['fiscalamount']=  $header['amount'];
 
        $ret['fiscaltest']=  $pos->testing==1 ;

        return $ret;
    }

    //функции работы  со статистикой  для  z-отчета
    public static function insertStat($pos_id, $checktype, $amount0, $amount1, $amount2, $amount3, $document_number = '', $fiscnumber='') {
        $conn = \ZDB\DB::getConnect();

        if(strlen($document_number) >0) {
            $conn->Execute("delete from ppo_zformstat  where   document_number=". $conn->qstr($document_number));
        }

        $amount0 = number_format($amount0, 2, '.', '');
        $amount1 = number_format($amount1, 2, '.', '');
        $amount2 = number_format($amount2, 2, '.', '');
        $amount3 = number_format($amount3, 2, '.', '');
        $sql = "insert into ppo_zformstat (pos_id,checktype,  amount0,amount1,amount2,amount3,document_number,createdon,fiscnumber) values ({$pos_id},{$checktype}, {$amount0}, {$amount1},{$amount2},{$amount3}," . $conn->qstr($document_number) . "," . $conn->DBDate(time()) . ",'{$fiscnumber}')";

        $conn->Execute($sql);
    }

    public static function clearStat($pos_id) {
        $conn = \ZDB\DB::getConnect();

        $conn->Execute("delete from ppo_zformstat where  pos_id=" . $pos_id);
    }

    public static function delStat($id) {
        $conn = \ZDB\DB::getConnect();

        $conn->Execute("delete from ppo_zformstat where  zf_id=" . $id);
    }

    public static function getStat($pos_id, $ret = false) {
        $conn = \ZDB\DB::getConnect();

        $sql = "select count(*) as cnt, coalesce(sum(amount0),0)  as amount0, coalesce(sum(amount1),0)  as amount1, coalesce(sum(amount2),0) as amount2, coalesce(sum(amount3),0) as amount3 from  ppo_zformstat where    pos_id=" . $pos_id;
        if ($ret == true) {
            $sql = $sql . "  and checktype =3"; //возврат
        } else {
            $sql = $sql . "  and checktype <>3";
        }


        return $conn->GetRow($sql);
    }

    public static function getStatList($pos_id) {
        $conn = \ZDB\DB::getConnect();

        $sql = "select  * from  ppo_zformstat where  pos_id=" . $pos_id;

        $list = array();
        foreach($conn->Execute($sql) as $row) {
            $item = new \App\DataItem($row) ;
            $list[]= $item;

        }

        return $list;
    }

    public static function decrypt($data) {
        return PPO::decrypt($data, true);
    }


    public static function sync($pos_id) {
        $pos = \App\Entity\Pos::load($pos_id);


        if ($pos == 0) {
            return;
        }
     
        //"2022-05-01T00:00:00+03:00"

        $dt = new  \App\DateTime() ;
        
        $start = $dt->startOfMonth()  ;
        $from = date('c', $start->getTimestamp());

        $to = date('c');

        $res = PPOHelper::send(json_encode(array('Command' => 'Shifts', 'NumFiscal' => $pos->fiscalnumber, 'From' => $from, 'To' => $to)), 'cmd', $pos);

        if($res['success']==false) {
            \App\System::setErrorMsg($res['data']);
            return;
        }
        $res = json_decode($res['data']);
        $docs = array();
        if(is_array($res->Shifts)) {
            foreach ($res->Shifts as $sh) {
                if(strlen($sh->CloseName)==0) {


                    $res = PPOHelper::send(json_encode(array('Command' => 'Documents', 'NumFiscal' => $pos->fiscalnumber, 'ShiftId' => $sh->ShiftId)), 'cmd', $pos);


                    if($res['success']==false) {
                        \App\System::setErrorMsg($res['data']);
                        return;
                    }
                    $res = json_decode($res['data']);
                    if(is_array($res->Documents)) {

                        foreach ($res->Documents as $doc) {
                            if($doc->DocClass=='ZRep') {
                                continue;
                            }
                            if($doc->CheckDocType=='OpenShift') {
                                continue;
                            }
                            if($doc->CheckDocType=='CloseShift') {
                                continue;
                            }
                            $docs[$doc->NumFiscal] = $doc;
                        }
                    }
                }
            }
        }

        $conn = \ZDB\DB::getConnect();

        $sql = "select  zf_id,fiscnumber   from  ppo_zformstat where  pos_id=".$pos_id;
        $fd = array();
        foreach($conn->Execute($sql) as $d) {
            $fd[$d['fiscnumber']]= $d['zf_id'];
        }



        $conn->Execute("delete from  ppo_zformstat where pos_id=".$pos_id." and  fiscnumber is  null ") ;

        $fdocs = array_keys($docs)  ;//номера  в  налоговой
        $floc = array_keys($fd)  ;//номера  локально

        //удаляем лишние
        foreach($floc as $l) {
            if(in_array($l, $fdocs)==false) {
                $conn->Execute("delete from  ppo_zformstat where pos_id=".$pos_id." and  fiscnumber=".$conn->qstr($l)) ;
            }
        }
        //добавляем недостающие



        foreach($fdocs as $d) {
            if(in_array($d, $floc)==false) {


                $res = PPOHelper::send(json_encode(array('Command' => 'Check', 'RegistrarNumFiscal' => $pos->fiscalnumber, 'NumFiscal' =>  $d )), 'cmd', $pos);

                if($res['success']==false) {
                    continue;
                }
                $decrypted  = PPOHelper::decrypt($res['data']) ;

                //  $decrypted = mb_convert_encoding($decrypted , "utf-8" ,"windows-1251" )  ;

                $xml = simplexml_load_string($decrypted) ;
                if($xml==false) {
                    continue;
                }
                $st =  (string)$xml->CHECKHEAD->DOCSUBTYPE;


                $amount0=0;
                $amount1=0;
                $amount2=0;
                $amount3=0;


                foreach ($xml->CHECKPAY->children() as $row) {
                    $fc =  (string)$row->PAYFORMCD;
                    $sum =  (string)$row->SUM;
                    $sum  = doubleval($sum);
                    if($fc=="0") {
                        $amount0 += $sum;
                    } elseif($fc=="1") {
                        $amount1 += $sum;
                    } elseif($fc=="2") {
                        $amount2 += $sum;
                    } elseif($fc=="3") {
                        $amount3 += $sum;
                    } else {
                        \App\Helper::log("payform ".$fc);
                        \App\Helper::log($xml);
                    }
                }


                \App\Modules\PPO\PPOHelper::insertStat($pos_id, $st=="1" ? 2 : 0, $amount0, $amount1, $amount2, $amount3, '', $d);


            }

        }
    }

    /**
    * состояние фискального сервера
    *
    * @param mixed $pos
    */
    public static function checkServer($pos) {
        $res = PPOHelper::send(json_encode(array('Command' => 'ServerState')), 'cmd', $pos);
        if($res['success'] != true) {
            return  false;
        }
        $data = json_decode($res['data'], true);
        $ts = strtotime($data['Timestamp']) ;
        if($ts===false) {
            return  false;
        }

        return true;
    }

    /**
    * состояние  регистратора
    *
    * @param mixed $fiscnumber
    * @param mixed $pos
    */
    public static function rroState($fiscnumber, $pos) {
        $res = PPOHelper::send(json_encode(array('Command' => 'TransactionsRegistrarState','NumFiscal'=>$fiscnumber)), 'cmd', $pos);
        if($res['success'] != true) {
            return  false;
        }
        $res = json_decode($res['data'], true);
        return  $res;

    }

    /**
    * получение итоговых  данных по смене  для  z отчета
    *
    * @param mixed $fiscnumber
    * @param mixed $pos
    */
    public static function shiftTotal($fiscnumber, $pos) {
        $res = PPOHelper::send(json_encode(array('Command' => 'LastShiftTotals','NumFiscal'=>$fiscnumber)), 'cmd', $pos);
        if($res['success'] != true) {
            return  false;
        }

        $res = json_decode($res['data'], true);
        return  $res;


    }

    private static function getCashier($doc=null) {

        $cname = \App\System::getUser()->username;
        if($doc instanceof \App\Entity\Doc\Document) {
            if(strlen($doc->headerdata['cashier']) >0) {
                $cname = $doc->headerdata['cashier'];
            } else {
                $cname = $doc->username;
            }
            return $cname;            
        }
        $common = \App\System::getOptions("common");
        if(strlen($common['cashier'])>0) {
            $cname = $common['cashier'] ;
        }       
        return $cname;
    }

  /**
    * состояние сессии
    *
    * @param mixed $pos
    */
    public static function checkSession($pos) {
       
       
      
        $res = PPOHelper::send(json_encode(array('Command' => 'TransactionsRegistrarState','NumFiscal'=>$pos->fiscalnumber)), 'cmd', $pos);
        if($res['success'] != true) {
            return  false;
        }

        $res = json_decode($res['data'], true);
      
        return  $res['ShiftState'] == 1  ;
        
    }
    
    
    
    /**
    * автоматическое  закрытие  смены
    * 
    * @param mixed $posid
    */
    public static function autoshift($posid ) {
     
        $pos = \App\Entity\Pos::load($posid);
        if(self::checkSession($pos) != true) {
            return true;
        }

        $stat = self::getStat($posid);
        $rstat = self::getStat($posid, true);

        $ret = self::zform($posid, $stat, $rstat);
        if ($ret['success']==true) {
            $pos->fiscdocnumber = $ret['doclocnumber'] + 1;
            $pos->save();            
            $ret =  self::shift($posid,false) ;

        }
        if($ret['success'] == false) {
          H::logerror($ret['data'])    ;
           return false;
        }
 
        return true;
       

   }    
    
}
