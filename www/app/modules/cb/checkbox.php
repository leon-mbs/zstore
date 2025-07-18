<?php

namespace App\Modules\CB;

/**
* Хелперный класс для  CheckBox
*/
class CheckBox
{
    protected string $access_token;
    protected string $license_key;    //test16741de6daf9c3ec07b187430
    protected string $pin_code;      //2591384368
    protected const API_URL = 'https://api.checkbox.ua/api/v1';   //https://wiki.checkbox.ua/uk/api/specification

    public function __construct($license_key, $pin_code) {
        $this->license_key = $license_key;
        $this->pin_code = $pin_code;

    }

    public function PinCodeAuth() {
        if(strlen($this->pin_code)==0 || strlen($this->license_key)==0) {
            return "Не задано параметри  для  CheckBox ";
        }
        $body = [
            'pin_code' => $this->pin_code
        ];

        $curl = curl_init();


        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL."/cashier/signinPinCode",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER =>false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-License-Key: {$this->license_key}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);


        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status_code !== 200) {

            if($status_code == 403 || $status_code == 422) {
                $response = json_decode($response, true);
                return $response['message'] ;
            }

            return "HTTP Error #:" . $status_code. ' ' . $response;
        }

        $response = json_decode($response, true);
        $this->access_token = $response['access_token'];


        curl_close($curl);

        return true;
    }


    public function OpenShift() {

        $ret = $this->PinCodeAuth() ;
        if($ret !== true) {
            return $ret;
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL."/shifts",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER =>false,
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->access_token}",
                "X-License-Key: {$this->license_key}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);


        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status_code !== 202) {

            if($status_code == 422 || $status_code == 400) {
                $response = json_decode($response, true);
                return $response['message'] ;
            }


            return "HTTP Error #:" . $status_code. ' ' . $response;
        }

        $response = json_decode($response, true);
        //  $this->shift_id = $response['id'];

        curl_close($curl);
    
         
        return true;

    }


    public function CloseShift() {

        $ret = $this->PinCodeAuth() ;
        if($ret !== true) {
            return $ret;
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL."/shifts/close",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER =>false,
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->access_token}",
                "X-License-Key: {$this->license_key}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);


        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status_code !== 202) {

            if($status_code == 422 || $status_code == 400) {
                $response = json_decode($response, true);
                return $response['message'] ;
            }


            return "HTTP Error #:" . $status_code. ' ' . $response;
        }

        $response = json_decode($response, true);
        //  $this->shift_id = $response['id'];

        curl_close($curl);

        return true;

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

            $sum +=  round($g['price'] * $item->quantity);

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

        if(($doc->headerdata['payment']??0)  >0) {


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
                \App\Helper::logerror($response) ;
                $response = json_decode($response, true);
                return $response['message'] ;
            }
            return "HTTP Error #:" . $status_code. ' ' . $response;
        }

        $response = json_decode($response, true);

        $ret =[];

        $ret["checkid"] = $response['id'];



        $counter=10;
        while (--$counter >0) {
            sleep(1)  ;
            $receipt = $this->GetRawReceipt($ret["checkid"]);
            $response = json_decode($receipt, true);

            $ret["fiscnumber"] = $response['fiscal_code']  ;
            $ret["tax_url"] = $response['tax_url']  ;
            if(strlen($ret["fiscnumber"]) >0) {
                return $ret;  
            }

                
        }
  
        throw new \Exception("Не повернено фіскальний номер");
   

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


        $counter=10;
        while (--$counter >0) {
            sleep(1)  ;
            $receipt = $this->GetRawReceipt($ret["checkid"]);
            $response = json_decode($receipt, true);

            $ret["fiscnumber"] = $response['fiscal_code']  ;
            $ret["tax_url"] = $response['tax_url']  ;
            if(strlen($ret["fiscnumber"]) >0) {
                return $ret;  
            }

                
        }
  
        throw new \Exception("Не повернено фіскальний номер");
    }


    public function GetRawReceipt($receipt_id) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL . "/receipts/{$receipt_id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_SSL_VERIFYPEER =>false,
              CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->access_token}",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);


        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($status_code !== 200) {

            if($status_code == 422 || $status_code == 400) {
                $response = json_decode($response, true);
                return $response['message'] ;
            }

            return "HTTP Error #:" . $status_code . ' ' . $response;
        }
        return $response;
    }


   public function CheckShift() {

        $ret = $this->PinCodeAuth() ;
        if($ret !== true) {
            return $ret;
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::API_URL."/cashier/shift",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER =>false,
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->access_token}",
                "X-License-Key: {$this->license_key}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);


        if ($err) {
            return "cURL Error #:" . $err;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status_code !== 202) {

            if($status_code == 422 || $status_code == 400) {
                $response = json_decode($response, true);
                return $response['message'] ;
            }


            return "HTTP Error #:" . $status_code. ' ' . $response;
        }

        $response = json_decode($response, true);
        //  $this->shift_id = $response['status'];

        curl_close($curl);

        return $response['status']=='OPENED';

    }

    
    
  /**
    * автоматическое  закрытие  смены
    * 
    * @param mixed $posid
    */
    public static function autoshift($posid ) {
     
        
        
        $pos = \App\Entity\Pos::load($posid);
       
       
        $cb = new CheckBox($pos->cbkey, $pos->cbpin) ;
        
        if($cb->CheckShift() != true) {
            return true;
        }
        
        if(true !== $cb->CloseShift()  ){
           return false;    
        }
        
        return true;     

       
   }    
        
}



 