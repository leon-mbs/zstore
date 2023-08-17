<?php

namespace App\Entity;

use App\Helper as H;
use App\System;

/**
 * Класc-сущность   задача  в  очереди  планироващика
 *
 * @table=crontask
 * @keyfield=id
 */

class CronTask extends \ZCL\DB\Entity
{
    public const MIN_INTERVAL=300;
    protected function init() {

        $this->id = 0;
        $this->created = time();
        $this->starton = time();

    }


    protected function afterLoad() {

        $this->created = strtotime($this->created);
        $this->starton = strtotime($this->starton);


        parent::afterLoad();
    }

    public static function do(): void {
        global $logger;

        $cron =H::getKeyVal('cron') ?? false;
        if(!$cron) {
           return;
        }  
        
        $last = \App\Helper::getKeyVal('lastcron')  ?? 0;
        if( (time()-last ) < self::MIN_INTERVAL  ) { //не  чаще  раза в пять минут
       //     return;
        }
        $stop = \App\Helper::getKeyVal('stopcron')  ?? false;
        if($stop== false) { //уже  запущены
            return;
        }
        \App\Helper::setKeyVal('lastcron', time()) ;
        \App\Helper::setKeyVal('stopcron',false) ;        
        
        try {
            $conn = \ZDB\DB::getConnect()  ;
             
            //задачи каждый  при  каждом  вызове

            self::doQueue();

            //задачи  раз  в  час
            $last =  intval(\App\Helper::getKeyVal('lastcronh'));
            if((time() - $last) > 3600) {
                \App\Helper::setKeyVal('lastcronh', time()) ;

            } ;


            //задачи  раз  в  сутки
            $last =  intval(\App\Helper::getKeyVal('lastcrond'));
            if(date('Y-m-d') != date('Y-m-d', $last)) {
                \App\Helper::setKeyVal('lastcrond', time()) ;

                
                //очищаем  уведомления
                $dt = $conn->DBDate( strtotime('-1 month',time())  ) ;
                $conn->Execute("delete  from notifies  where  dateshow < ". $dt) ;


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
        \App\Helper::setKeyVal('stopcron',true) ;

    }

    private  static function doQueue() {
        $ret=""; 
        $conn=\Zdb\DB::getConnect() ;
        
        $queue = CronTask::find(" starton >= NOW() ", "id asc", 100) ;
        foreach($queue as $task) {
            $done = false;
            if($task->tasktype=='subsemail') {
                $msg =unserialize($task->taskdata);

                $ret = \App\Entity\Subscribe::sendEmail($msg['email'], $msg['text'], $msg['subject'], $msg['document_id'] > 0 ? \App\Entity\Doc\Document::load($msg['document_id']) : null);
                if(strlen($ret)==0) {
                    $done = true;
                }

            }

           if($task->tasktype=='eventcust') {
                $data =unserialize($task->taskdata);
                $text = $date['text']  ;
                $user = \App\Entity\User::load($data['user_id']);
                
                if(strlen($u->chat_id) >0){
                  $ret= \App\Entity\Subscribe::sendBot($u->chat_id,$text) ;
                } else
                if(strlen($u->email) >0  && System::useEmail()){
                  $ret= \App\Entity\Subscribe::sendEmail($u->email,$text,"XStore  notify") ;
                }
                if(strlen($ret)==0) {
                    $done = true;
                }                
                
            }

            if($done) {
                CronTask::delete($task->id) ;
            }

        }
    }
    public static function getTypes() {
        $ret=[];
        $ret['subsemail']  = 'Email по  підписці  ';
        $ret['eventcust']  = 'Подія з контрагентом ';

        return $ret;
    }

}
