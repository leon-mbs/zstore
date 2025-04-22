<?php
 
/**
* Пример  клиента 
* $client = new ApiClient("http://local.zstore/",2) ;
* $client->login('admin','admin');
* $client->call('common','parealist');  
*/
  
class ApiClient  
{
   private $url="",$port=80,$usessl=false,$auth=0;
   private $token="";
 
   /**
   * 
   * 
   * @param mixed $url   адрес сайта
   * @param mixed $auth   тип  авторизации 0-не требуется,1-Basic,2-JWT токен
   * @param mixed $usessl   использовать  проверку  https
   */
   public function __construct($url,$auth=0,$usessl = false) {
       $this->auth = $auth ;
       $this->usessl = $usessl ;
       
       $this->url = rtrim($url,'/') ;
       $a=explode(':',$this->url);
    
       if(count($a)>2) {
         
           $this->url = $a[0].':'. $a[1];
           $this->port = $a[2];
               
       }
         
   } 
   
   /**
   * авторизация  
   * 
   * @param mixed $login
   * @param mixed $password
   * @return mixed
   */
   public function login($login,$password){
      if($this->auth==1) {
          $this->token =  'Basic ' . base64_encode($login . ':' . $password)  ;
      }
      if($this->auth==2) {
          
          $res = $this->call('common','token',['login'=>$login,'password'=>$password]);
          if($res['error'] !='')  {
              return $res['error'];
          }
          $this->token =  'Bearer ' . $res['data'] ;
      }
      
      return "OK";
   }
   
   /**
   * вызов  метода  API
   * 
   * @param mixed $endpoint   точка  входа
   * @param mixed $method     метож
   * @param mixed $params     массив  параметров (если  требуется)
   * Возвращает  массив с данными или ошибкой  ['error'=>'','data'=[]]
   */
   public function call($endpoint,$method, $params=[]){
        $headers=[];
        $headers[] = "Content-Type: application/json";
        if($this->token != '') {
            $headers[] ="Authorization: {$this->token}";            
        }

    
        $reuest=['jsonrpc'=>'2.0','id'=>1,'method'=>$method]  ;
        if(count($params)>0) {
            $reuest['params']= $params;
        }
        $json=json_encode($reuest, JSON_UNESCAPED_UNICODE);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
                    CURLOPT_PORT           => $this->port,
                    CURLOPT_URL            => $this->url.'/api/'.$endpoint ,
                    CURLOPT_POST           => true,
                    CURLOPT_ENCODING       => "",
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 20,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_SSL_VERIFYPEER => $this->usessl  ,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_POSTFIELDS     => $json
                ]);
            
        //execute post
        $result = curl_exec($ch);
        
        if (curl_errno($ch) > 0) {
           $error = curl_error($ch);
           return ['error'=>$error,'data'=>[]];              
        }        
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($status_code >= 300) {
            return ['error'=>'http code '. $status_code,'data'=>[]];   
        }
        curl_close($ch);        
        
        $data = json_decode($result, true);
        if(isset($data['error'])  ) {
            return ['error'=>$data['error']['message'],'data'=>[]];   
        }

        return ['error'=>'','data'=>$data['result']];       
   }
   
   
}
