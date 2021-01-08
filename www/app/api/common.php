<?php
  
namespace App\API; 

class Common extends  JsonRPC
{
    
    public function Login($user,$password) {
          
       
          $api = \App\System::getOptions('api') ;
          
          $user = \App\Helper::login($user,$password) ;
          
          if($user instanceof \App\Entity\User ) {
                    $key = strlen($api['key']) > 0 ?  $api['key'] : "defkey" ;
                    $exp = strlen($api['exp']) > 0 ?  $api['exp'] : 60 ;
                    
                    $token = array(
                        "user_id" => $user->user_id,
                        "iat" => time()  ,
                        "exp" => time()  + $exp*60  
                    );
                     
                    $jwt = \Firebase\JWT\JWT::encode($token, $key);  
   
                   
            }   else {
                throw new  \Exception('Неверный логин',1000);
            }       
        
            return  $jwt ;
        
    }
    public function Info(  ) {
         
       if(!$this->checkAcess())return;
       $user = \App\System::getUser();
       
       
        
    }

    
}