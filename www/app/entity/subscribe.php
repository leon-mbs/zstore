<?php

namespace App\Entity;

use App\Helper as H;
use App\System;

/**
 * Класс-сущность  подписка  на  событие
 *
 * @table=subscribes
 * @keyfield=sub_id
 */
class Subscribe extends \ZCL\DB\Entity
{
    //типы  событий
    public const EVENT_DOCSTATE = 1;
    public const EVENT_NEWCUST  = 2;
    public const EVENT_ENDDAY   = 3;
    
    //типы сообщений
    public const MSG_NOTIFY = 1;
    public const MSG_EMAIL  = 2;
    public const MSG_SMS    = 3;
    public const MSG_VIBER  = 4;
    public const MSG_BOT    = 5;

    //типы  получателей
    public const RSV_CUSTOMER  = 1;
    public const RSV_DOCAUTHOR = 2;
    public const RSV_USER      = 3;
    public const RSV_WH        = 4;
    public const RSV_SYSTEM    = 5;
    public const RSV_DOCRESP   = 6;
    public const RSV_TG        = 7;
    public const RSV_EMAIL     = 8;

    protected function init() {
        $this->sub_id = 0;
    }

    protected function afterLoad() {

        $xml = @simplexml_load_string($this->detail);

        $this->sub_typename = (string)($xml->sub_typename[0]);
        $this->reciever_typename = (string)($xml->reciever_typename[0]);
        $this->msg_typename = (string)($xml->msg_typename[0]);
        $this->statename = (string)($xml->statename[0]);
        $this->doctypename = (string)($xml->doctypename[0]);
        $this->msgsubject = (string)($xml->msgsubject[0]);
        $this->url = (string)($xml->url[0]);
        $this->chat_id = (string)($xml->chat_id[0]);
        $this->username = (string)($xml->username[0]);
        $this->user_id = (int)($xml->user_id[0]);
        $this->state = (int)($xml->state[0]);
        $this->doctype = (int)($xml->doctype[0]);
        $this->attach = (int)($xml->attach[0]);
        $this->html = (int)($xml->html[0]);

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();

        $this->detail = "<detail>";

        $this->detail .= "<sub_typename>{$this->sub_typename}</sub_typename>";
        $this->detail .= "<reciever_typename>{$this->reciever_typename}</reciever_typename>";
        $this->detail .= "<msg_typename>{$this->msg_typename}</msg_typename>";
        $this->detail .= "<user_id>{$this->user_id}</user_id>";
        $this->detail .= "<state>{$this->state}</state>";
        $this->detail .= "<attach>{$this->attach}</attach>";
        $this->detail .= "<html>{$this->html}</html>";
        $this->detail .= "<doctype>{$this->doctype}</doctype>";
        $this->detail .= "<doctypename>{$this->doctypename}</doctypename>";
        $this->detail .= "<statename>{$this->statename}</statename>";
        $this->detail .= "<username>{$this->username}</username>";
        $this->detail .= "<msgsubject>{$this->msgsubject}</msgsubject>";
        $this->detail .= "<url>{$this->url}</url>";
        $this->detail .= "<chat_id>{$this->chat_id}</chat_id>";

        $this->detail .= "</detail>";

        return true;
    }

    //типы  подписок 
    public static function getEventList() {
        $list = array();
        $list[self::EVENT_DOCSTATE] = "Зміна статусу документа";
        $list[self::EVENT_NEWCUST]  = "Новий контрагент";
        $list[self::EVENT_ENDDAY]   = "Кінець робочого дня";


        return $list;
    }

