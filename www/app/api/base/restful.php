<?php

namespace App\API\Base;

/**
 * Base class for RESTFul
 */
abstract class RestFul
{
      
  
    protected function JsonAnswer($json) {
      $this->headers();
      header("Content-type: application/json");
        http_response_code(200);
        echo $json;
        die;
    }
   

    protected function TextAnswer($text) {
      $this->headers();
      header("Content-type: text/plain");
        http_response_code(200);
        echo $text;
        die;
    }


    protected function OKAnswer() {
      $this->headers();

      http_response_code(200);
        die;
    }

    protected function FailAnswer($error="") {
      $this->headers(400);
 
        echo $error;
        die;
    }
    
    protected function code401() {
        $this->headers(401);  
 
        die;
    }
    
    protected function code403() {
      $this->headers(403);
 
        die;
    }
    private function headers($code=200){
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
      if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') 
          http_response_code(200);
      else 
         http_response_code($code);
     
        
    }
    protected function checkAcess() {
   
 
            $jwt = "";
            $headers = apache_request_headers();
            foreach ($headers as $header => $value) {

                    
                if ( strtolower($header) == "authorization") {
                    $jwt = str_replace("Bearer ", "", $value);
                    $jwt = trim($jwt);
                    break;
                }
            }

            $key =   "defkey";
            try{
              $decoded = \Firebase\JWT\JWT::decode($jwt, $key, array('HS256'));    
            } catch(\Exception $e) {
              $this->FailAnswer($e->getMessage());
            }
            
            
            if($decoded->user_id >0) {
                
                //$user = \App\Entity\User::load($decoded->user_id);
              //  if($user== null)   $this->code401();
               return $decoded->user_id;   
            }   else {
                $this->code401();     
                
            }
   
    }

    
    protected function parsePost($post){
          if($post==null)  $this->FailAnswer("Must be POST request") ;
          
          $post = json_decode($post)     ;
         
          if($post==null)  $this->FailAnswer("Invalid JSON") ;
            
          return  $post;
          
    }
}
