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
        $ret = $this->PinCodeAuth() ;
        if($ret !== true) {
            return $ret;
        }

        $check = [] ;
        $check["goods"] = [] ;
        $check["payments"] = [] ;
        $check["discounts"] = [] ;

        $sum = 0;

        if($doc->meta_name=="ReturnIssue") {

            if($doc->parent_id >0) {
                $p = \App\Entity\Doc\Document::load($doc->parent_id) ;
                if(strlen($p->headerdata['checkbox']) > 0) {
                    $check["related_receipt_id"] = $p->headerdata['checkbox'];
                }

            }


        }



        foreach($doc->unpackDetails('detaildata') as $item) {
            $good=[];

            $g=[];
            $g['name'] = $item->itemname;
            $g['price'] =$item->price*100;
            $g['code'] = $item->item_id;

            $good["good"] = $g ;

            $good["quantity"] = $item->quantity * 1000 ;
            //    $good["sum"] =1000000;
            $good["is_return"] = $doc->meta_name=="ReturnIssue";

            $sum +=  round($g['price'] * $item->quantity);

            $check["goods"][] = $good;


        }

        foreach($doc->unpackDetails('services') as $item) {
            $good=[];

            $g=[];
            $g['name'] = $item->service_name;
            $g['price'] =$item->price*100;
            $g['code'] = $item->service_id;

            $good["good"] = $g ;

            $good["quantity"] = $item->quantity * 1000 ;
            //    $good["sum"] =1000000;
            $good["is_return"] = false;

            $sum +=  round(['price'] * $item->quantity);

            $check["goods"][] = $good;


        }


        $check['total_sum'] = $sum  ;

        $disc =  $sum - $doc->payamount*100 - doubleval($doc->headerdata["prepaid"]) * 100 ;
        if($disc > 0) {
            if($doc->headerdata['bonus'] >0) {
                $check["discounts"][] = array("type"=>"DISCOUNT","name"=> "Бонуси ". $doc->headerdata['bonus'] ." грн", "value"=> $doc->headerdata['bonus']*100,  "mode"=> "VALUE");
                $disc  = $disc -  $doc->headerdata['bonus'] ;
            }
            if($disc >0) {
                $check["discounts"][] = array("type"=>"DISCOUNT","name"=> "Знижка ". $disc/100 ." грн", "value"=> $disc,  "mode"=> "VALUE");
            }
        }

        if($doc->headerdata["prepaid"] >0) {
            //   $check["discounts"][] = array("type"=>"EXTRA_CHARGE","name"=> "Передплата ". $doc->headerdata['prepaid'] ." грн", "value"=> $doc->headerdata['prepaid']*100,  "mode"=> "VALUE");
        }



        // $check['total_payment'] = $doc->payamount*100;
        //   $check['total_rest'] = 0 ;

        if($this->headerdata['payment']  >0) {


            $payed =  doubleval($doc->payed) ;
            if ($doc->headerdata['payment'] == 0 && $payed > 0) {
                $payment=array("type"=>"CASH","label"=>"Готівка","value"=>$payed*100);
                $check["payments"][] = $payment;

            }
            if ($doc->headerdata['payment'] > 0 && $doc->payed > 0) {
                $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);
                if ($mf->beznal == 1) {
                    $payment=array("type"=>"CASHLESS","label"=>"Банківська карта","value"=>$payed*100);
                } else {
                    $payment=array("type"=>"CASH","label"=>"Готівка","value"=>$payed*100);
                }
                $check["payments"][] = $payment;

            }
        } else {
            if($doc->headerdata['mfnal']  >0 && $doc->headerdata['payed'] > 0) {
                $payment=array("type"=>"CASH","label"=>"Готівка","value"=>$doc->headerdata['payed'] * 100);
                $check["payments"][] = $payment;
            }
            if($doc->headerdata['mfbeznal']  >0 && $doc->headerdata['payedcard'] > 0) {
                $payment=array("type"=>"CASHLESS","label"=>"Банківська карта","value"=>$doc->headerdata['payedcard'] * 100);
                $check["payments"][] = $payment;
            }

        }
        $payed  =    doubleval($doc->headerdata['payed']) + doubleval($doc->headerdata['payedcard']);

        if ($payed < $doc->payamount) {

            $payment=array("type"=>"CASH","label"=>"Кредит","value"=> ($doc->payamount - $payed) * 100);
            $check["payments"][] = $payment;

        }


        if($doc->headerdata["prepaid"] >0) {
            $payment=array("type"=>"CASH","label"=>"Передплата","value"=> $doc->headerdata["prepaid"] * 100);
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

    public function Payment($doc, $payed, $mf) {
        $ret = $this->PinCodeAuth() ;
        if($ret !== true) {
            return $ret;
        }

        $check = [] ;
        $check["goods"] = [] ;
        $check["payments"] = [] ;
        $check["discounts"] = [] ;

        $sum = 0;

        $payed =  doubleval($payed) ;


        $good=[];

        $g=[];
        $g['name'] = $doc->document_number;
        $g['price'] = $payed*100;
        $g['code'] = $doc->document_id;

        $good["good"] = $g ;

        $good["quantity"] =   1000 ;
        //    $good["sum"] =1000000;
        $good["is_return"] = false;

        $sum +=  round($g['price'] * $good["quantity"]);

        $check["goods"][] = $good;



        $check['total_sum'] = $sum  ;

        $disc =  $sum - $doc->payamount*100;
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
    
*/