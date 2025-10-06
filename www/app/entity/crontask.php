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
    public const MIN_INTERVAL=300 ;
    public const TYPE_SUBSEMAIL='subsemail' ;
    public const TYPE_EVENTCUST='eventcust' ;
    public const TYPE_AUTOSHIFT='autoshift' ;
    
    
    protected function init() {

        $this->id = 0;
        $this->created = time();
        $this->starton = time();
        $this->tasktype = "";

    }


    protected function afterLoad() {

        $this->created = strtotime($this->created);
        $this->starton = strtotime($this->starton);


        parent::afterLoad();
    }

    public static function do(): void {
        global $logger;

        if(!System::useCron()) {
            return;
        }
        
 
        $options = System::getOptions('common');
        $modules = System::getOptions('modules');
       
        
        $last = intval( \App\Helper::getKeyVal('lastcron') );
        if((time()-$last) < self::MIN_INTERVAL) { //не  чаще  раза в пять минут
            return;
        }
        $start = \App\Helper::getKeyVal('lastcron')  ?? 0 ;
        $stop = \App\Helper::getKeyVal('stopcron')  ?? '' ;
        if($start >0 &&  $stop=== 'false') { //уже  запущен
            return;
        }
        \App\Helper::setKeyVal('lastcron', time()) ;
        \App\Helper::setKeyVal('stopcron', 'false') ;

        try {
            $conn = \ZDB\DB::getConnect()  ;

            //задачи    при  каждом  вызове

            self::doQueue();

            //задачи  раз  в  час
            $last =  intval(\App\Helper::getKeyVal('lastcronh'));
            if((time() - $last) > 3600) {
                \App\Helper::setKeyVal('lastcronh', time()) ;

            }
       
            //задачи в  конце дня
            $last =   \App\Helper::getKeyVal('lastcronhed');
            $endday = strtotime( date('Y-m-d 23:00') );
            if($last !==  date('Ymd') && time() > $endday  ) {
                \App\Helper::setKeyVal('lastcronhed',date('Ymd')) ;
                \App\Entity\Subscribe::onEndDay();
            }

            //задачи  раз  в  сутки
            $last =  intval(\App\Helper::getKeyVal('lastcrond'));
            if(date('Y-m-d') != date('Y-m-d', $last)) {
                \App\Helper::setKeyVal('lastcrond', time()) ;

                //очищаем  уведомления
                $dt = $conn->DBDate(strtotime('-1 month', time())) ;
                $conn->Execute("delete  from notifies  where  dateshow < ". $dt) ;
                  
                
                //очистка товаров у поставщика
                $days = $options['ci_clean'] ?? 0;
                if($days >0) {
                    $conn->Execute("delete from custitems where  updatedon <  ". $conn->DBDate( strtotime("-{$days} day"))  ) ;
                    $conn->Execute("optimize table custitems ")   ;
              
                }
             ;
                
            }
            
            //задачи  раз  в месяц
            $last =  intval(\App\Helper::getKeyVal('lastcronm'));
            if(date('m') != date('m', $last)) {
                \App\Helper::setKeyVal('lastcronm', time()) ;

                //очищаем статистику
                $dt = $conn->DBDate(strtotime('-12 month', time())) ;
                $conn->Execute("delete  from stats  where category not in   (4) and  dt < ". $dt) ;
                $conn->Execute(" OPTIMIZE TABLE stats  " ) ;
                $conn->Execute("optimize table substitems ")   ;
              //  $conn->Execute(" OPTIMIZE TABLE store_stock  " ) ;
              
        
                
            }

            
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
        \App\Helper::setKeyVal('stopcron', 'true') ;


    }

    public static function doQueue($task_id=0) {
        global $logger;
     
        $ret="";
        $conn=\ZDB\DB::getConnect() ;

        $where =" starton <= NOW() "  ;
        if($task_id >0 ) {
            $where = " id = ". $task_id    ;        
        }
        
        $queue = CronTask::findYield($where , "id asc" ) ;
        foreach($queue as $task) {
            try {
                $done = false;
                if($task->tasktype==self::TYPE_SUBSEMAIL) {
                    $msg =unserialize($task->taskdata);

                    $ret = \App\Entity\Subscribe::sendEmail($msg['email'], $msg['text'], $msg['subject'], $msg['document_id'] > 0 ? \App\Entity\Doc\Document::load($msg['document_id']) : null);
                    if(strlen($ret)==0) {
                        $done = true;
                    }

                }

                if($task->tasktype==self::TYPE_EVENTCUST) {
                    $data =unserialize($task->taskdata);
                    $text = $data['text']  ;
                    $user = \App\Entity\User::load($data['user_id']);

                    if(strlen($user->chat_id) >0) {
                        $ret= \App\Entity\Subscribe::sendBot($user->chat_id, $text) ;
                    } elseif(strlen($user->email) >0  ) {
                        $ret= \App\Entity\Subscribe::sendEmail($user->email, $text, "ZStore  notify") ;
                    }
                    if(strlen($ret)==0) {
                        $done = true;
                    }

                }
             
                if($task->tasktype==self::TYPE_AUTOSHIFT) {
                    $msg = unserialize($task->taskdata);

                    $b=false;  
                    if($msg['type']=='ppro') {
                       $b=  \App\Modules\PPO\PPOHelper::autoshift($msg['pos_id'])  ;
                    }
                    if($msg['type']=='cb') {
                       $b=  \App\Modules\CB\CheckBox::autoshift($msg['pos_id']) ;
                    }
                    if(!$b) {
                        $admin = \App\Entity\User::getByLogin('admin');

                        $n = new  Notify();
                        $n->user_id =  $admin->user_id;
                        $n->sender_id =  Notify::SYSTEM;

                        $n->message = "Помилка  автоматичного закриття змiни";
                        $n->save();          
                    }
                    
                    $done = true;
                }


                if($done) {
                   CronTask::delete($task->id) ;
                }   
            } catch(\Exception $e) {
                $msg = $e->getMessage();
                $logger->error($msg);
                $task->starton +=  (12 *3600) ;
                $task->save() ;//откладываем
                
            }    

        }
         
    }
    public static function getTypes() {
        $ret=[];
        $ret[self::TYPE_SUBSEMAIL]  = 'Email по  підписці  ';
        $ret[self::TYPE_EVENTCUST]  = 'Подія з контрагентом ';
        $ret[self::TYPE_AUTOSHIFT]  = 'Автозакриття зміни ПРРО';
            
            
        return $ret;
    }

}