    //типы  соотбщений по  типу получателя 
    public static function getMsgTypeList($rt=0) {
        $rt = intval($rt);
        if($rt==0)  return [];

        $sms = \App\System::getOptions('sms')  ;

        $list = array();
        $list[self::MSG_NOTIFY] = "Текст";
      
        $list[self::MSG_EMAIL] = "E-mail";

        if($sms['smstype'] > 0) {
            $list[self::MSG_SMS] = "SMS";
        }

        if($sms['smstype']==2) {
            $list[self::MSG_VIBER] =  "Viber";
        }

        if(strlen(\App\System::getOption("common", 'tbtoken'))>0) {
            $list[self::MSG_BOT] = "Телеграм";
        }
      
        
        if($rt==self::RSV_CUSTOMER) {
           unset($list[self::MSG_NOTIFY])  ;
        }
        
        if($rt==self::RSV_WH || $rt==self::RSV_SYSTEM) {
           unset($list[self::MSG_EMAIL])  ;
           unset($list[self::MSG_VIBER])  ;
           unset($list[self::MSG_BOT])  ;
           unset($list[self::MSG_SMS])  ;
        }
     
        if($rt==self::RSV_TG ) {
           unset($list[self::MSG_EMAIL])  ;
           unset($list[self::MSG_VIBER])  ;
 
           unset($list[self::MSG_SMS])  ;
           unset($list[self::MSG_NOTIFY])  ;
        }
     
        if($rt==self::RSV_EMAIL ) {
           unset($list[self::MSG_BOT])  ;
           unset($list[self::MSG_VIBER])  ;
 
           unset($list[self::MSG_SMS])  ;
           unset($list[self::MSG_NOTIFY])  ;
        }
     
    

        return $list;
    }

    
    //типы  получателей по  типу подписок 
    public static function getRecieverList($et=0) {
        $et = intval($et);
        if($et==0)  return [];


        $list = array();
        if($et==self::EVENT_DOCSTATE) {
           $list[self::RSV_DOCAUTHOR] = "Автор документу";
           $list[self::RSV_DOCRESP] = "Відповідальний за документ";
           $list[self::RSV_CUSTOMER] = "Контрагент документу";
        }
        if($et==self::EVENT_NEWCUST) {
           $list[self::RSV_CUSTOMER] = "Контрагент";
        }
        $list[self::RSV_SYSTEM] = "Системний лог";
        $list[self::RSV_USER] = "Користувач системи";
        $list[self::RSV_WH] = "Web Hook";
        $list[self::RSV_EMAIL] = "E-mail";
       
        if(strlen(\App\System::getOption("common", 'tbtoken'))>0) {
            $list[self::RSV_TG] = "Телеграм";
        }
        
         
        return $list;
    }

    //изменение  состояния  документа
    public static function onDocumentState($doc_id, $state) {
        $doc = \App\Entity\Doc\Document::load($doc_id);

        $list = self::find('disabled <> 1 and sub_type= ' . self::EVENT_DOCSTATE);
        foreach ($list as $sub) {
            if ($sub->doctype != $doc->meta_id) {
                continue;
            }
            if ($sub->state != $state) {
                continue;
            }

            $options=[];
            $c=null;
            $u=null;
            
            
            if ($sub->reciever_type == self::RSV_CUSTOMER) {
                $c = \App\Entity\Customer::load($doc->customer_id);
                if($c->nosubs == 1) {
                   $c=null; 
                }
            }
            if ($sub->reciever_type == self::RSV_DOCAUTHOR) {
                $u = \App\Entity\User::load($doc->headerdata['author']);
            }
            if ($sub->reciever_type == self::RSV_DOCRESP) {
                $u = \App\Entity\User::load($doc->user_id);
            }
            if ($sub->reciever_type == self::RSV_USER) {
                $u = \App\Entity\User::load($sub->user_id);
                
                if($doc->branch_id > 0 && $u->rolename != 'admins') {
                    $blist =  explode(',',$u->aclbranch) ; 
                    if(in_array($doc->branch_id,$blist)==false) {
                       continue; 
                    }
                }
                
            }   
         
               
            if ($c != null  ) {
                $options['phone'] = $c->phone;
                $options['viber'] = $c->viber;
                $options['email'] = $c->email;
                $options['chat_id'] = $c->chat_id;
            }
            
            if ($u != null) {
                $options['phone'] = $u->phone;
                $options['viber'] = $u->viber;
                $options['email'] = $u->email;
                $options['chat_id'] = $u->chat_id;
                $options['notifyuser'] = $u->user_id;
            }  
            if ($sub->reciever_type == self::RSV_TG) {
                $options['chat_id'] = $sub->chat_id;;
            }
                      
            $options['doc']  = $doc;
            
            $text = $sub->getTextDoc($doc);
            
            
            $text = $sub->sendmsg($text,$options);
            
            
 

        }
    }

