<?php
namespace App;
  
class CronTask
{
    public  static  function do():void{
        global $logger;
        $logger->info('Start cron');
        try{
            //задачи  раз  в  час
            $last =  intval(\App\Helper::getVal('lastcronh') );
            if( (time() - $last) < 4000){
                return;
            } ;
            \App\Helper::setVal('lastcronh',time()) ;       

            //задачи  раз  в  сутки           
            $last =  intval(\App\Helper::getVal('lastcrond') );
            if( ( time() - $last) < (3600 * 24)){
                return;
            } ;
            \App\Helper::setVal('lastcrond',time()) ;       
            
        } catch(\Exception $ee) {
            $msg = $e->getMessage();
            $logger->error($e);
           
        }
        
        
        
    }
}
