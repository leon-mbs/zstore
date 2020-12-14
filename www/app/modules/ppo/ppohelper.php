<?php

namespace App\Modules\PPO;


/**
 * Вспомагательный  класс      
 */
class PPOHelper
{
    
    const DPI_SERVER="http://80.91.165.208:8609/fs/" ;
    const PAY_NAL='Готiвка' ;
    const PAY_BEZNAL='Банкiвська картка' ;
    const PAY_CREDIT='В кредит' ;
    const PAY_PREPAID='Передплата' ;
    
 
    public static function guid() {

        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    }

    public static function sign($data,  $server,$port) {
   
 


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

            return array('success'=>false,'data'=>'Curl error: ' . curl_error($request));

        }

        curl_close($request);
        $ret  = json_decode($ret,true);

       
        return array('success'=>true,'data'=>base64_decode($ret['data']));
        
    }

    public static function decrypt($data,   $server,$port) {
 

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

    public static function send($data, $type, $server,$port, $encrypted = false) {

        $signed = self::sign($data, $server,$port);
        if ($signed['success'] == true) {


            $request = curl_init();

            

            curl_setopt_array($request, [
                CURLOPT_URL            => self::DPI_SERVER.$type,
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
                
                $decrypted = self::decrypt($return, $server,$port);
               
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
        $firm = \App\Helper::getFirmData($cid );
        $branch = \App\Entity\Branch::load($branch_id);

        $header = array();
        $header['doctype'] = $open == true ? 100 : 101;
        $header['firmname'] = $firm['firmname'];
        $header['inn'] = $firm['inn'];
        $header['edrpou'] = $firm['edrpou'];
        $header['address'] = $firm['address'];
        $header['branchname'] = strlen($branch->branch_name) > 0 ? $branch->branch_name : $firm['firmname'];
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscalnumber;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc;
        $header['username'] = \App\System::getUser()->username;
        $header['guid'] = self::guid();

 
        $report = new \App\Report('shift.xml');

        $xml = $report->generate($header);

 
        $xml = mb_convert_encoding($xml, "windows-1251", "utf-8");
  

        return self::send($xml, 'doc', $firm['signserver'],$firm['signserverport'], true);


    }
   
  public static function zform($cid, $posid ) {
        $pos = \App\Entity\Pos::load($posid);

        $branch_id = \App\Session::getSession()->branch_id;
        $firm = \App\Helper::getFirmData($cid );
        $branch = \App\Entity\Branch::load($branch_id);
     
        $stat = self::getStat($posid) ;
        
        $header = array();
        $header['doctype'] = $open == true ? 100 : 101;
        $header['firmname'] = $firm['firmname'];
        $header['inn'] = $firm['inn'];
        $header['edrpou'] = $firm['edrpou'];
        $header['address'] = $firm['address'];
        $header['branchname'] = strlen($branch->branch_name) > 0 ? $branch->branch_name : $firm['firmname'];
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscalnumber;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc;
        $header['username'] = \App\System::getUser()->username;
        $header['guid'] = self::guid();
        $header['pays']  = array();
        $header['paysr']  = array();
        $amount=0;
        $amountr=0;
        $cnt=0;
        $cntr=0;        
      
        $stat = self::getStat($pos->pos_id);
        //реализация
        foreach($stat as $row) {
           $n=1;
           $cnt = $cnt+ $row['cnt'];
           if($row['amount0']>0) {
               $header['pays'][] = array(
                   'formname'=> self::PAY_NAL,
                   'formcode'=> 0,
                   'sum'=> number_format($row['amount0'], 2, '.', '') ,
                   'num'=>"ROWNUM=\"{$n}\"" 
               );      
               $amount = $amount + $row['amount0'] ;
               $n++;
           }
           if($row['amount1']>0) {
               $header['pays'][] = array(
                   'formname'=> self::PAY_BEZNAL,
                   'formcode'=> 1,
                   'sum'=> number_format($row['amount1'], 2, '.', '') ,
                   'num'=>"ROWNUM=\"{$n}\"" 
               );      
               $amount = $amount + $row['amount1'] ;
               $n++;
           }
           if($row['amount2']>0) {
               $header['pays'][] = array(
                   'formname'=> self::PAY_CREDIT,
                   'formcode'=> 2,
                   'sum'=> number_format($row['amount2'], 2, '.', '') ,
                   'num'=>"ROWNUM=\"{$n}\"" 
               );      
               $amount = $amount + $row['amount2'] ;
               $n++;
           }
           if($row['amount3']>0) {
               $header['pays'][] = array(
                   'formname'=> self::PAY_PREPAID,
                   'formcode'=> 3,
                   'sum'=> number_format($row['amount3'], 2, '.', '') ,
                   'num'=>"ROWNUM=\"{$n}\"" 
               );      
               $amount = $amount + $row['amount3'] ;
               $n++;
           }
        }

        $stat = self::getStat($pos->pos_id,true);
        //возврат
        foreach($stat as $row) {
           $n=1;
           $cntr = $cntr+ $row['cnt'];
           if($row['amount0']>0) {
               $header['paysr'][] = array(
                   'formname'=> self::PAY_NAL,
                   'formcode'=> 0,
                   'sum'=> number_format($row['amount0'], 2, '.', '') ,
                   'num'=>"ROWNUM=\"{$n}\"" 
               );      
               $amountr = $amountr + $row['amount0'] ;
               $n++;
           }
           if($row['amount1']>0) {
               $header['paysr'][] = array(
                   'formname'=> self::PAY_BEZNAL,
                   'formcode'=> 1,
                   'sum'=> number_format($row['amount1'], 2, '.', '') ,
                   'num'=>"ROWNUM=\"{$n}\"" 
               );      
               $amountr = $amountr + $row['amount1'] ;
               $n++;
           }
      
        }
        /*
        $cnt=6;
              $header['pays'][] = array(
                   'formname'=> 'Готiвка',
                   'formcode'=> 0,
                   'sum'=> number_format(64, 2, '.', '') ,
                   'num'=>"ROWNUM=\"1\"" 
               );  
        
             $header['pays'][] = array(
                   'formname'=> 'Наличная',
                   'formcode'=> 1,
                   'sum'=> number_format(30.3, 2, '.', '') ,
                   'num'=>"ROWNUM=\"2\"" 
               );  
        
                */
        $header['amount'] = number_format($amount, 2, '.', '');
        $header['amountr'] = number_format($amountr, 2, '.', '');
        $header['cnt'] = $cnt  ;
        $header['cntr'] = $cntr ;
        $header['isreal'] = $cnt>0 ;
        $header['isret'] = $cntr>0 ;
   
        $report = new \App\Report('zform.xml');

        $xml = $report->generate($header);
        
        
        $xml = mb_convert_encoding($xml , "windows-1251","utf-8"  )  ;       

        return  self::send($xml, 'doc', $firm['signserver'],$firm['signserverport'], true);

    }

   public static function check($cid, $posid ) {
        $pos = \App\Entity\Pos::load($posid);

        $branch_id = \App\Session::getSession()->branch_id;
        $firm = \App\Helper::getFirmData($cid, $branch_id);
        $branch = \App\Entity\Branch::load($branch_id);
        
 
               
        $header = array( );
        $header['doctype'] = $doctype  ;
        $header['docsubtype'] = $docsubtype ;
        $header['firmname'] = $firm['firmname']  ;
        $header['inn'] = $firm['inn'];
        $header['edrpou'] =  $firm['edrpou'];
        $header['address'] = $firm['address']  ; 
        $header['branchname'] =  $branch->branch_name  ;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscalnumber;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc ;
        $header['username'] =   $doc->username  ;
        $header['guid'] = self::guid();
       
       
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
        
        $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);
        
        $amount0=0;
        $amount1=0;
        $amount2=0;
        $amount3=0;
        $header['pays'] = array();
        $n=1;
 
        if ($doc->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID) {
            $pay = array(
               'formname'=> self::PAY_PREPAID,
               'formcode'=> 3,
               'paysum'=> number_format($header['amount'], 2, '.', '') ,
               'payed'=> number_format($header['amount'], 2, '.', '') ,
               'num'=>"ROWNUM=\"{$n}\"" 
            );
         
            $header['pays'][]=$pay;
            $n++;
            $amount3 = $pay['paysum'];            
         }  else  
         if ( $mf !=null && $mf->beznal==1) {
            $pay = array(
               'formname'=> self::PAY_BEZNAL,
               'formcode'=> 1,
               'paysum'=> number_format($doc->payamount, 2, '.', '') ,
               'payed'=> number_format($doc->payamount, 2, '.', '') ,
               'num'=>"ROWNUM=\"{$n}\"" 
            );
            // в долг
            if($doc->payed  < $doc->payamount) {
                $pay['paysum'] = number_format($doc->payamount, 2, '.', '')   ;
                $pay['payed'] =    number_format($doc->payed, 2, '.', '') ;  
                       
            }          
            $header['pays'][]=$pay;
            $n++;
            $amount1 = $pay['paysum'];            
        }  else {
            
            $pay = array(
               'formname'=> self::PAY_NAL,
               'formcode'=> 0,
               'paysum'=> number_format($doc->payamount, 2, '.', '') ,
               'payed'=> number_format($doc->payed, 2, '.', '') ,
               'rest'=>false,
               'num'=>"ROWNUM=\"{$n}\"" 
            );
            //сдача
            if($doc->headerdata["exchange"] >0 ){
               $pay['rest'] = number_format($doc->headerdata["exchange"], 2, '.', '');
            }
            // в долг
            if($doc->payed  < $doc->payamount) {
                $pay['paysum'] = number_format($doc->payed, 2, '.', '')   ;
                $pay['payed'] =    number_format($doc->payed, 2, '.', '') ;  
                       
            }              
             
            $header['pays'][]=$pay;
            $n++;
            $amount0 = $pay['paysum'];
        }      
        // в долг
        if($doc->payed  < $doc->payamount) {
             $pay = array(
               'formname'=>self::PAY_CREDIT,
               'formcode'=> 2,
               'paysum'=> number_format($doc->payamount - $doc->payed, 2, '.', '') ,
               'payed'=> number_format($doc->payamount - $doc->payed, 2, '.', '') ,
               'rest'=>false,
               'num'=>"ROWNUM=\"{$n}\"" 
            );                       
            $header['pays'][]=$pay;           
            $n++;
            
            $amount2 = $pay['paysum'];
        } 
        
        $header['pay'] = count($header['pays'])>0 ;
        $header['disc'] = false;
         
        $header['amount'] = number_format($header['amount'], 2, '.', ''); 
        if($doc->headerdata["paydisc"] >0 ) {
           // $header['disc']  = number_format($doc->headerdata["paydisc"], 2, '.', '') ;
     
        }       
        $report = new \App\Report('check.xml');
        
        $xml = $report->generate($header);
 
        $xml = mb_convert_encoding($xml , "windows-1251","utf-8"  )  ;       
   
        $ret = self::send($xml,'doc',$firm['signserver'],$firm['signserverport']);   
        if($ret['success'] == true){
               
            self::insertStat($pos->pos_id,1,$amount0,$amount1,$amount2,$amount3,$doc->document_number);
        }
        
        return $ret;

        
    }    
 
    public static function checkpay($cid,$doc  ) {

          
        $firm = \App\Helper::getFirmData($cid );
        $branch = \App\Entity\Branch::load($doc->branch_id);
        //todo  терминал  что ща  открыта  смена      
        $pos = \App\Entity\Pos::load($doc->headerdata['pos']);
        
        $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);
         
        $header = array( );
 
        $header['firmname'] = $firm['firmname']  ;
        $header['inn'] = $firm['inn'];
        $header['edrpou'] =  $firm['edrpou'];
        $header['address'] = $firm['address']  ; 
        $header['branchname'] =  $branch->branch_name  ;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscalnumber;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc ;
        $header['username'] =   $doc->username  ;
        $header['guid'] = self::guid();
     
        $amount0=0;
        $amount1=0;
        $amount2=0;
        $amount3=0;
     
     
        
        if ($mf !=null && $mf->beznal==1) {
            $header['formname'] = self::PAY_BEZNAL;
            $header['formcode'] = 1;
            $amount1  = number_format($doc->payed, 2, '.', '')  ;            
        }  else {
           $header['formname'] = self::PAY_NAL;
           $header['formcode'] = 0;
           $amount0  = number_format($doc->payed, 2, '.', '')  ;
        }      
      //  $header['amount'] = number_format($doc->amount, 2, '.', ''); 
        $header['pay'] = $doc->payed > 0; 
        $header['paysum'] = number_format($doc->payed, 2, '.', '')   ;
        $header['payed'] =    number_format($doc->payed, 2, '.', '') ;
      
   
        $header['parentcheck'] =  $doc->document_number ;
        $header['amount'] = number_format($doc->payed, 2, '.', '') ;

        $report = new \App\Report('checkpay.xml');
        
        $xml = $report->generate($header);
 
       $xml = mb_convert_encoding($xml , "windows-1251","utf-8"  )  ;       
 
       $ret =  self::send($xml,'doc',$firm['signserver'],$firm['signserverport']);   
       if($ret['success'] == true){

            
            
            
            self::insertStat($pos->pos_id,2,$amount0,$amount1,$amount2,$amount3,$doc->document_number);
        }
        
        return $ret;

        
    }    
 
     //возврат
    public static function checkback($cid,$doc ) {
       
 
        
        $firm = \App\Helper::getFirmData($cid );
        $branch = \App\Entity\Branch::load($doc->branch_id);
       //todo  терминал  что ща  открыта  смена      
        $pos = \App\Entity\Pos::load($doc->headerdata['pos']);
        $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);
       
     
               
        $header = array( );
        $header['doctype'] = $doctype  ;
        $header['docsubtype'] = $docsubtype ;
        $header['firmname'] = $firm['firmname']  ;
        $header['inn'] = $firm['inn'];
        $header['edrpou'] =  $firm['edrpou'];
        $header['address'] = $firm['address']  ; 
        $header['branchname'] =  $branch->branch_name  ;
        $header['date'] = date('dmY');
        $header['time'] = date('His');
        $header['docnumber'] = $pos->fiscalnumber ;
        $header['docnumberback'] = $doc->headerdata["docnumberback"] ;
        $header['posinner'] = $pos->posinner;
        $header['posnumber'] = $pos->fisc ;
        $header['username'] =   $doc->username  ;
        $header['guid'] = self::guid();
        $amount0=0;
        $amount1=0;
        $amount2=0;
        $amount3=0;
     
     
        
        if ($mf !=null && $mf->beznal==1) {
            $header['formname'] = self::PAY_BEZNAL;
            $header['formcode'] = 1;
            $amount1  = number_format($doc->payed, 2, '.', '')  ;            
        }  else {
           $header['formname'] = self::PAY_NAL;
           $header['formcode'] = 0;
           $amount0  = number_format($doc->payed, 2, '.', '')  ;
        }       
          
        $header['paysum'] = number_format($doc->payed, 2, '.', '')   ;
        $header['payed'] =    number_format($doc->payed, 2, '.', '') ;
     
        $header['details'] = array();
        
        $n=1;
        $header['amount'] =0;
        foreach ($doc->detaildata as  $item) {
            $header['details'][] = array(
                   'num'=>"ROWNUM=\"{$n}\"",
                   'name'=> $item['itemname'] ,   
                   'qty'=> number_format($item['quantity']   , 3, '.', '') ,   
                   'price'=> number_format($item['price']    , 2, '.', ''),    
                   'cost'=> number_format($item['quantity']  * $item['price']    , 2, '.', '')    
            )  ;
            $n++;
            $header['amount'] = $header['amount'] + $item['quantity']  * $item['price']  ;
        }
 
        
        $header['amount'] = number_format($header['amount'], 2, '.', ''); 
        
        $report = new \App\Report('checkback.xml');
        
        $xml = $report->generate($header);
 
        $xml = mb_convert_encoding($xml , "windows-1251","utf-8"  )  ;       
 
   
        $ret =  self::send($xml,'doc',$firm['signserver'],$firm['signserverport']);   
        if($ret['success'] == true){
   
            
            self::insertStat($pos->pos_id,3,$amount0,$amount1,$amount2,$amount3,$doc->document_number);
        }
        
        return $ret;
     
    }    
     
     
     public static function insertStat($pos_id,$checktype,$amount0,$amount1,$amount2,$amount3,$document_number=''){
          $conn = \ZDB\DB::getConnect();
          $amount0 = number_format($amount0, 2, '.', '') ;
          $amount1 = number_format($amount1, 2, '.', '') ;
          $amount2 = number_format($amount2, 2, '.', '') ;
          $amount3 = number_format($amount3, 2, '.', '') ;
          $sql = "insert into ppo_zformstat (pos_id,checktype,  amount0,amount1,amount2,amount3,document_number,createdon) values ({$pos_id},{$checktype}, {$amount0}, {$amount1},{$amount2},{$amount3},". $conn->qstr($document_number) .",". $conn->DBDate(time()) .")";
          
          $conn->Execute($sql) ;
       
     }
     
     public static function clearStat($pos_id ){
          $conn = \ZDB\DB::getConnect();
          
          $conn->Execute("delete from ppo_zformstat where  pos_id=".$pos_id ) ;
       
     }
  
     public static function getStat($pos_id,$ret=false ){
          $conn = \ZDB\DB::getConnect();
          
          $sql = "select count(*) as cnt, coalesce(sum(amount0),0)  as amount0, coalesce(sum(amount1),0)  as amount1, coalesce(sum(amount2),0) as amount2, coalesce(sum(amount3),0) as amount3 from  ppo_zformstat where  pos_id=".$pos_id;
          if($ret==true){
             $sql = $sql . "  and checktype =3" ; //возврат
          }  else {
             $sql = $sql . "  and checktype <>3" ; 
          }
          
          
          return $conn->Execute($sql) ;
       
     }
       
}
 