    //Новый контрагент
    public static function onNewCustomer($customer_id) {
        $c = \App\Entity\Customer::load($customer_id);
 
        $list = self::find('disabled <> 1 and sub_type= ' . self::EVENT_NEWCUST);
        foreach ($list as $sub) {
            $options=[];
         
            $u=null;
            
            
            if ($sub->reciever_type == self::RSV_CUSTOMER) {
                if($c->nosubs == 1) {
                   continue;
                }
            }
            if ($sub->reciever_type == self::RSV_USER) {
                $u = \App\Entity\User::load($sub->user_id);
            }   
            
               
            if ($c != null  ) {
                $options['phone'] = $c->phone;
                $options['viber'] = $c->viber;
                $options['email'] = $c->email;
                $options['chat_id'] = $c->chat_id;
            }
            
            if ($u != null) {
                $options['phone'] = $u->phone;
                $options['viber'] = $u->viber;
                $options['email'] = $u->email;
                $options['chat_id'] = $u->chat_id;
                $options['notifyuser'] = $u->user_id;
            }            
//      
            if ($sub->reciever_type == self::RSV_TG) {
                $options['chat_id'] = $sub->chat_id;;
            }
            
            $text = $sub->getTextCust($c);
            
            
            $sub->sendmsg($text,$options);
 
        }
    }

    //конец дня (задается  в  планировщике)
    public static function onEndDay( ) {
        $list = self::find('disabled <> 1 and sub_type= ' . self::EVENT_ENDDAY);
        foreach ($list as $sub) {
            $options=[];
         
            $u=null;
          
            if ($sub->reciever_type == self::RSV_USER) {
                $u = \App\Entity\User::load($sub->user_id);
            }   
    
            if ($u != null) {
                $options['phone'] = $u->phone;
                $options['viber'] = $u->viber;
                $options['email'] = $u->email;
                $options['chat_id'] = $u->chat_id;
                $options['notifyuser'] = $u->user_id;
            }            
//      
            if ($sub->reciever_type == self::RSV_TG) {
                $options['chat_id'] = $sub->chat_id;;
            }
            if ($sub->reciever_type == self::RSV_EMAIL) {
                $options['email'] = $sub->email;;
            }
            
            $text = $sub->getTextEndDay();
            
            
            $sub->sendmsg($text,$options);
            
            
 

        }
  
    }

    
    private    function sendmsg($text, $options=[]){
        $ret='';    
        if ($options['notifyuser'] > 0 && $this->msg_type == self::MSG_NOTIFY) {
                self::sendNotify($options['notifyuser'], $text);
            }
            if (  $this->reciever_type== self::RSV_SYSTEM) {
                self::sendNotify(\App\Entity\Notify::SYSTEM, $text);
            }

            if (strlen($options['phone']) > 0 && $this->msg_type == self::MSG_SMS) {
                $ret =   self::sendSMS($options['phone'], $text);
            }
            if (strlen($options['email']) > 0 && $this->msg_type == self::MSG_EMAIL) {
                // отправляем  в  очередь если  включен  планировщик
                if(System::useCron()) {
                    $task = new  \App\Entity\CronTask();
                    $task->tasktype=\App\Entity\CronTask::TYPE_SUBSEMAIL;
                    $task->taskdata= serialize(array(
                       'email'=>$options['email'] ,
                       'subject'=>$this->msgsubject ,
                       'text'=>$text ,
                       'document_id'=> $this->attach==1 ?  $options['doc']->document_id : 0
                    ));

                    $task->save();
                } else {
                    $ret =   self::sendEmail($options['email'], $text, $this->msgsubject, $this->attach==1 ? $options['doc'] : null);
                }

            }

            if(strlen($options['viber'])==0) {
                $options['viber'] = $options['phone'];
            }
            if(strlen($options['viber'])>0 && $this->msg_type == self::MSG_VIBER) {
                $ret =   self::sendViber($options['viber'], $text) ;
            }
            if(strlen($options['chat_id'])>0 && $this->msg_type == self::MSG_BOT) {
                $ret =   self::sendBot($options['chat_id'], $text, $this->attach==1 ? $options['doc'] : null,$this->html==1) ;
            }
         
            if($this->reciever_type == self::RSV_WH) {
                $ret =   self::sendHook($this->url, $text) ;
            }

            if(strlen($ret)>0) {
                \App\Helper::logerror($ret);
                $n = new \App\Entity\Notify();
                $n->user_id = \App\Entity\Notify::SYSTEM;
                $n->sender_id = \App\Entity\Notify::SUBSCRIBE;
                $n->message = $ret;

                $n->save();
                
            }         
    }    
    
