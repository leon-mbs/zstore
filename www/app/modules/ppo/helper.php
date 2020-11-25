<?php

namespace App\Modules\PPO;


/**
 * Вспомагательный  класс
 */
class Helper
{

    public static function guid() {

        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    }

    public static function sign($data, $cid) {
        $c = \App\Entity\Firm::load($cid);


        $ap = explode(':', $c->pposerv);


        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT           => $ap[1],
            CURLOPT_URL            => "{$ap[0]}:{$ap[1]}/sign",
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

            return array('success'=>false,'data'=>'Curl error: ' . curl_error($request));

        }

        curl_close($request);
        $ret  = json_decode($ret,true);

       
        return array('success'=>true,'data'=>base64_decode($ret['data']));
        
    }

    public static function decrypt($data, $cid) {
        $c = \App\Entity\Firm::load($cid);


        $ap = explode(':', $c->pposerv);


        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT           => $ap[1],
            CURLOPT_URL            => "{$ap[0]}:{$ap[1]}/decrypt",
            CURLOPT_POST           => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS     => base64_encode($data) 
        ]);

        

        if (curl_errno($request) > 0) {
            return array('success'=>false,'data'=>'Curl error: ' . curl_error($request));


        }
        $ret = (curl_exec($request));
        
        curl_close($request);
        $data = json_decode($ret,true); 
        return array('success'=>true,'data'=>base64_decode($data['data']));
 
    }

    public static function send($data, $type, $cid, $encrypted = false) {

        $signed = Helper::sign($data, $cid);
        if ($signed['success'] == true) {


            $request = curl_init();

            

            curl_setopt_array($request, [
                CURLOPT_URL            => "http://80.91.165.208:8609/fs/{$type}",
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
                return array('success'=>false,'data'=>'Curl error: ' . curl_error($request));
            }
            curl_close($request);
            
            if (strpos($return, 'Номер документа повинен дорівнювати') > 0) {
                 $arr = explode(' ',$return);
                 if($arr[count($arr) - 1] > 0) {
                    return array('success'=>false, 'docnumber'=>$arr[count($arr) - 1],'data'=>$return);   
                 }
                 
                   
            }
            if (strpos($return, 'помилки') > 0) {
                
                 return array('success'=>false,'data'=>$return);
                  
            }
            
            //декодируем  подписаный ответ
            if ($encrypted) {
                
                $decrypted = Helper::decrypt($return, $cid);
               
                if ($decrypted['success'] == true ) {
                    //если  вернул  xml 
                    if(substr($decrypted['data'] ,0,5)=="<?xml" && $type=="doc") {
                          $xml = $decrypted['data'] ;
                         // $xml = mb_convert_encoding($xml , "utf-8", "windows-1251" )  ;  
                           
                          $xml = simplexml_load_string($xml);
                          $errorcode = (string) ($xml->ERRORCODE[0]);
                          $taxnum = (string) ($xml->ORDERTAXNUM[0]);
                          
                          if($errorcode=='0' && $taxnum>0)   //следующий номер  документа
                          {
                              return array('success'=>true, 'docnumber'=>$taxnum,'data'=>$return);   
                              
                          }
                          return array('success'=>false,  'data'=>$errorcode);  
                             
                    }                    
                    
                    
                    
                     return array('success'=>true,'data'=> $decrypted['data'] );
                } else {
                     return array('success'=>false,'data'=>$decrypted['data']);
                }

            } else {
                return    array('success'=>true,'data'=>$return); ;
            }


        } else {
             return array('success'=>false,'data'=>$signed['data']);
                 
             
        }


    }


    public static function shift($cid, $posid, $open) {
        $pos = \App\Entity\Pos::load($posid);

        $branch_id = \App\Session::getSession()->branch_id;
        $firm = \App\Helper::getFirmData($cid, $branch_id);
        $branch = \App\Entity\Branch::load($branch_id);

        $header = array();
        $header['doctype'] = $open == true ? 100 : 101;
        $header['firmname'] = $firm['firmname'];
        $header['inn'] = $firm['inn'];
        $header['edrpou'] = $firm['tin'];
        $header['address'] = $firm['address'];
        $header['branchname'] = strlen($branch->branch_name) > 0 ? $branch->branch_name : $firm['firmname'];
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc;
        $header['username'] = \App\System::getUser()->username;
        $header['guid'] = Helper::guid();

 
        $report = new \App\Report('shift.xml');

        $xml = $report->generate($header);

 
        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");
  

        return Helper::send($xml, 'doc', $cid, true);


    }
   public static function zform($cid, $posid ) {
        $pos = \App\Entity\Pos::load($posid);

        $branch_id = \App\Session::getSession()->branch_id;
        $firm = \App\Helper::getFirmData($cid, $branch_id);
        $branch = \App\Entity\Branch::load($branch_id);

        $header = array();
        $header['doctype'] = $open == true ? 100 : 101;
        $header['firmname'] = $firm['firmname'];
        $header['inn'] = $firm['inn'];
        $header['edrpou'] = $firm['tin'];
        $header['address'] = $firm['address'];
        $header['branchname'] = strlen($branch->branch_name) > 0 ? $branch->branch_name : $firm['firmname'];
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc;
        $header['username'] = \App\System::getUser()->username;
        $header['guid'] = Helper::guid();

 
        $report = new \App\Report('zform.xml');

        $xml = $report->generate($header);

        return  Helper::send($xml, 'doc', $cid, true);

    }

   public static function check($cid, $posid ) {
        $pos = \App\Entity\Pos::load($posid);

        $branch_id = \App\Session::getSession()->branch_id;
        $firm = \App\Helper::getFirmData($cid, $branch_id);
        $branch = \App\Entity\Branch::load($branch_id);

        $header = array();
      
        $header['firmname'] = $firm['firmname'];
        $header['inn'] = $firm['inn'];
        $header['edrpou'] = $firm['tin'];
        $header['address'] = $firm['address'];
        $header['branchname'] = strlen($branch->branch_name) > 0 ? $branch->branch_name : $firm['firmname'];
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscdocnumber;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc;
        $header['username'] = \App\System::getUser()->username;
        $header['guid'] = Helper::guid();
        $header['formname'] = 'Готiвка';
        $header['formcode'] = 0;
        if ($doc->headerdata['payform'] == 1) {
            $header['formname'] = 'Банкiвська  картка';
            $header['formcode'] = 1;
        }        

         $header['pay'] = $doc->payamount > 0; 
        $header['paysum'] = number_format($doc->payamount, 2, '.', '')   ;
        $header['payed'] =    number_format($doc->payed, 2, '.', '') ;
        $header['rest'] =  $doc->headerdata["exchange"] >0 ?   number_format($doc->headerdata["exchange"], 2, '.', '') : false;
        if($doc->payed  < $doc->payamount) {
            $header['paysum'] = number_format($doc->payed, 2, '.', '')   ;
            $header['payed'] =    number_format($doc->payed, 2, '.', '') ;  
            $header['rest'] = false;         
        }
       
       
        $header['disc'] =  $doc->headerdata["paydisc"] >0 ?   number_format($doc->headerdata["paydisc"], 2, '.', '') : false;
        $header['details'] = array();
        $n=1;
        $disc=1;
        if($doc->headerdata["paydisc"] >0 ) {
           $disc = 1 - ($doc->headerdata["paydisc"]/$doc->amount);
     
        }
        $header['amount'] =0;
        foreach ($doc->unpackDetails('detaildata') as  $item) {
            $header['details'][] = array(
                   'num'=>"ROWNUM=\"{$n}\"",
                   'name'=> $item->itemname ,   
                   'qty'=> number_format($item->quantity  , 3, '.', '') ,   
                   'price'=> number_format($item->price*$disc  , 2, '.', ''),    
                   'cost'=> number_format($item->quantity*$item->price*$disc  , 2, '.', '')    
            )  ;
            $n++;
            $header['amount'] = $header['amount']+ $item->quantity*$item->price*$disc;
        }
        foreach ($doc->unpackDetails('services') as  $item) {
            $header['details'][] = array(
                   'num'=>"ROWNUM=\"{$n}\"",
                   'name'=> $item->service_name ,   
                   'qty'=> number_format($item->quantity  , 3, '.', '') ,   
                   'price'=> number_format($item->price*$disc  , 2, '.', ''),    
                   'cost'=> number_format($item->quantity*$item->price*$disc  , 2, '.', '')    
            )  ;
            $n++;
            $header['amount'] = $header['amount']+ $item->quantity*$item->price*$disc;
        }
        
        
        $header['amount'] = number_format($header['amount'], 2, '.', ''); 
  
        $report = new \App\Report('check.xml');

        $xml = $report->generate($header);

        return  Helper::send($xml, 'doc', $cid, true);

    }

}