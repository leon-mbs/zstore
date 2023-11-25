<?php

namespace App\Modules\VK;
use App\Helper as H;
/**
* Хелперный класс для  ВчасноКасса
*/
class VK
{
    protected string $access_token;  //JRvbIyE8ri0CfbsHyUDx7RggQilRIWNz_8bSr1RL4_R1H33GkGWlQY9VhvgAoKNj
 
    protected const API_URL = 'https://kasa.vchasno.ua/api/v3/fiscal/execute';   //https://wiki.checkbox.ua/uk/api/specification

    public function __construct($access_token ) {
        $this->access_token = $access_token;

    }

 
    public function OpenShift($open=true) {

        
        $req=array('fiscal'=>array('task'=>0,'cashier'=>self::getCashier())) ;
        if($open==false) {
           $req=array('fiscal'=>array('task'=>11)) ;
        }
        
        $body=json_encode($req, JSON_UNESCAPED_UNICODE);
   
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL ,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body ,           
            CURLOPT_SSL_VERIFYPEER =>false,

            CURLOPT_HTTPHEADER => [
                "Authorization: {$this->access_token}" 
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);


        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if($status_code != 200) {
           return "HTTP Error #:" . $status_code. ' ' . $response;
        }


        $response = json_decode($response, true);
        if($response['res_action']==0) {
            return true;
        } 

        if(strlen($response['errortxt'])>0) {
            return $response['errortxt'];
        }
        if($response['res']>0) {
            return "Помилка ".$response['res'];
        }
  
        return true;

    }


    public function CloseShift() {

        return self::OpenShift(false); 

    }

    public function Check($doc) {
   

        $check = [] ;
        $check["rows"] = [] ;
        $check["pays"] = [] ;
    
        $sum = 0;
 



        foreach($doc->unpackDetails('detaildata') as $item) {
            $good=[];
   
            $good['name'] = $item->itemname;
            $good['price'] = self::fa($item->price); 
            $good['code'] = $item->item_id;
            $good['disc'] = 0;
            $good['taxgrp'] = 7;

         
            $good["cnt"] = self::fqty($item->quantity)  ;

            $sum +=   ($good['price'] * $item->quantity);

            $check["rows"][] = $good;


        }

        foreach($doc->unpackDetails('services') as $item) {
            $good=[];
 
   
            $good['name'] = $item->service_name;
            $good['price'] =self::fa($item->price);
            $good['code'] = $item->service_id;
            $good['disc'] = 0;
            $good['taxgrp'] = 7;

         
            $good["cnt"] = self::fqty($item->quantity)  ;

            $sum +=   ($good['price'] * $item->quantity);

            $check["rows"][] = $good;

        }


        $check['sum'] =  self::fa($sum); 

  
        if($doc->headerdata['payment']??''  >0) {


             
            if ($doc->headerdata['payment'] == 0 && $payed > 0) {
                $payment=array(
                "type"=>1,
                "sum"=>  self::fa($doc->headerdata['payed'] )  
                );
                if($doc->headerdata['exchange'] > 0) {
                    $payment['change']  = self::fa($doc->headerdata['exchange'] );  
                    $payment['sum']  = self::fa($payment['sum'] -$doc->headerdata['exchange'] );  
               }
                $check["pays"][] = $payment;
            }
            if ($doc->headerdata['payment'] > 0 && $doc->payed > 0) {
                $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);
                if ($mf->beznal == 1) {
                    $payment=array(
                    "type"=>2,
                    "sum"=>self::fa($doc->headerdata['payed'] )
                    );
                } else {
                   $payment=array(
                    "type"=>0,
                    "sum"=>self::fa($doc->headerdata['payed']  )
                     );
                    if($doc->headerdata['exchange'] ?? 0 > 0) {
                        $payment['change']  = self::fa($doc->headerdata['exchange'] );  
                        $payment['sum']  = self::fa($payment['sum'] -$doc->headerdata['exchange'] );  
                    }                     
                }
                $check["pays"][] = $payment;

            }
        } else {
            if($doc->headerdata['mfnal']  >0 && $doc->headerdata['payed'] > 0) {
                $payment=array(
                "type"=>0,
                "sum"=> self::fa($doc->headerdata['payed'] )
                );
                if($doc->headerdata['exchange'] > 0) {
                    $payment['change']  = self::fa($doc->headerdata['exchange'] );  
                    $payment['sum']  = self::fa($payment['sum'] -$doc->headerdata['exchange'] );  
                }  

                              
                $check["pays"][] = $payment;
            }
            if($doc->headerdata['mfbeznal']  >0 && $doc->headerdata['payedcard'] > 0) {
                $payment=array(
                "type"=>2,
                "sum"=>self::fa($doc->headerdata['payedcard'] ) 
                );
                $check["pays"][] = $payment;
            }

        }
        $payed  =    doubleval($doc->headerdata['payed'] ??0) + doubleval($doc->headerdata['payedcard']??0);