    /**
     * возвращает текст  с  учетом разметки
     *
     * @param mixed $c
     */
    private function getTextCust($c) {
        $this->msgtext = str_replace('{', '{{', $this->msgtext);
        $this->msgtext = str_replace('}', '}}', $this->msgtext);
        $common = \App\System::getOptions("common");

        $header = array();
        $header['customer_id'] = $c->customer_id;
        $header['customer_name'] = $c->customer_name;
       
       
        try {
            $m = new \Mustache_Engine();
            $text = $m->render($this->msgtext, $header);

            return $text;
        } catch(\Exception $e) {
            return "Помилка розмітки";
        }        
    }
 /**
     * возвращает текст  с  учетом разметки
     *
     * @param mixed $c
     */
    private function getTextEndDay( ) {
        $this->msgtext = str_replace('{', '{{', $this->msgtext);
        $this->msgtext = str_replace('}', '}}', $this->msgtext);
        $common = \App\System::getOptions("common");
        $conn =   \ZDB\DB::getConnect();

        $header = array();
     

        $sql = "select coalesce(sum(amount),0)  from paylist_view where  paytype <=1000 and mf_id  in (select mf_id  from mfund where detail not like '%<beznal>1</beznal>%' )";
        $header['day_nal']= H::fa($conn->GetOne($sql));
        $sql = "select coalesce(sum(amount),0)  from paylist_view where  paytype <=1000 and mf_id  in (select mf_id  from mfund where detail like '%<beznal>1</beznal>%' )";
        $header['day_beznal']= H::fa($conn->GetOne($sql));
       
        $sql = "  select   sum(0-e.quantity*e.outprice) as summa 
              from entrylist_view  e
              join documents_view d on d.document_id = e.document_id
              where   (e.tag = 0 or e.tag = -1  or e.tag = -4) 
              and d.meta_name in ('GoodsIssue','ServiceAct' ,'POSCheck', 'TTN','OrderCust','OrderFood')           
              AND  e.document_date = CURDATE() ";             
              
        $header['day_summa']= H::fa( abs(  $conn->GetOne($sql) ) );
        $sql = " select   sum(0-e.quantity*e.outprice) as summa 
              from entrylist_view  e
              join documents_view d on d.document_id = e.document_id
              where   (e.tag = 0 or e.tag = -1  or e.tag = -4) 
              and d.meta_name in ( 'ReturnIssue' )           
              AND  e.document_date = CURDATE() ";             
              
        $header['day_return']= H::fa( abs( $conn->GetOne($sql) ));
        
        
        
 //минимальное количество
            $header['minqtylist']  = [];
   
            $sql = "select coalesce(t.qty,0) as onstoreqty, i.minqty,i.itemname as name,i.item_code as code    from 
           items  i 
          left join (select  item_id, coalesce(sum( qty),0) as qty   from  store_stock       group by  item_id    ) t
               on t.item_id = i.item_id
           
            where i.disabled  <> 1 and  coalesce(t.qty,0) < i.minqty and i.minqty>0 order  by  i.itemname ";
            $rs = $conn->Execute($sql);
  
            foreach($rs as $row) {
               $header['minqtylist'][]= $row; 
            }
  
   
        try {
            $m = new \Mustache_Engine();
            $text = $m->render($this->msgtext, $header);

            return $text;
        } catch(\Exception $e) {
            return "Помилка розмітки";
        }        
    }
   
