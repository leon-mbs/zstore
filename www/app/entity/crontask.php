<?php
namespace App\Entity;

/**
 * Класc-сущность   задача  в  очереди  планироващика
 *
 * @table=crontask
 * @keyfield=id
 */
  
class CronTask  extends \ZCL\DB\Entity
{
    
    
    protected function init() {

        $this->id = 0;
        $this->created = time();

    }

 
    protected function afterLoad() {

        $this->created = strtotime($this->created);
 

        parent::afterLoad();
    }     
    
    public  static  function do():void{
        global $logger;

        \App\Helper::setVal('lastcron',time()) ;       

        try{
            
            //задачи каждый  при  каждом  вызове
            
            $queue = CronTask::find("","id asc",100) ;
            foreach($queue as $task) {
               $done = false;
               if($task->tasktype=='subsemail')  {
                   $msg =unserialize( $task->taskdata);
                   
                   $ret = \App\Entity\Subscribe::sendEmail($msg['email'], $msg['text'],$msg['subject'] , $msg['document_id'] > 0 ? \App\Entity\Doc\Document::load($msg['document_id'])   : null );                    
                   if(strlen($ret)==0) {
                       $done = true;
                   }
                   
               }
               
               if($done) {
                  CronTask::delete($task->id) ;    
               }
               
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
    
    public  static function getTypes(){
        $ret=[];
        $ret['subsemail']  = 'Email по  подписке  ';
        
        return $ret;
    }
    
}