        if ($payed < $doc->payamount) {
            $payment=array(
            "type"=>4,
            "sum"=> self::fa($doc->payamount - $payed ) 
            );
            $check["pays"][] = $payment;

        }


        if($doc->headerdata["prepaid"]??0 >0) {
            $payment=array(
            "type"=>3,
            "sum"=>self::fa( $doc->headerdata["prepaid"]  )  
            );
            $check["pays"][] = $payment;
        }


        $req=array('fiscal'=>array('task'=>$doc->meta_name=="ReturnIssue" ?2:1,
                 'cashier'=>self::getCashier($doc),
                 'receipt'=>$check));
        
        
        $receipt=json_encode($req, JSON_UNESCAPED_UNICODE);
        H::log($receipt);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER =>false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $receipt,
            CURLOPT_HTTPHEADER => [
                 "Authorization: {$this->access_token}" 
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);


        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if($status_code != 200) {
           return "HTTP Error #:" . $status_code. ' ' . $response;
        }


        $response = json_decode($response, true);
    

        if(strlen($response['errortxt'])>0) {
            return $response['errortxt'];
        }
        if($response['res']>0) {
            return "Помилка ".$response['res'];
        }
  

        $ret =[];

        $ret["fiscnumber"] = $response['info']['doccode'];
      //  $ret["qr"] = $response['info']['qr'];
      

