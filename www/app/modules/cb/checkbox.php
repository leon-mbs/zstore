<?php
  
namespace App\Modules\CB;

/**
* Хелперный класс для  CheckBox 
*/
class CheckBox
{

    protected string $access_token;
    protected string $license_key;    //test16741de6daf9c3ec07b18743
    protected string $pin_code;      //2591384368
    protected const API_URL = 'https://api.checkbox.ua/api/v1';

    public function __construct($license_key,$pin_code) {
       $this->license_key = $license_key;
       $this->pin_code = $pin_code;
 
    }

    public function PinCodeAuth( ) 
    {
     
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
            
            if($status_code == 403 || $status_code == 422){
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
   
    
    public function OpenShift(){
        
        $ret = $this->PinCodeAuth() ;
        if($ret !== true){
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
            
           if( $status_code == 422 || $status_code == 400 ){
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

       
     
}