    /**
     * возвращает текст  с  учетом разметки
     *
     * @param mixed $doc
     */
    private function getTextDoc($doc) {
        //в  разметке  одинарные
        $this->msgtext = str_replace('{', '{{', $this->msgtext);
        $this->msgtext = str_replace('}', '}}', $this->msgtext);
        
        $common = \App\System::getOptions("common");

        $header = array();


        $header['document_id'] = $doc->document_id;
        $header['customer_id'] = $doc->customer_id;
        $header['document_number'] = $doc->document_number;
        $header['doc_dn'] = intval(preg_replace('/[^0-9]/', '', $doc->document_number));
        $header['document_date'] = \App\Helper::fd($doc->document_date);
        $header['document_type'] = $doc->meta_desc;
        $header['amount'] = \App\Helper::fa($doc->amount);
        $header['forpay'] = \App\Helper::fa($doc->payamount);
        $header['customer_name'] = $doc->customer_name;
        $header['author'] = $doc->username;
        $header['notes'] = $doc->notes;
        $header['nal'] = '';
        $header['mf'] = '';
        $header['pos'] = '';
        $header['source'] = '';
        $header['payed'] = '';
        $header['credit'] = '';
        $header['payurl'] = '';
        $header['orderno'] = '';
       // $header['botname'] = $common['tbname'] ??'';
        $header['device'] = $doc->headerdata['device'] ??'';
        $header['ttnnp'] = $doc->headerdata['ship_number'] ??'';
        if (strlen($doc->headerdata['device']??'') > 0 && strlen($doc->headerdata['devsn']??'') > 0) {
            $header['device'] .= " (" . $doc->headerdata['devsn'] . ")";
        }


        if ($doc->headerdata['payment'] > 0) {
            $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);
            $header['mf'] = $mf->mf_name;
            if(strlen($mf->bank)>0) {
                $header['mf'] = $mf->bank;
                $header['mfacc'] = $mf->bankacc;
            }

            if ($mf->beznal == 1) {
                $header['nal'] = "Безготівка";
            } else {
                $header['nal'] = "Готівка";
            }
        } else {
            if ($doc->payamount > 0 && $doc->headerdata['payed'] == 0) {
                $header['mf'] = "Постоплата (кредит)";
            }
            if ($doc->payamount == 0) {
                $header['mf'] = "Передоплата";
            }
        }
        if($doc->meta_name == 'POSCheck') {

            if(doubleval($doc->headerdata['payedcard']) ==0 &&  $doc->headerdata['mfnal']  >0 && $doc->headerdata['payed'] > 0) {
                $header['nal'] = "Готівка";
                $mf = \App\Entity\MoneyFund::load($doc->headerdata['mfnal']);
                $header['mf'] = $mf->mf_name;

            }
            if(doubleval($doc->headerdata['payed']) ==0 && $doc->headerdata['mfbeznal']  >0 && $doc->headerdata['payedcard'] > 0) {
                $header['nal'] = "Безготівка";
                $mfb = \App\Entity\MoneyFund::load($doc->headerdata['mfbeznal']);
                $header['mf'] = $mfb->mf_name;
                if(strlen($mfb->bank)>0) {
                    $header['mf'] = $mfb->bank;
                    $header['mfacc'] = $mfb->bankacc;
                }

            }
            if($doc->headerdata['mfnal']  >0 && $doc->headerdata['payed'] > 0 && $doc->headerdata['mfbeznal']  >0 && $doc->headerdata['payedcard'] > 0) {
                $mf = \App\Entity\MoneyFund::load($doc->headerdata['mfnal']);
                $mfb = \App\Entity\MoneyFund::load($doc->headerdata['mfbeznal']);
                $header['mf'] = $mf->mf_name." + ".$mfb->mf_name;
                if(strlen($mfb->bank)>0) {
                    $header['mf'] =  $mf->mf_name." + ".$mfb->bank;
                    $header['mfacc'] = $mfb->bankacc;
                }


                $header['nal'] = "Комбінована";
            }

        }

        $payed= doubleval($doc->headerdata['payed']) + doubleval($doc->headerdata['payedcard']) ;

        if ($payed == 0 && $doc->payamount > 0) {
            $header['mf'] = "Постоплата (кредит)";
        }
        if ($payed == 0 && $doc->payamount == 0) {
            $header['mf'] = "Передоплата";
        }
        if ($payed > 0) {
            $header['payed'] = \App\Helper::fa($payed);
        }

        if ($doc->headerdata['pos']) {
            $pos = \App\Entity\Pos::load($doc->headerdata['pos']);
            $header['pos'] = $pos->pos_name;
        }
        if ($doc->headerdata['salesource'] > 0) {
            $sl = H::getSaleSources();
            $header['source'] = $sl[$doc->headerdata['salesource']];
        }
        if ($doc->customer_id > 0) {
            $cust = \App\Entity\Customer::load($doc->customer_id) ;
         
            $header['customer_name'] = $cust->customer_name;  
            $dolg = $cust->getDolg();
            if($dolg >0) {
                $header['credit'] = \App\Helper::fa($dolg);
            }

        }
        $header['taxurl'] = $doc->getFiscUrl();
        if(strlen($doc->headerdata['hash'])>0) {

            $header['docurl'] = _BASEURL . 'doclink/' . $doc->headerdata['hash'];

        }
        $header['docview'] = _BASEURL . 'doclist/' . $doc->document_id;

