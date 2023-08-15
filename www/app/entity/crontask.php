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

    protected function beforeSave() {
        parent::beforeSave();
        $this->details = "<details>";
        $this->details .= "<type>{$this->type}</type>";
        $this->details .= "<data><![CDATA[". serialize($this->data ?? [])  ."]]></data>";
        $this->details .= "</details>";

        return true;
    }
    
    protected function afterLoad() {

        $this->created = strtotime($this->created);

        $xml = @simplexml_load_string($this->details);
  
        $this->data = unserialize(  (string)($xml->data[0]) );
        $this->type = (int)($xml->type[0]);

        parent::afterLoad();
    }     
    
    public  static  function do():void{
        global $logger;

        \App\Helper::setVal('lastcron',time()) ;       

        try{
            
            //задачи каждый  при  каждом  вызове
            
            $queue = CronTask::find("","id asc",100) ;
            foreach($queue as $task) {
                
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