        return $ret;

    }

    public function Payment($doc, $payed, $mf) {
    

        $check = [] ;
        $check["goods"] = [] ;
        $check["payments"] = [] ;
        $check["discounts"] = [] ;

        $sum = 0;

        $payed =  doubleval($payed) ;


        $good=[];

        $g=[];
        $g['name'] = $doc->document_number;
        $g['price'] = $payed ;
        $g['code'] = $doc->document_id;

        $good["good"] = $g ;

        $good["quantity"] =   1000 ;
        //    $good["sum"] =1000000;
        $good["is_return"] = false;

        $sum +=   ($g['price'] * $good["quantity"]);

        $check["goods"][] = $good;



        $check['total_sum'] = $sum  ;

        $disc =  $sum - $doc->payamount;
        if($disc > 0) {
            if($doc->headerdata['bonus'] >0) {
                $check["discounts"][] = array("type"=>"DISCOUNT","name"=> "Бонуси ". $doc->headerdata['bonus'] ." грн", "value"=> $doc->headerdata['bonus'],  "mode"=> "VALUE");
                $disc  = $disc -  $doc->headerdata['bonus'] ;
            }
            if($disc >0) {
                $check["discounts"][] = array("type"=>"DISCOUNT","name"=> "Знижка ". $disc ." грн", "value"=> $disc,  "mode"=> "VALUE");
            }


        }

        // $check['total_payment'] = $doc->payamount*100;
        //   $check['total_rest'] = 0 ;

        if ($mf == 0 && $payed > 0) {
            $payment=array("type"=>"CASH","label"=>"Готівка","value"=>$payed*100);
            $check["payments"][] = $payment;

        }
        if ($mf > 0 && $payed > 0) {
            $mf = \App\Entity\MoneyFund::load($mf);
            if ($mf->beznal == 1) {
                $payment=array("type"=>"CASHLESS","label"=>"Банківська карта","value"=>$payed*100);
            } else {
                $payment=array("type"=>"CASH","label"=>"Готівка","value"=>$payed*100);
            }
            $check["payments"][] = $payment;

        }


        $receipt =  json_encode($check, JSON_UNESCAPED_UNICODE);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL."/receipts/sell",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER =>false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $receipt,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->access_token}",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);



        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status_code !== 201) {
            if($status_code == 422 || $status_code == 400) {
                $response = json_decode($response, true);
                return $response['message'] ;
            }
            return "HTTP Error #:" . $status_code. ' ' . $response;
        }

        $response = json_decode($response, true);

        $ret =[];

        $ret["checkid"] = $response['id'];


        $receipt = $this->GetRawReceipt($ret["checkid"]);
        $response = json_decode($receipt, true);

        $ret["fiscnumber"] = $response['fiscal_code']  ;
        $ret["tax_url"] = $response['tax_url']  ;


        return $ret;

    }


    

   public function CheckShift() {

        $req=array('fiscal'=>array('task'=>18)) ;
        
        $body=json_encode($req, JSON_UNESCAPED_UNICODE);
   
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL ,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body ,           
            CURLOPT_SSL_VERIFYPEER =>false,

            CURLOPT_HTTPHEADER => [
                "Authorization: {$this->access_token}" 
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);


        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if($status_code != 200) {
           return "HTTP Error #:" . $status_code. ' ' . $response;
        }


        $response = json_decode($response, true);
        if($response['res_action']==0) {
            return true;
        } 

        if(strlen($response['errortxt'])>0) {
            return $response['errortxt'];
        }
        if($response['res']>0) {
            return "Помилка ".$response['res'];
        }
  
        return true;

        return $response['info']['shift_status']==1;

    }

    
    
  /**
    * автоматическое  закрытие  смены
    * 
    * @param mixed $posid
    */
    public static function autoshift($posid ) {
     
        
        
        $pos = \App\Entity\Pos::load($posid);
        $firm = \App\Entity\Firm::load($pos->firm_id);
     
       
        $vk = new VK($pos->vktoken) ;
        
        if($vk->CheckShift() != true) {
            return true;
        }
        
        if(true !== $vk->CloseShift()  ){
           return false;    
        }
        
        return true;     

       
   }    
   
   
    //форматиование  сум
    private static function fa($value) {
       return   floatval( number_format($value, 2, '.', '') ) ;  ;
    }
    //форматиование количеств
    private static function fqty($value) {
       return   floatval( number_format($value, 3, '.', '') ) ;  ;
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
    
    
        
}
/*
curl --location 'https://kasa.vchasno.ua/api/v3/fiscal/execute' \
--header 'Authorization: {{token_vhasno}}' \
--data '{
    "fiscal": {
        "task": 18
    }
}'

9999992475406556
    TEST_t8Ue-3-BVOytQQ
    
    JRvbIyE8ri0CfbsHyUDx7RggQilRIWNz_8bSr1RL4_R1H33GkGWlQY9VhvgAoKNj
    
https://documenter.getpostman.com/view/26351974/2s93shy9To    
https://kasa.vchasno.ua/app/shops/0f77aeb2-8790-52c6-ed35-ffd399b9a15b/registers    




{
  "fiscal": {
    "task": 1,
    "cashier": "Сидоров",
    "receipt": {
      "rows": [
        {
          "name": "Роба",
          "price": 1150.00,
          "code": "12",
          "cnt": 1 ,
              "taxgrp": "4"
        }
      ],
      "pays": [
        {
          "type": 0,
          "sum": 1150.00
        }
      ],
      "sum": 1150.00
    }
  }
}

 

{
    
    "fiscal": {
        "task": 1,
        "cashier": "Постман",
        "receipt": {
            "sum": 1445.29,
            "round": 0.01,
            "comment_up": "Зразок! Комментар шапки чеку",
            "comment_down": "ДЯКУЄМО за покупку",
            "rows": [
                {
                    "code": "Dm-123%1",
                    "code1": "79545322",
                    "code2": "45632",
                    "name": "Dm-123%1 Продукт1тест тест тест назва на 2 рядки",
                    "cnt": 2,
                    "price": 20.10,
                    "disc": -0.10,
                    "taxgrp": "4"

                },
                {
                    "code": "424311" ,
                    
                    "name": "Продукт 2",
                    "cnt": 1,
                    "price": 1410.04,
                    "disc": 5.05,
                    "taxgrp": 3
                    
                }
            ],
            "pays": [
                {
                    "type": 0,
                    "sum": 1445.30 
                   
                } 
            ]
        }
    }
}

{
  "task": 1,
  "type": 1,
  "ver": 6,
  "source": "POSTMAN",
  "device": "99999999955555555",
  "tag": "12d447fdb722ec2b044f95d7ef4ad44e",
  "dt": "20230918143856",
  "res": 0,
  "res_action": 0,
  "errortxt": "",
  "warnings": [],
  "info": {
    "task": 1,
    "fisid": "99999999955555555",
    "dataid": 64,
    "doccode": "TEST_NxD0959v08gYgg",
    "dt": "20230918173855",
    "cashier": "Постман",
    "dtype": 0,
    "isprint": 0,
    "isoffline": false,
    "safe": 4320.6,
    "shift_link": 13,
    "docno": 3,
    "cancelid": "TEST_NxD0959v08gYgg",
    "qr": "87b5b248c5cd9a8746c543fd3743961a5f38d158e23dc42a4a96179c52579114\n18.09.2023 17:38:55\n#TEST_NxD0959v08gYgg\n1445.29\n#99999999955555555"
  }
}    



curl --location 'https://kasa.vchasno.ua/api/v3/fiscal/execute' \
--header 'Authorization: <token>' \
--data '{
    "source": "POSTMAN",
    "fiscal": {
        "cashier": "Постман",
        "task": 2,
        "receipt": {
            "sum": 729.94,
            "round": -0.04,
            "comment_up": "Коментар верху чеку ~:?*()_-+=",
            "comment_down": "ДЯКУЄМО-ЗА-ПОКУПКУ",
            "rows": [
                {
                    "code": "Dm-124%",
                    "code1": "1111111",
                    "code2": "",
                    "name": "\"Повернення\" продукт 1",
                    "cnt": 2,
                    "price": 360.05,
                    "disc": 20.00,
                    "taxgrp": "1",
                    "comment": "тестТест"
                },
                {
                    "code": "4244",
                    "code1": "",
                    "code_a": "AA123325435",
                    "code_aa": [
                        "AA123325435"
                    ],
                    "code2": "45662",
                    "name": "Пиво л",
                    "cnt": 0.35,
                    "price": 85.26,
                    "disc": 0.00,
                    "taxgrp": 2,
                    "comment": "***КОРОНА***"
                }
            ],
            "pays": [
                {
                    "type": 2,
                    "sum": 729,
                    "commission": 1,
                    "paysys": "MasterCard",
                    "rrn": "123",
                    "oper_type": "Повернення",
                    "cardmask": "122345******1111",
                    "term_id": "",
                    "bank_id": "",
                    "auth_code": "",
                    "show_additional_info": true
                },
                {
                    "type": 0,
                    "sum": 0.9
                }
            ]
        }
    }
}'
*/