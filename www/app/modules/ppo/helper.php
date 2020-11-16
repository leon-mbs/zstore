<?php

namespace App\Modules\PPO;

 

/**
 * Вспомагательный  класс   
 */
class Helper
{
    
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
   
        if($open==false){
            
                
              $header['doctype']   = 102;
                
              $report = new \App\Report('zform.xml');
            
              $xml = $report->generate($header);
            
              $ret = Helper::send($xml,'doc',$cid,true); 
            
        }
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