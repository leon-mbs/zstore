<?php

namespace App;

use App\Entity\User;
use App\Entity\Customer;
use App\Helper  as H;

/**
* класс для  работы  с  телеграм  ботом
*/
class ChatBot
{
    private $token;

    public function __construct($token) {

        $this->token = $token;

    }

    public function doGet($command, $p=null) {

        $url ="https://api.telegram.org/bot".$this->token."/".$command;

        if(is_array($p)) {
            $url .= "?". http_build_query($p)  ;
        }


        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $resultQuery = curl_exec($ch);
        curl_close($ch);

        $res =  json_decode($resultQuery, true);

        if($res['ok'] != true) {
           \App\Helper::log($url);
           \App\Helper::log($resultQuery);
      
        }        
        return $res;
    }

    public function getUpdates() {
        $ret= $this->doGet('getUpdates') ;
        if($ret['ok'] !== true) {
            return array();
        }

        $u = array_pop($ret['result'])  ;
        $this->onMessage($u['message'])  ;


    }

    public function onHook() {
        global  $logger;

        $request = file_get_contents('php://input');

        $ret = json_decode($request, true)   ;

        // $logger->info($request);
        if(false === $this->onMessage($ret['message'])) {
            $logger->error("bot answer " . $request);
        }



    }

    public function onMessage($msg) {
        global  $logger;

        if(!is_array($msg)) {
            $logger->error("bot message" . $msg);
            return  false;
        }



        $text = $msg['text'] ;
        $chat_id = $msg['chat']['id'] ;
        if(strpos($text, "/help")===0) {
            $this->sendMessage($chat_id, "Перелік команд:\nlogin логін пароль - вхід для користувача\nlogin телефон(". H::PhoneL()." цифр) пароль - вхід для контрагента\nlogout - вихід") ;

        }
        if(strpos($text, "login") === 0) {
            $s = \App\Util::strtoarray($text) ;
            if(count($s) != 3) {
                $this->sendMessage($chat_id, "Login fail") ;
                return;
            }
            $user = H::login($s[1], $s[2]);

            if ($user instanceof User) {
                $user->chat_id = $chat_id;
                $user->save();
                $this->sendMessage($chat_id, "Login OK") ;
                return;
            }

            $c = \App\Entity\Customer::getByPhone($s[1]);
            if ($c = null) {
                $this->sendMessage($chat_id, "Login fail") ;
                return;
            }

            if ($c->passw != $s[2]) {
                $this->sendMessage($chat_id, "Login fail") ;
                return;
            }

            if ($c instanceof \App\Entity\Customer) {
                $c->chat_id = $chat_id;
                $c->save();
                $this->sendMessage($chat_id, "Login OK") ;
                return;
            }


        }
        if(strpos($text, "logout")===0) {
            $u = $this->getUser($chat_id)  ;
            if($u != null) {
                $u->chat_id = '';
                $u->save();
                $this->sendMessage($chat_id, "Logout OK") ;
            } else {
                $this->sendMessage($chat_id, "Ви не залогінені") ;
            }

        }


        if(strlen($msg['caption'] >0)) {
            $text = $msg['caption'] ;  //коментарий к  файлу
        }

        if(is_array($msg['document'])) {
            $filename=$msg['document']['file_name'] ;
            $mimetype=$msg['document']['mime_type'] ;
            $size=$msg['document']['file_size'] ;
            $file_id=$msg['document']['file_id'] ;

            $ret = $this->doGet('getFile?file_id='.$file_id);
            if($ret['ok'] === true) {
                $path= $ret['result']['file_path'] ;

                $url="https://api.telegram.org/file/bot".$this->token."/". $path;


                // $f = file_get_contents($url) ;


            } else {
                H::log($ret['description']) ;
            }

        }

        return  true;

    }

    public function sendMessage($chat_id, $msg,$html=true) {
        $p=array('chat_id'=>$chat_id,'text'=>$msg)  ;
        if($html) {
            $p['parse_mode']  =  'HTML';   
        }
        
        $this->doGet('sendMessage',$p ) ;
    }
    public function sendDocument($chat_id, $filepath, $filename, $mime='application/pdf', $caption='') {

        $arrayQuery = array(
            'chat_id' => $chat_id,
            'document' => curl_file_create($filepath, $mime, $filename)
        );
        if(strlen($caption)>0) {
            $arrayQuery['caption'] = $caption;
        }


        $ch = curl_init('https://api.telegram.org/bot'. $this->token .'/sendDocument');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayQuery);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $resultQuery = curl_exec($ch);
        curl_close($ch);

        return json_decode($resultQuery, true);
    }

    private function getUser($chat_id) {
        foreach(User::find("disabled <> 1")  as $user) {
            if($user->chat_id == $chat_id) {
                return $user;
            }
        }

        $customer = \App\Entity\Customer::getFirst("detail like '%<chat_id>{$chat_id}</chat_id>%'") ;
        if($customer instanceof \App\Entity\Customer) {
            return $customer;
        }

        return null;

    }


}