        $qr=$doc->getQRPay() ;
        if(is_array($qr)) {
            $header['payurl']   = $qr['url']  ;
        }

        if($doc->meta_name == 'Order') {
           $header['orderno'] = $doc->document_number;
           if($doc->getHD('outnumber','') !=''){
               $header['orderno'] = $doc->getHD('outnumber' ) ;
           }
        }           
        
        if($doc->parent_id >0)  {
            $basedoc=\App\Entity\Doc\Document::load($doc->parent_id)->cast();
            if($basedoc->meta_name == 'POSCheck') {
               $header['taxurl'] = $basedoc->getFiscUrl();
               if($basedoc->parent_id >0)   {
                   $basebasedoc=\App\Entity\Doc\Document::load($basedoc->parent_id)->cast();
                   if($basebasedoc->meta_name == 'Order') { //если  чек  на основании заказа
                      $header['orderno'] = $basebasedoc->document_number;
                      if($basebasedoc->getHD('outnumber','') !=''){
                          $header['orderno'] = $basebasedoc->getHD('outnumber' ) ;
                      }
                   }   
               }
            }           
            if($basedoc->meta_name == 'Order') {   //если     на основании заказа
               $header['orderno'] = $basedoc->document_number;
               if($basedoc->getHD('outnumber','') !=''){
                   $header['orderno'] = $basedoc->getHD('outnumber' ) ;
               }
               
            }           
            
        }
        


        $table = array();
        foreach ($doc->unpackDetails('detaildata') as $item) {
            $table[] = array('item_name'    => $item->itemname,
                             'item_code'    => $item->item_code,
                             'item_barcode' => $item->bar_code,
                             'msr'          => $item->msr,
                             'qty'          => \App\Helper::fqty($item->quantity),
                             'price'        => \App\Helper::fa($item->price),
                             'summa'        => \App\Helper::fa($item->price * $item->quantity)
            );
        }


        $header['list'] = $table;

