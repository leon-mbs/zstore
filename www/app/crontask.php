<?php
namespace App;
  
class CronTask
{
    public  static  function do():void{
        global $logger;

        \App\Helper::setVal('lastcron',time()) ;       

        try{
            
            //задачи каждый  при  каждом  вызове
            
            $queue = \App\Entity\Queue::find("","id asc",100) ;
            foreach($queue as $q) {
                
            }
            
            
            //задачи  раз  в  час
            $last =  intval(\App\Helper::getVal('lastcronh') );
            if( (time() - $last) > 3600){
                \App\Helper::setVal('lastcronh',time()) ;       
                
            } ;


            //задачи  раз  в  сутки           
            $last =  intval(\App\Helper::getVal('lastcrond') );
            if(  date('Y-m-d') != date('Y-m-d',$last) ){
               \App\Helper::setVal('lastcrond',time()) ;       
               
               
               
            } ;

            
        } catch(\Exception $ee) {
            $msg = $ee->getMessage();
            $logger->error($msg);
            
            foreach(\App\Entity\User::find("rolename='admins' ") as $u) {
                $n = new \App\Entity\Notify() ;
                $n->user_id = $u->user_id;
                $n->message = $msg;
                $n->sender_id = \App\Entity\Notify::CRONTAB   ;
                      
                
                $n->save()  ;
                
            }
           
        }
        
        
        
    }
}
