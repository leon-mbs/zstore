<?php

namespace App\Modules\PPO;

use \App\Helper as H;

/**
 * Вспомагательный  класс для  фискализации
 */
class PPOHelper
{

    const DPI = "http://80.91.165.208:8609/fs/";

    /**
     * цифровая подпись  данных
     *
     * @param mixed $data
     * @param mixed $server сервер  подписи
     * @param mixed $port порт сервера
     */
    public static function sign($data, $server, $port) {

        $server = rtrim($server, '/');

        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT           => $port,
            CURLOPT_URL            => "{$server}:{$port}/sign",
            CURLOPT_POST           => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS     => $data
        ]);

        $ret = curl_exec($request);
        if (curl_errno($request) > 0) {

            return array('success' => false, 'data' => 'Curl error: ' . curl_error($request));
        }

        curl_close($request);
        $ret = json_decode($ret, true);

        return array('success' => true, 'data' => base64_decode($ret['data']));
    }

    /**
     * проверка  подписи  и распаковка  ответа
     *
     * @param mixed $data
     * @param mixed $server
     * @param mixed $port
     */
    public static function decrypt($data, $server, $port) {


        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT           => $port,
            CURLOPT_URL            => "{$server}:{$port}/decrypt",
            CURLOPT_POST           => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS     => base64_encode($data)
        ]);

        if (curl_errno($request) > 0) {
            return array('success' => false, 'data' => 'Curl error: ' . curl_error($request));
        }
        $ret = (curl_exec($request));

        curl_close($request);
        $data = json_decode($ret, true);
        return array('success' => true, 'data' => base64_decode($data['data']));
    }

    /**
     * Отправка  данных  в  налоговую
     *
     * @param mixed $data
     * @param mixed $type cmd (команда) или  doc (документ)
     * @param mixed $server сервер налоговой
     * @param mixed $port порт сервера
     * @param mixed $encrypted ответ  требующий распаковки  и провкри  (как  правила  в  случае  отправки документа)
     */
    public static function send($data, $type, $server, $port, $encrypted) {

        $signed = self::sign($data, $server, $port);
        if (strlen($signed['data']) == 0) {
            return array('success' => false, 'data' => 'Неверный ответ сервера  подписи');
        }

        if ($signed['success'] == true) {


            $request = curl_init();

            curl_setopt_array($request, [
                CURLOPT_URL            => self::DPI . $type,
                CURLOPT_POST           => true,
                CURLOPT_HEADER         => false,
                CURLOPT_HTTPHEADER     => array('Content-Type: application/octet-stream', "Content-Length: " . strlen($signed['data'])),
                CURLOPT_ENCODING       => "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_VERBOSE        => 1,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_POSTFIELDS     => $signed['data']
            ]);

            $return = curl_exec($request);

            if (curl_errno($request) > 0) {
                return array('success' => false, 'data' => 'Curl error: ' . curl_error($request));
            }
            curl_close($request);

            if (strpos($return, 'Номер документа повинен дорівнювати') > 0) {
                $arr = explode(' ', $return);
                if ($arr[count($arr) - 1] > 0) {
                    return array('success' => false, 'docnumber' => $arr[count($arr) - 1], 'data' => $return);
                }
            }
            if (strpos($return, 'помилки') > 0) {

                return array('success' => false, 'data' => $return);
            }

            //декодируем  подписаный ответ
            if ($encrypted) {

                $decrypted = self::decrypt($return, $server, $port);

                if ($decrypted['success'] == true) {
                    //если  вернул  xml 
                    if (substr($decrypted['data'], 0, 5) == "<?xml" && $type == "doc") {
                        $xml = $decrypted['data'];
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

                    return array('success' => true, 'data' => $decrypted['data']);
                } else {
                    return array('success' => false, 'data' => $decrypted['data']);
                }
            } else {
                return array('success' => true, 'data' => $return);;
            }
        } else {
            return array('success' => false, 'data' => $signed['data']);
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

        $firm = \App\Helper::getFirmData($pos->firm_id);

        $header = array();
        $header['doctype'] = $open == true ? 100 : 101;
        $header['firmname'] = $firm['firm_name'];
        $header['inn'] = strlen($firm['inn']) > 0 ? $firm['inn'] : false;
        $header['tin'] = $firm['tin'];
        $header['address'] = $pos->address;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = \App\System::getUser()->username;
        $header['guid'] = \App\Util::guid();

        $report = new \App\Report('shift.xml');

        $xml = $report->generate($header);

        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");

        return self::send($xml, 'doc', $firm['pposerver'], $firm['pposerverport'], true);
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

        $firm = \App\Helper::getFirmData($pos->firm_id);

        $header = array();
        $header['doctype'] = $open == true ? 100 : 101;
        $header['firmname'] = $firm['firm_name'];
        $header['inn'] = strlen($firm['inn']) > 0 ? $firm['inn'] : false;
        $header['tin'] = $firm['tin'];
        $header['address'] = $pos->address;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = \App\System::getUser()->username;
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
                'formname' => H::l('ppo_nal'),
                'formcode' => 0,
                'sum'      => number_format($stat['amount0'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amount = $amount + $stat['amount0'];
            $n++;
        }
        if ($stat['amount1'] > 0) {
            $header['pays'][] = array(
                'formname' => H::l('ppo_bnal'),
                'formcode' => 1,
                'sum'      => number_format($stat['amount1'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amount = $amount + $stat['amount1'];
            $n++;
        }
        if ($stat['amount2'] > 0) {
            $header['pays'][] = array(
                'formname' => H::l('ppo_credit'),
                'formcode' => 2,
                'sum'      => number_format($stat['amount2'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amount = $amount + $stat['amount2'];
            $n++;
        }
        if ($stat['amount3'] > 0) {
            $header['pays'][] = array(
                'formname' => H::l('ppo_prepaid'),
                'formcode' => 3,
                'sum'      => number_format($stat['amount3'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amount = $amount + $stat['amount3'];
            $n++;
        }


        //возврат

        $n = 1;

        if ($rstat['amount0'] > 0) {
            $header['paysr'][] = array(
                'formname' => H::l('ppo_nal'),
                'formcode' => 0,
                'sum'      => number_format($rstat['amount0'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );
            $amountr = $amountr + $rstat['amount0'];
            $n++;
        }
        if ($rstat['amount1'] > 0) {
            $header['paysr'][] = array(
                'formname' => H::l('ppo_bnal'),
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

        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");

        return self::send($xml, 'doc', $firm['pposerver'], $firm['pposerverport'], true);
    }

  
    
    /**
     * отправка  чека
     *
     * @param mixed $doc
     */
    public static function check($doc) {


        $pos = \App\Entity\Pos::load($doc->headerdata['pos']);
        $firm = \App\Helper::getFirmData($pos->firm_id);

        $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);

        $header = array();
        $header['doctype'] = $doctype;
        $header['docsubtype'] = $docsubtype;
        $header['firmname'] = $firm['firm_name'];
        $header['inn'] = strlen($firm['inn']) > 0 ? $firm['inn'] : false;
        $header['tin'] = $firm['tin'];
        $header['address'] = $pos->address;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = $doc->username;
        $header['guid'] = \App\Util::guid();

        $header['disc'] = $doc->headerdata["paydisc"] > 0 ? number_format($doc->headerdata["paydisc"], 2, '.', '') : false;
        $header['details'] = array();
        $n = 1;
        $disc = 1;
        if ($doc->headerdata["paydisc"] > 0) {
            $disc = 1 - ($doc->headerdata["paydisc"] / $doc->amount);
        }
        $header['amount'] = 0;
        foreach ($doc->unpackDetails('detaildata') as $item) {
            $header['details'][] = array(
                'num'   => "ROWNUM=\"{$n}\"",
                'name'  => $item->itemname,
                'qty'   => number_format($item->quantity, 3, '.', ''),
                'price' => number_format($item->price * $disc, 2, '.', ''),
                'cost'  => number_format($item->quantity * $item->price * $disc, 2, '.', '')
            );
            $n++;

            $header['amount'] = $header['amount'] + $item->quantity * $item->price * $disc;
        }
        foreach ($doc->unpackDetails('services') as $item) {
            $header['details'][] = array(
                'num'   => "ROWNUM=\"{$n}\"",
                'name'  => $item->service_name,
                'qty'   => number_format($item->quantity, 3, '.', ''),
                'price' => number_format($item->price * $disc, 2, '.', ''),
                'cost'  => number_format($item->quantity * $item->price * $disc, 2, '.', '')
            );
            $n++;
            $header['amount'] = $header['amount'] + $item->quantity * $item->price * $disc;
        }
        $amount0 = 0;
        $amount1 = 0;
        $amount2 = 0;
        $amount3 = 0;
        $header['pays'] = array();
        $n = 1;

        if ($doc->headerdata['payment'] == 0) {
            $pay = array(
                'formname' => H::l('ppo_prepaid'),
                'formcode' => 3,
                'paysum'   => number_format($header['amount'], 2, '.', ''),
                'payed'    => number_format($header['amount'], 2, '.', ''),
                'num'      => "ROWNUM=\"{$n}\""
            );

            $header['pays'][] = $pay;
            $n++;
            $amount3 = $pay['paysum'];
        } else {
            if ($mf != null && $mf->beznal == 1) {
                $pay = array(
                    'formname' => H::l('ppo_bnal'),
                    'formcode' => 1,
                    'paysum'   => number_format($doc->payamount, 2, '.', ''),
                    'payed'    => number_format($doc->payamount, 2, '.', ''),
                    'num'      => "ROWNUM=\"{$n}\""
                );
                // в долг
                if ($doc->payed < $doc->payamount) {
                    $pay['paysum'] = number_format($doc->payamount, 2, '.', '');
                    $pay['payed'] = number_format($doc->payed, 2, '.', '');
                }
                $header['pays'][] = $pay;
                $n++;
                $amount1 = $pay['paysum'];
            } else {

                $pay = array(
                    'formname' => H::l('ppo_nal'),
                    'formcode' => 0,
                    'paysum'   => number_format($doc->payamount, 2, '.', ''),
                    'payed'    => number_format($doc->payed, 2, '.', ''),
                    'rest'     => false,
                    'num'      => "ROWNUM=\"{$n}\""
                );
                //сдача
                if ($doc->headerdata["exchange"] > 0) {
                    $pay['rest'] = number_format($doc->headerdata["exchange"], 2, '.', '');
                }
                // в долг
                if ($doc->payed < $doc->payamount) {
                    $pay['paysum'] = number_format($doc->payed, 2, '.', '');
                    $pay['payed'] = number_format($doc->payed, 2, '.', '');
                }

                $header['pays'][] = $pay;
                $n++;
                $amount0 = $pay['paysum'];
            }
        }
        // в долг
        if ($doc->payed < $doc->payamount) {
            $pay = array(
                'formname' => H::l('ppo_credit'),
                'formcode' => 2,
                'paysum'   => number_format($doc->payamount - $doc->payed, 2, '.', ''),
                'payed'    => number_format($doc->payamount - $doc->payed, 2, '.', ''),
                'rest'     => false,
                'num'      => "ROWNUM=\"{$n}\""
            );
            $header['pays'][] = $pay;
            $n++;

            $amount2 = $pay['paysum'];
        }

        $header['pay'] = count($header['pays']) > 0;
        $header['disc'] = false;

        $header['amount'] = number_format($header['amount'], 2, '.', '');
        if ($doc->headerdata["paydisc"] > 0) {
            // $header['disc']  = number_format($doc->headerdata["paydisc"], 2, '.', '') ;
        }
        $report = new \App\Report('check.xml');

        $xml = $report->generate($header);
        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");

        $ret = self::send($xml, 'doc', $firm['pposerver'], $firm['pposerverport'], true);
        if ($ret['success'] == true) {

            self::insertStat($pos->pos_id, 1, $amount0, $amount1, $amount2, $amount3, $doc->document_number);
        }

        return $ret;
    }

    /**
     * отправка  доплаты
     *
     * @param mixed $doc
     */
    public static function checkpay($doc, $pos_id, $payed, $payment) {


        $pos = \App\Entity\Pos::load($pos_id);
        $firm = \App\Helper::getFirmData($pos->firm_id);
        $mf = \App\Entity\MoneyFund::load($payment);

        $header = array();

        $header['firmname'] = $firm['firm_name'];
        $header['inn'] = strlen($firm['inn']) > 0 ? $firm['inn'] : false;
        $header['tin'] = $firm['tin'];
        $header['address'] = $pos->address;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = \App\System::getUser()->username;
        $header['guid'] = \App\Util::guid();

        $amount0 = 0;
        $amount1 = 0;
        $amount2 = 0;
        $amount3 = 0;

        if ($mf != null && $mf->beznal == 1) {
            $header['formname'] = H::l('ppo_bnal');
            $header['formcode'] = 1;
            $amount1 = number_format($payed, 2, '.', '');
        } else {
            $header['formname'] = H::l('ppo_nal');
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

        $ret = self::send($xml, 'doc', $firm['pposerver'], $firm['pposerverport'], true);
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
        $firm = \App\Helper::getFirmData($pos->firm_id);
        $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);

        $header = array();
        $header['doctype'] = $doctype;
        $header['docsubtype'] = $docsubtype;
        $header['firmname'] = $firm['firm_name'];
        $header['inn'] = strlen($firm['inn']) > 0 ? $firm['inn'] : false;;
        $header['tin'] = $firm['tin'];
        $header['address'] = $pos->address;
        $header['pointname'] = $pos->pointname;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumberback'] = $doc->headerdata["docnumberback"];
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->fiscallocnumber;
        $header['posnumber'] = $pos->fiscalnumber;
        $header['username'] = $doc->username;
        $header['guid'] = \App\Util::guid();
        $amount0 = 0;
        $amount1 = 0;
        $amount2 = 0;
        $amount3 = 0;

        if ($mf != null && $mf->beznal == 1) {
            $header['formname'] = H::l('ppo_bnal');
            $header['formcode'] = 1;
            $amount1 = number_format($doc->payed, 2, '.', '');
        } else {
            $header['formname'] = H::l('ppo_nal');
            $header['formcode'] = 0;
            $amount0 = number_format($doc->payed, 2, '.', '');
        }

        $header['paysum'] = number_format($doc->payed, 2, '.', '');
        $header['payed'] = number_format($doc->payed, 2, '.', '');

        $header['details'] = array();

        $n = 1;
        $header['amount'] = 0;
        foreach ($doc->unpackDetails('detaildata') as $item) {
            $header['details'][] = array(
                'num'   => "ROWNUM=\"{$n}\"",
                'name'  => $item->itemname,
                'qty'   => number_format($item->quantity, 3, '.', ''),
                'price' => number_format($item->price, 2, '.', ''),
                'cost'  => number_format($item->quantity * $item->price, 2, '.', '')
            );
            $n++;
            $header['amount'] = $header['amount'] + $item->quantity * $item->price;
        }


        $header['amount'] = number_format($header['amount'], 2, '.', '');

        $report = new \App\Report('checkback.xml');

        $xml = $report->generate($header);

        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");

        $ret = self::send($xml, 'doc', $firm['pposerver'], $firm['pposerverport'], true);
        if ($ret['success'] == true) {


            self::insertStat($pos->pos_id, 3, $amount0, $amount1, $amount2, $amount3, $doc->document_number);
        }

        return $ret;
    }

    //функции работы  со статистикой  для  z-отчета 
    public static function insertStat($pos_id, $checktype, $amount0, $amount1, $amount2, $amount3, $document_number = '') {
        $conn = \ZDB\DB::getConnect();
        $amount0 = number_format($amount0, 2, '.', '');
        $amount1 = number_format($amount1, 2, '.', '');
        $amount2 = number_format($amount2, 2, '.', '');
        $amount3 = number_format($amount3, 2, '.', '');
        $sql = "insert into ppo_zformstat (pos_id,checktype,  amount0,amount1,amount2,amount3,document_number,createdon) values ({$pos_id},{$checktype}, {$amount0}, {$amount1},{$amount2},{$amount3}," . $conn->qstr($document_number) . "," . $conn->DBDate(time()) . ")";

        $conn->Execute($sql);
    }

    public static function clearStat($pos_id) {
        $conn = \ZDB\DB::getConnect();

        $conn->Execute("delete from ppo_zformstat where  pos_id=" . $pos_id);
    }

    public static function getStat($pos_id, $ret = false) {
        $conn = \ZDB\DB::getConnect();

        $sql = "select count(*) as cnt, coalesce(sum(amount0),0)  as amount0, coalesce(sum(amount1),0)  as amount1, coalesce(sum(amount2),0) as amount2, coalesce(sum(amount3),0) as amount3 from  ppo_zformstat where  pos_id=" . $pos_id;
        if ($ret == true) {
            $sql = $sql . "  and checktype =3"; //возврат
        } else {
            $sql = $sql . "  and checktype <>3";
        }


        return $conn->GetRow($sql);
    }

}