        try {
            $m = new \Mustache_Engine();
            $text = $m->render($this->msgtext, $header);

            return $text;
        } catch(\Exception $e) {
            return "Помилка розмітки";
        }
    }

     
    
    public static function sendEmail($email, $text, $subject, $doc=null) {
        global $_config;

       

        $emailfrom = $_config['smtp']['emailfrom'];
        if(strlen($emailfrom)==0) {
            $emailfrom = $_config['smtp']['user'];

        }
        $filename = '';
        $f = '';
        try {

            if($doc != null) {
                $filename = strtolower($doc->meta_name) . ".pdf";
                $html = $doc->cast()->generateReport();
                $dompdf = new \Dompdf\Dompdf(array('isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans'));
                $dompdf->loadHtml($html);

                $dompdf->render();

                $data = $dompdf->output();

                $f = tempnam(sys_get_temp_dir(), "eml");
                file_put_contents($f, $data);

            }



            $mail = new \PHPMailer\PHPMailer\PHPMailer();

            if ($_config['smtp']['usesmtp'] == true) {
                $mail->isSMTP();
                $mail->Host = $_config['smtp']['host'];
                $mail->Port = $_config['smtp']['port'];
                $mail->Username = $_config['smtp']['user'];
                $mail->Password = $_config['smtp']['pass'];
                $mail->SMTPAuth = true;
                if ($_config['smtp']['tls'] == true) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
            }


            $mail->setFrom($emailfrom);
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->msgHTML($text);
            $mail->CharSet = "UTF-8";
            $mail->IsHTML(true);
            if(strlen($filename)>0) {
                $mail->AddAttachment($f, $filename, 'base64', 'application/pdf');
            }


            if ($mail->send() === false) {
                H::logerror($mail->ErrorInfo) ;
                return "See log";
            } else {
                //  System::setSuccessMsg('E-mail відправлено');
            }
        } catch(\Exception $e) {

            H::logerror($e->getMessage()) ;
            return "See log";

        }
        return '';
    }

    public static function sendViber($phone, $text) {

        $sms = System::getOptions("sms");


        if ($sms['smstype'] == 2) {  // sms club


            $url = 'https://im.smsclub.mobi/vibers/send';

            $data = json_encode([
                'phones' => array($phone),
                'message' => $text,
                'sender' => $sms['smsclubvan']
            ]);

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERPWD => $sms['smsclublogin'] . ':' . $sms['smsclubpass'],
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ]
            ]);


            $response = curl_exec($ch);

            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


            $encoded = json_decode($response, true);
            curl_close($ch);


            if ($httpcode > 200) {
                return "code ".$httpcode . ' ' .$response;
            }

            return  ""  ;



        }
    }

    public static function sendNotify($user_id, $text) {
        $n = new \App\Entity\Notify();
        $n->user_id = $user_id;
        $n->sender_id = \App\Entity\Notify::SUBSCRIBE;
        $n->message = $text;

        $n->save();
    }

    public static function sendBot($chat_id, $text, $doc=null, $ishtml=false) {
        $bot = new \App\ChatBot(\App\System::getOption("common", 'tbtoken')) ;
        $bot->sendMessage($chat_id, $text,$ishtml)  ;
        if($doc!= null) {
            $filename = strtolower($doc->meta_name) . ".pdf";
            $html = $doc->cast()->generateReport();
            $dompdf = new \Dompdf\Dompdf(array('isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans'));
            $dompdf->loadHtml($html);

            $dompdf->render();

            $data = $dompdf->output();

            $f = tempnam(sys_get_temp_dir(), "bot");
            file_put_contents($f, $data);
            $bot->sendDocument($chat_id, $f, $filename) ;
            return '';
        }
    }

    public static function sendSMS($phone, $text) {

        try {
            $sms = System::getOptions("sms");

            if ($sms['smstype'] == 1) {  //semy sms
                $data = array(
                    "phone"  => $phone,
                    "msg"    => $text,
                    "device" => $sms['smssemydevid'],
                    "token"  => $sms['smssemytoken']
                );
                $url = "https://semysms.net/api/3/sms.php";
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                $output = curl_exec($curl);
                if (curl_errno($curl) > 0) {

                    return 'Curl error: ' . curl_error($curl);
                }
                curl_close($curl);
                $output = json_decode($output, true);
                if ($output['code'] <> 0) {

                    return $output['error'];
                } else {
                    return '';
                }
            }

            if ($sms['smstype'] == 2) {  // sms club


                $url = 'https://im.smsclub.mobi/sms/send';

                $data = json_encode([
                    'phone' => array($phone),
                    'message' => $text,
                    'src_addr' => $sms['smscluban']
                ]);

                $ch = curl_init();

                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $sms['smsclubtoken'],
                        'Content-Type: application/json'
                    ]
                ]);


                $response = curl_exec($ch);

                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


                $encoded = json_decode($response, true);
                curl_close($ch);

                if ($httpcode >200) {
                    H::logerror("code ".$httpcode) ;
                    H::logerror($response) ;
                    return "Error. See logs";
                }

                return  ""  ;
            }

            if ($sms['smstype'] == 3) {  //sms  fly

                $an = '';
                if (strlen($sms['flysmsan']) > 0) {
                    $an = "source=\"{$sms['flysmsan']}\"";
                }


                $lifetime = 4; // срок жизни сообщения 4 часа

                $myXML = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
                $myXML .= "<request>" . "\n";
                $myXML .= "<operation>SENDSMS</operation>" . "\n";
                $myXML .= '        <message   lifetime="' . $lifetime . '" ' . $an . ' >' . "\n";
                $myXML .= "        <body>" . $text . "</body>" . "\n";
                $myXML .= "        <recipient>" . $phone . "</recipient>" . "\n";
                $myXML .= "</message>" . "\n";
                $myXML .= "</request>";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_USERPWD, $sms['flysmslogin'] . ':' . $sms['flysmspass']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, 'https://sms-fly.com/api/api.php');
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
                $response = curl_exec($ch);

                if (curl_errno($ch) > 0) {

                    return 'Curl error: ' . curl_error($ch);
                }
                curl_close($ch);
                if (strpos($response, 'ACCEPT') > 0) {
                    return '';
                }

                return $response;
            }


        } catch(\Exception $e) {

            return $e->getMessage();
        }
    }

    
    public static function sendHook($url, $text) {

        try {
            
   
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $text);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
     //           $output = curl_exec($curl);
                if (curl_errno($curl) > 0) {

                    return 'Curl error: ' . curl_error($curl);
                }
                $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($status_code >= 300 ) {
                    return 'http code: ' . $status_code;
                }
                if ($status_code == 0 ) {
                    return 'http code:0 ' ;
                }
                 
                
                curl_close($curl);
                return '';

        } catch(\Exception $e) {

            return $e->getMessage();
        }
    }
    
}
