<?php

namespace App\Entity;

use App\Helper as H;

/**
 * Класс-сущность  подписка  на  событие
 *
 * @table=subscribes
 * @keyfield=sub_id
 */
class Subscribe extends \ZCL\DB\Entity
{

    //типы  событий
    const EVENT_DOCSTATE =1;
    
    //типы сообщений
    const MSG_NOTIFY = 1;
    const MSG_EMAIL = 2;
    const MSG_SMS = 3;
    const MSG_VIBER = 4;
  
    //типы  получателей
    const RSV_CUSTOMER =1;
    const RSV_DOCAUTHOR =2;
    const RSV_USER =3;
 
    protected function init() {
        $this->sub_id = 0;
    }
  
    protected function afterLoad() {
 
        $xml = @simplexml_load_string($this->detail);

        $this->docmetaname = (string)($xml->docmetaname[0]);
        $this->docstate = (int)($xml->docstate[0]);
 
        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();

        $this->detail = "<detail>";

        $this->detail .= "<docmetaname>{$this->docmetaname}</docmetaname>";
        $this->detail .= "<docstate>{$this->docstate}</docstate>";
 
        $this->detail .= "</detail>";

        return true;
    }

    public  static  function onDocumentState($metaname,$state) {
       
   //    $list = self::find('disabled <> 1 and sub_type= '. self::TYPE_DOCSTATE) ;
         
    }
    
    
    
    public static function getEventList(){
        $list = array();
        $list[self::EVENT_DOCSTATE]=  H::l("sb_docstate");
        
        return  $list;
        
    }
    public static function getMsgTypeList(){
        $list = array();
        $list[self::MSG_NOTIFY]=  H::l("sb_msgnotify");
        $list[self::MSG_EMAIL]=  H::l("sb_msgemail");
        $list[self::MSG_SMS]=  H::l("sb_msgsms");
        $list[self::MSG_VIBER]=  H::l("sb_msgviber");
        
        return  $list;
        
    }
    public static function getRecieverList(){
        $list = array();
        $list[self::RSV_CUSTOMER]=  H::l("sb_rsvcust");
        $list[self::RSV_DOCAUTHOR]=  H::l("sb_rsvda");
        $list[self::RSV_USER]=  H::l("sb_rsvuser");
        
        
        return  $list;
        
    }
    
}