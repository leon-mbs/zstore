<?php

namespace App\Entity\Doc;

use App\Helper;
use App\System;

/**
 * Класс-сущность документ
 *
 */
class Document extends \ZCL\DB\Entity
{
    // состояния  документа
    public const STATE_NEW         = 1;     //Новый
    public const STATE_EDITED      = 2;  //Отредактирован
    public const STATE_CANCELED    = 3;      //Отменен
    public const STATE_EXECUTED    = 5;      // Проведен
    public const STATE_DELETED     = 6;       //  Удален
    public const STATE_INPROCESS   = 7; // в  работе
    public const STATE_WA          = 8; // ждет подтверждения
    public const STATE_CLOSED      = 9; // Закрыт (выполнен и оплачен)
    public const STATE_INSHIPMENT  = 11; // В доставке
    public const STATE_DELIVERED   = 14; // доставлен
    public const STATE_REFUSED     = 15; // отклонен
    public const STATE_SHIFTED     = 16; // отложен
    public const STATE_FAIL        = 17; // Аннулирован
    public const STATE_FINISHED    = 18; // Закончен
    public const STATE_APPROVED    = 19;      //  Утвержден
    public const STATE_READYTOSHIP = 20; // готов к отправке
    public const STATE_WP          = 21; // ждет  оплату
    public const STATE_PAYED       = 22; // Оплачен

    // типы  экспорта
    public const EX_WORD  = 1;    //  Word
    public const EX_EXCEL = 2;    //  Excel
    public const EX_PDF   = 3;    //  PDF
    public const EX_POS   = 4;    //  POS терминал
    public const EX_MAIL  = 5;    //  Отправка  email

    //доставка
    public const DEL_SELF    = 1;    //  самовывоз
    public const DEL_BOY     = 2;    //  курьер
    public const DEL_SERVICE = 3;    //  служба доставки
    public const DEL_NP      = 4;    //  новая почта
    public const DEL_UP      = 5;    //  укрпочта
    public const DEL_MEEST   = 6;    //  мест
    public const DEL_ROZ     = 7;    //  

    /**
     * Ассоциативный массив   с атрибутами заголовка  документа
     *
     * @var mixed
     */
    public $headerdata = array();

    public $detaildata = array();

    private static $_metalist  = array();

    /**
     * документы должны создаватся методом create
     *
     * @param mixed $row
     */
    public function __construct($row = null) {
        parent::__construct($row);
    }

    /**
     * начальная инициализация. Вызывается автоматически  в  конструкторе  Entity
     *
     */
    protected function init() {
        $this->document_id = 0;
        $this->state = 0;
        $this->customer_id = 0;
        $this->branch_id = 0;
        $this->parent_id = 0;
        $this->amount = 0;
        $this->payamount = 0;
        $this->payed = 0;
        $this->document_number = '';
        $this->document_date = time();
        $this->notes = '';
        $this->headerdata = array();
        $this->detaildata = array();
        $this->headerdata['_state_before_approve_'] = '';
        $this->headerdata['contract_id'] = 0;
        $this->headerdata['timeentry'] = 0; // если  проводки нужны не на дату документа
        $this->headerdata['time'] = time();  //  для чеков
        
    }

    /**
     * возвращает метаданные  чтобы  работало в  дочерних классах
     *
     */
    protected static function getMetadata() {
        return array('table' => 'documents', 'view' => 'documents_view', 'keyfield' => 'document_id');
    }

    protected function afterLoad() {
        if(is_integer($this->document_date)==false && strlen($this->document_date) >0 ) {
            $this->document_date = @strtotime($this->document_date);
        }
        if(is_integer($this->lastupdate)==false && strlen($this->lastupdate) >0 ) {
            $this->lastupdate = @strtotime($this->lastupdate);
        }
       
        $this->unpackData();
    }

    protected function beforeSave() {
        $this->lastupdate=time();

        $common = \App\System::getOptions('common') ;
        $da = $common['actualdate'] ?? 0 ;

        if($da>$this->document_date) {
            throw new \Exception("Не можна змінювати документ старший " .date('Y-m-d', $da));
        }

        $fn = intval(mb_substr($this->document_number,0,1) );
        if($fn >0) {
            throw new \Exception("Номер документу має починатись з букви ");
        }

        if (false == $this->checkUniqueNumber()) {
            System::setWarnMsg('Не унікальний номер документа');
        }

        if ($this->parent_id > 0) {
            $p = Document::load($this->parent_id);
            $this->headerdata['parent_number'] = $p->document_number;
        }
        $this->packData();


        

    }

    /**
     * Упаковка  данных  в  XML
     *
     */
    private function packData() {


        $this->content = "<doc><header>";

        foreach ($this->headerdata as $key => $value) {

            $value= str_replace('<![CDATA[', '', $value) ;
            $value= str_replace(']]>', '', $value) ;
 

            if (is_numeric($value) || strlen($value) == 0) {

            } else {
                $value = "<![CDATA[" . $value . "]]>";
            }
            $this->content .= "<{$key}>{$value}</{$key}>";
        }
        $this->content .= "</header>";

        $this->content .= "</doc>";
  
        $this->content .= serialize($this->detaildata);    
        
        
        
        
    }

    /**
     * распаковка из  XML
     *
     */
    private function unpackData() {
        global $logger;
        $this->headerdata = array();
        if (strlen($this->content ?? '') == 0) {
            return;
        }

        $endxml = strpos($this->content,'</header></doc>') ;
        
        $xml=substr($this->content,0,$endxml+15) ;
        
        $xml = @simplexml_load_string($xml) ;
        if($xml==false) {

            $logger->error("Документ " . $this->document_number . " Невірний  контент");
            return;
        }


        foreach ($xml->header->children() as $child) {
            $ch = (string)$child;
 
            $this->headerdata[(string)$child->getName()] = $ch;
        }
        
        
        $det =    $xml=substr($this->content,$endxml+15) ;
      
        $this->detaildata = @unserialize($det) ;
      
      
        if(!is_array($this->detaildata)) {
            $this->detaildata =[];
        }
  
    }

    
   /**
     * распаковываем данные  детализации
     *
     */
    public function unpackDetails($dataname) {
        
        if(is_array($this->detaildata[$dataname] ?? null)) {
            return unserialize(serialize( $this->detaildata[$dataname] ));
        }

        //для   совместимтсти
        $list = @unserialize(@base64_decode($this->headerdata[$dataname] ??''));
        if (is_array($list)) {
            return $list;
        } else {
            return array();
        }
    }

    public function packDetails($dataname, $list) {
//        $data = base64_encode(serialize($list));
 //       $this->headerdata[$dataname] = $data;
       $this->detaildata[$dataname]= $list;
 
    }

    
    /**
    * устанавливает значение  в шапке
    * 
    * @param mixed $name
    * @param mixed $value
    * @return mixed
    */
    public function setHD(string $name, $value=null)  {
       if(strlen($name)=='')    return;
       if($value==null) {
          unset( $this->headerdata[$name] );    
       }   else {
          $this->headerdata[$name] = $value;       
       }
      
    }
 
    /**
    * возвращает  значение  шапки без предупреждений в  старших версиях  PHP
    * 
    * @param mixed $name
    * @param mixed $def
    */
    public function getHD(string $name, $def=null)  {    
       return  $this->headerdata[$name] ?? $def ;
    }    
     
    /**
     * Генерация HTML  для  печатной формы
     *
     */
    public function generateReport() {
        return "";
    }

    /**
     * Генерация  печати для POS  терминала  или  принтеров чеков
     *  $ps - генерировать  из шаблона  для  принт сервера
     */
    public function generatePosReport($ps=false) {
        return "";
    }

    /**
    * Генерация  команд для сервера  печати
    *
    */
    public function generatePS() {
        return "";
    }

    /**
     * Выполнение документа - обычно проводки по  складу и платежи
     *
     */
    public function Execute() {

    }


    /**
     * Запись  платежей
     * Для  документов  у которых платеж идет отдельно от остальных проводок
     */
    public function DoPayment() {

    }

    /**
     * Проводки по складу
     * Для  документов  у которых проводки  по  складу  идут отдельно от остальных проводок
     */
    public function DoStore() {

    }
  
    /**
    * обновляет баланс  контрагента
    * 
    */
    public function DoBalans() {

    }

    /**
     * Отмена  документа
     *
     */
    protected function Cancel() {
            $conn = \ZDB\DB::getConnect();

            //  удаляем  документ  со  всех  движений
            $conn->Execute("delete from entrylist where document_id =" . $this->document_id);

            //удаляем освободившиеся стоки
            $conn->Execute("delete from store_stock where stock_id not in (select  stock_id  from entrylist) ");

            //отменяем оплаты
            $conn->Execute("delete from paylist where document_id = " . $this->document_id);
     

            $conn->Execute("delete from iostate where document_id=" . $this->document_id);

            //лицевые счета  сотрудника
            $conn->Execute("delete from empacc where document_id=" . $this->document_id);
            
            //лицевые счета  контрагентов
            $conn->Execute("delete from custacc where document_id=" . $this->document_id);
            
            $conn->Execute("delete from eqentry where document_id=" . $this->document_id);

 
    }

     /**
     * проверка  может  ли  быть  отменен
     * Возвращает  текст ошибки если  нет
     */
    public function canCanceled() {

        $common = \App\System::getOptions('common') ;
        $da = $common['actualdate'] ?? 0 ;

        if($da>$this->document_date) {
            return  "Не можна відміняти документ старший " .date('Y-m-d', $da);
        }

        return "";
    }
    
    
    /**
     * создает  экземпляр  класса  документа   в   соответсии  с  именем  типа
     *
     * @param mixed $classname
     */
    public static function create($classname, $branch_id = 0): Document {
        $arr = explode("\\", $classname);
        $classname = $arr[count($arr) - 1];
        $conn = \ZDB\DB::getConnect();
        $sql = "select meta_id from  metadata where meta_type=1 and meta_name='{$classname}'";
        $meta = $conn->GetRow($sql);
        $fullclassname = '\App\Entity\Doc\\' . $classname;

        $doc = new $fullclassname();
        $doc->meta_id = $meta['meta_id'];
        $user = \App\System::getUser();

        $doc->user_id = $user->user_id;
        $doc->headerdata['author'] = $user->user_id;

        $doc->branch_id = $branch_id;
        if ($branch_id == 0) {
            $doc->branch_id = \App\ACL::checkCurrentBranch();
        }

        $doc->headerdata['cashier'] = $user->username;
        $common = \App\System::getOptions("common");
        if(strlen($common['cashier'])>0) {
            $doc->headerdata['cashier'] = $common['cashier'] ;
        }
        $hash = md5(''.rand(1, 1000000), false);
        $hash = base64_encode(substr($hash, 0, 24));
        $doc->headerdata['hash'] = strtolower($hash)  ;
    
        $firm=Helper::getFirmData()  ;
     
        $doc->headerdata["firm_name"]  =  $firm['firm_name']  ;
         
        return $doc;
    }

    /**
     * Приведение  типа и клонирование  документа
     */
    public function cast(): Document {

        if (strlen($this->meta_name ?? '') == 0) {
            $metarow = Helper::getMetaType($this->meta_id);
            $this->meta_name = $metarow['meta_name'];
        }
        $class = "\\App\\Entity\\Doc\\" . $this->meta_name;
        $doc = new $class($this->getData());
        $doc->unpackData();
       // $doc->document_number=$this->document_number;
        $doc->document_date=$this->document_date;
        $doc->lastupdate=$this->lastupdate;
        return $doc;
    }

    /**
     * Обновляет состояние  документа
     *
     * @param mixed $state
     * @param mixed $onlystate   только  смена  статуса  без  проводок
     */
    public function updateStatus($state, $onlystate = false) {


        if ($this->document_id == 0) {
            return false;
        }

        //если нет права  выполнять
        if ($state >= self::STATE_EXECUTED && \App\ACL::checkExeDoc($this, false, false) == false) {

            $this->headerdata['_state_before_approve_'] .= ( ','. $state);  //целевой статус
  

            $state = self::STATE_WA;   //переводим на   ожидание  утверждения
            \App\System::setInfoMsg('Очікує затвердження') ;
          
            
        } else {
            if ($state == self::STATE_CANCELED) {
                if($onlystate == false) {
                    $this->headerdata['timeentry'] = 0;
                    $this->Cancel();
                }
                $this->headerdata['_state_before_approve_'] = '';                
            } else {
                if ($state == self::STATE_EXECUTED) {
                    if($onlystate == false) {
                        $this->Execute();
                    }

                }
            }
        }


        $oldstate = $this->state;
        $this->state = $state;
     

        $this->priority = $this->getPriorytyByState($this->state) ;

        $this->save();

        if ($oldstate != $state) {
            $this->insertLog($state);            
            
            $doc = $this->cast();
            if($onlystate == false) {
                $doc->onState($state, $oldstate);
                if($state >4 && true) {
                   $doc->DoAcc();   
                }
                
            }
            // подписка  на  смену  статуса
            \App\Entity\Subscribe::onDocumentState($doc->document_id, $state);
            
           
            
        }

        return true;
    }

    /**
     * обработчик  изменения  статусов
     * переопределяется в  дочерних документах
     *
     * @param mixed $state новый  статус
     * @param mixed $oldstate старый статус
     */
    protected function onState($state, $oldstate) {

    }

    public function getPriorytyByState($state) {
        if($state == self::STATE_NEW) {
            return 100;
        }
        if($state == self::STATE_CLOSED) {
            return 1;
        }
        if($state == self::STATE_EXECUTED) {
            return 10;
        }
        if($state == self::STATE_FINISHED) {
            return 20;
        }
        if($state == self::STATE_DELIVERED) {
            return 30;
        }
        if($state == self::STATE_INPROCESS) {
            return 50;
        }
        if($state == self::STATE_SHIFTED) {
            return 40;
        }
        if($state == self::STATE_INSHIPMENT) {
            return 50;
        }
        if($state == self::STATE_WA) {
            return 90;
        }
        if($state == self::STATE_APPROVED) {
            return 80;
        }
        if($state == self::STATE_CANCELED) {
            return 70;
        }
        if($state == self::STATE_EDITED) {
            return 80;
        }
        if($state == self::STATE_REFUSED) {
            return 3;
        }
        if($state == self::STATE_DELETED) {
            return 2;
        }
        if($state == self::STATE_FAIL) {
            return 3;
        }
        if($state == self::STATE_READYTOSHIP) {
            return 50;
        }
        if($state == self::STATE_WP) {
            return 75;
        }
        if($state == self::STATE_PAYED) {
            return 15;
        }

        return 0;
    }

    /**
     * Возвращает название  статуса  документа
     *
     * @param mixed $state
     * @return mixed
     */
    public static function getStateName($state) {

        switch($state) {
            case Document::STATE_NEW:
                return "Новий";
            case Document::STATE_EDITED:
                return "Відредагований";
            case Document::STATE_CANCELED:
                return "Скасований";
            case Document::STATE_EXECUTED:
                return "Проведений";
            case Document::STATE_CLOSED:
                return "Закритий";
            case Document::STATE_APPROVED:
                return "Затверджений";
            case Document::STATE_DELETED:
                return "Видалений";

            case Document::STATE_WA:
                return "Очікує затвердження";
            case Document::STATE_INSHIPMENT:
                return "На доставці";
            case Document::STATE_FINISHED:
                return "Виконаний";
            case Document::STATE_DELIVERED:
                return "Доставлений";
            case Document::STATE_REFUSED:
                return "Відхилений";
            case Document::STATE_SHIFTED:
                return "Відкладений";
            case Document::STATE_FAIL:
                return "Анульований";
            case Document::STATE_INPROCESS:
                return "Виконується";
            case Document::STATE_READYTOSHIP:
                return "Готовий до відправки";
            case Document::STATE_WP:
                return "Очікує оплату";
            case Document::STATE_PAYED:
                return "Оплачений";

            default:
                return "Невідомий статус";
        }
    }

    public static function getStateList() {
        $list = array();
        $list[Document::STATE_NEW] = "Новий";
        $list[Document::STATE_EDITED] = "Відредагований";
        $list[Document::STATE_CANCELED] = "Скасований";
        $list[Document::STATE_EXECUTED] = "Проведений";
        $list[Document::STATE_CLOSED] = "Закритий";
        $list[Document::STATE_APPROVED] = "Готовий до виконання";
        $list[Document::STATE_WA] =       "Очікує затвердження";
        $list[Document::STATE_INSHIPMENT] = "На доставці";
        $list[Document::STATE_FINISHED] = "Виконаний";
        $list[Document::STATE_DELIVERED] = "Доставлений";
        $list[Document::STATE_REFUSED] = "Відхилений";
        $list[Document::STATE_SHIFTED] = "Відкладений";
        $list[Document::STATE_FAIL] = "Анульований";
        $list[Document::STATE_INPROCESS] = "Виконується";
        $list[Document::STATE_READYTOSHIP] = "Готовий до відправки";
        $list[Document::STATE_WP] = "Очікує оплату";
        $list[Document::STATE_PAYED] = "Оплачений";

        return $list;
    }

    /**
    * список  для  произвольного перевода  статуса
    *
    */
    public static function getStateListMan() {
        $list = array();
        $list[Document::STATE_CLOSED] = "Закритий";
        $list[Document::STATE_INSHIPMENT] = "На доставці";
        $list[Document::STATE_FINISHED] = "Виконаний";
        $list[Document::STATE_DELIVERED] = "Доставлений";
        $list[Document::STATE_EXECUTED] = "Проведений";

        $list[Document::STATE_SHIFTED] = "Відкладений";
        $list[Document::STATE_FAIL] = "Анульований";
        $list[Document::STATE_INPROCESS] = "Виконується";
        $list[Document::STATE_READYTOSHIP] = "Готовий до відправлення";
        $list[Document::STATE_WP] = "Очікує оплату";

        return $list;
    }

    /**
     * проверка  номера  на  уникальность
     *
     */
    public function checkUniqueNumber() {
        $document_number = trim($this->document_number);
        
        $branch = "";
        if ($this->branch_id > 0) {
            $branch = " and branch_id=" . $this->branch_id;
        }
        //  $doc = Document::getFirst("meta_id={$this->meta_id}  and  document_number = '{$this->document_number}' {$branch}");
        $doc = Document::getFirst(" meta_id={$this->meta_id} and  document_number = '{$document_number}' {$branch}");
        if ($doc instanceof Document) {
            if ($this->document_id != $doc->document_id) {
                return false;
            }
        }
        return true;
    }

    public function nextNumber($branch_id = 0) {
        $doc = $this->cast();
        $conn = \ZDB\DB::getConnect();
        $branch = "";
        if ($this->branch_id > 0) {
            $branch = " and branch_id=" . $this->branch_id;
        }
        if ($branch_id > 0) {
            $branch = " and branch_id=" . $branch_id;
        }
        
        $last=0;
        $letters='';
        $sql = "select document_number from  documents  where   meta_id={$this->meta_id}   {$branch}   order  by  document_id desc  "; 
        $lastdoc= $conn->GetOne($sql) ;
        if(strlen($lastdoc ??'')==0) {
            $letters = preg_replace('/[0-9]/', '', $doc->getNumberTemplate());
        }    else {
            $letters = preg_replace('/[0-9]/', '', $lastdoc);
        }
        $sql = "select document_number from  documents  where document_number like ". $conn->qstr( $letters.'%') ." and   meta_id={$this->meta_id}   {$branch}   order  by  document_id desc  "; 
      
        foreach($conn->Execute($sql) as $row) {
           $digits = intval( preg_replace('/[^0-9]/', '', $row['document_number']) );
           if($digits > $last) {
              $last =  $digits ; //максимальная цифра
           }
        }
        
        $last++;
        $d=5;
        if( strlen( ''.$last) >$d){ //если не  влазит
           $d =  strlen( ''.$last); 
        }
        $next = $letters . sprintf("%0{$d}d", $last);

        return $next;
    }

    /**
     * Возвращает  список  типов экспорта
     * Перегружается  дочерними  для  добавление  специфических  типов
     *
     */
    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF);
    }

    /**
     * Поиск  документа
     *
     * @param mixed $type имя или id типа
     * @param mixed $from начало  периода  или  null
     * @param mixed $to конец  периода  или  null
     * @param mixed $header значения заголовка
     */
    public static function search($type, $from, $to, $header = array()) {
        $conn = $conn = \ZDB\DB::getConnect();
        $where = "state= " . Document::STATE_EXECUTED;

        if (strlen($type) > 0) {
            if ($type > 0) {
                $where = $where . " and  meta_id ={$type}";
            } else {
                $where = $where . " and  meta_name='{$type}'";
            }
        }

        if ($from > 0) {
            $where = $where . " and  document_date >= " . $conn->DBDate($from);
        }
        if ($to > 0) {
            $where = $where . " and  document_date <= " . $conn->DBDate($to);
        }
        foreach ($header as $key => $value) {
            $where = $where . " and  content like '%<{$key}>{$value}</{$key}>%'";
        }

        return Document::find($where);
    }

    /**
     * @see \ZDB\Entity
     *
     */
    protected function afterDelete() {
        //global $logger;

        $conn = \ZDB\DB::getConnect();

        $hasExecuted = $conn->GetOne("select count(*)  from docstatelog where docstate = " . Document::STATE_EXECUTED . " and  document_id=" . $this->document_id);
        //   $hasPayment = $conn->GetOne("select count(*)  from paylist_view where   document_id=" . $this->document_id);

        $conn->Execute("delete from docstatelog where document_id=" . $this->document_id);

        $conn->Execute("delete from messages where item_type=" . \App\Entity\Message::TYPE_DOC . " and item_id=" . $this->document_id);
        $conn->Execute("delete from files where item_type=" . \App\Entity\Message::TYPE_DOC . " and item_id=" . $this->document_id);
        $conn->Execute("delete from filesdata where   file_id not in (select file_id from files)");

        \App\Entity\Tag::updateTags([],   \App\Entity\Tag::TYPE_OFFICEDCO,$this->document_id) ;
        
        
        
        //   if(System::getUser()->userlogin =='admin') return;
        if ($hasExecuted) {

            $n = new \App\Entity\Notify();
            $n->user_id = \App\Entity\Notify::SYSTEM;

            $n->message = "Користувач ".System::getUser()->username." видалив раніше проведений документ " .$this->document_number  ;
            $n->save();
        }
    }

    /**
     *
     *  запись состояния в  лог документа
     * @param mixed $state
     * @param mixed $user_id  если переназначен  юзер документа
     */
    public function insertLog($state,$user_id=0) {
        $conn = \ZDB\DB::getConnect();
        $host = $_SERVER["REMOTE_ADDR"];
        if($host==null) {
            $host = "";
        }
        $host = $conn->qstr($host);
        if($user_id==0){
            $user = \App\System::getUser();
            if($user == null) {
                $user = \App\Entity\User::getByLogin('admin') ;
            }
            $user_id= $user->user_id;
        }  else {

                $n = new \App\Entity\Notify();
                $n->user_id = $user_id;
                $n->sender_id = \App\Entity\Notify::SYSTEM;
                $n->dateshow = time();
                $n->message = "Ви призначені виконавцем документу {$this->document_number} " ;
         
                $n->save();            
        }
        
        $sql = "insert into docstatelog (document_id,user_id,createdon,docstate,hostname) values({$this->document_id},{$user_id},now(),{$state},{$host})";
        $conn->Execute($sql);
    }

    /**
     * список записей   в  логе   состояний
     *
     */
    public function getLogList() {
        $conn = \ZDB\DB::getConnect();
        $rc = $conn->Execute("select * from docstatelog_view where document_id={$this->document_id} order  by  log_id");
        $states = array();
        $i=0;
        foreach ($rc as $row) {
            $row['createdon'] = strtotime($row['createdon']);
            $states[$i++] = new \App\DataItem($row);
        }

        return $states;
    }

    /**
     *  проверка  был ли документ в  таких состояниях
     *
     * @param array $states
     */
    public function checkStates(array $states) {
        if (count($states) == 0) {
            return false;
        }
        $conn = \ZDB\DB::getConnect();
        $states = implode(',', $states);

        $cnt = $conn->getOne("select coalesce(count(*),0) from docstatelog where docstate in({$states}) and document_id={$this->document_id}");
        return $cnt;
    }

    /**
     * возвращает шаблон номераЮ перегружается дочерними классам
     * типа ПР-000000.  Буквенный код должен  быть уникальным для типа документа
     */
    protected function getNumberTemplate() {
        return '';
    }

    /**
    * возвращает  условия для  ограничений  доступа...
    * 
    */
    public static function getConstraint() {
        $c = \App\ACL::getBranchConstraint();
        $user = System::getUser();
       
        if ($user->rolename != 'admins') {
            if (strlen($c) == 0) {
                $c = "1=1 ";
            }
            if ($user->onlymy == 1) {
                $c .= " and (user_id  = {$user->user_id}  or  content like '%<author>{$user->user_id}</author>%'   ) " ;
            }
            if (strlen($user->aclview) > 0) {
                $c .= " and meta_id in({$user->aclview}) ";
            } else {
                $c .= " and meta_id in(0) ";
            }
        }

        return $c;
    }

    /**
     * возвращает  сумму  оплат
     *
     */
    public function getPayAmount() {
        $conn = \ZDB\DB::getConnect();

        return $conn->GetOne("select coalesce(sum(amount),0) from paylist_view where   document_id = {$this->document_id}  ");
    }


    /* public function hasEntry() {
         $conn = \ZDB\DB::getConnect();

         return $conn->GetOne("select coalesce(sum(amount),0) from paylist_view where   document_id = {$this->document_id}  ");
     } */

    /**
     * список  дочерних
     *
     * @param mixed $type мета  тип
     * @param mixed $executed в  состоянии  выполнен и т.д.
     */
    public function getChildren($type = "", $executed = false) {
        $where = "parent_id=" . $this->document_id;
        if (strlen($type) > 0) {
            $where .= " and meta_name='{$type}'";
        }
        if ($executed) {
            $where .= " and state > 4 ";
        }
        return Document::find($where);
    }

    /**
     *  Возвращает  списки  документов которые  могут быть  созданы  на  основании
     *
     */
    public function getRelationBased() {
        $list = array();

        return $list;
    }

 
    /**
     * Локализованное название документа  по  мета имени
     *
     * @param mixed $meta_name
     */
    public static function getDesc($meta_name) {
        if (isset(self::$_metalist[$meta_name])) {
            return self::$_metalist[$meta_name];
        }
        $conn = \ZDB\DB::getConnect();

        $rs = $conn->Execute("select description, meta_name from metadata ");
        foreach ($rs as $m) {
            self::$_metalist[$m['meta_name']] = $m['description'];
        }

        return self::$_metalist[$meta_name];
    }

    /**
     * Список  типов  доставки
     */
    public static function getDeliveryTypes($np = false) {
        $list = array();
        $list[self::DEL_SELF] = 'Самовивіз';
        $list[self::DEL_BOY] = 'Кур`єр';
        if ($np == true) {
            $list[self::DEL_NP] = 'Нова пошта';
        }

        $list[self::DEL_UP] = 'Укр. пошта';
        $list[self::DEL_MEEST] = 'Meest';
        $list[self::DEL_ROZ] = 'Rozetka';
        $list[self::DEL_SERVICE] = 'Iнша служба доставки';

        return $list;
    }

    /**
     * Отправка  документа  по  почте
     *
     */
    public function sendEmail() {
        global $_config;
        $doc = $this->cast();

        if ($doc->customer_id == 0) {
            return;
        }

        $customer = \App\Entity\Customer::load($doc->customer_id);

        $filename = strtolower($doc->meta_name) . ".pdf";
        $html = $doc->generateReport();

        try {
            $dompdf = new \Dompdf\Dompdf(array('isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans'));
            $dompdf->loadHtml($html);

            $dompdf->render();

            $data = $dompdf->output();

            $f = tempnam(sys_get_temp_dir(), "eml");
            file_put_contents($f, $data);

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
            $mail->setFrom($_config['smtp']['emailfrom'], '');
            $mail->addAddress($customer->email);
            $mail->Subject = $doc->getEmailSubject();
            $mail->msgHTML($doc->getEmailBody());
            $mail->CharSet = "UTF-8";
            $mail->IsHTML(true);
            $mail->AddAttachment($f, $filename, 'base64', 'application/pdf');
            if ($mail->send() === false) {
                System::setErrorMsg($mail->ErrorInfo);
            } else {
                System::setSuccessMsg('E-mail відправлено');
            }
        } catch(\Exception $e) {
            System::setErrorMsg($e->getMessage());
        }
 
    }

    /**
     * возвращает  заполненый  шаблон  письма
     *
     */
    protected function getEmailBody() {
        return "";
    }

    /**
     * возвращает  тему письма
     *
     */
    protected function getEmailSubject() {
        return "";
    }


    /**
     * есть ли  оплаты
     *
     */
    public function hasPayments() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(sum(amount),0) from paylist_view where   document_id=" . $this->document_id;
        $am = doubleval($conn->GetOne($sql));

        return $am != 0;

    }

    /**
     * есть ли  проводки  по  складу
     *
     */
    public function hasStore() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(count(*),0) from entrylist where   document_id=" . $this->document_id;
        $am = round($conn->GetOne($sql));

        return $am > 0;

    }

    /**
     * возвращает  тэг <img> со штрих кодом номера  документа
     *
     */
    protected function getBarCodeImage() {
        $print = System::getOption('common', 'printoutbarcode');
        if ($print == 0) {
            return '';
        }
        try{
           $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
           $img = '<img style="max-width:200px" src="data:image/png;base64,' . base64_encode($generator->getBarcode($this->document_number, 'code128')) . '">';
        } catch (\Throwable $e) {
          \App\Helper::logerror("barcode: ".$e->getMessage()) ;
           return '';
        }
        return $img;
    }

    /**
     * возвращает  тэг <img> со QR кодом ссылки на  сайт налоговой
     *
     */
    protected function getQRCodeImage($text=false) {

        $print = System::getOption('common', 'printoutqrcode');
        if ($print == 0) {
            return '';
        }
        $url =$this->getFiscUrl();
        
        if($text) {
            if(strlen($url)==0) {
                return false;
            }
            return $url;
        }
        if(strlen($url)==0) {
            return '';
        }

        $dataUri = \App\Util::generateQR($url, 200, 5)  ;
        $img = "<img style=\"width:80%\"  src=\"{$dataUri}\"  />";

        return $img;
    }

    /**
    * put your comment there...
    *
      * @return mixed
    */
    public function getQRPay() {

        if(in_array($this->meta_name, ['GoodsIssue','Invoice','Order','POSCheck'])  ==  false) {
            return false;
        }
        //оплачен
        if( $this->payamount > 0 &&  $this->payamount <=  $this->payed  ) {
            return false;
        }
 
   
        $mf=\App\Entity\MoneyFund::load($this->getHD('payment'));
        
      
        if($mf == null) {
            return false;
        }
 
        $payee= $mf->payname ??'' ;
        $kod= $mf->code ??'' ;   
        $iban = $mf->iban??'';
        if(strlen($kod)==0 || strlen($iban) == 0|| strlen($payee) == 0) {
            return false;
        }

         
        
        $number = $this->document_number;
        if(strlen($this->headerdata['outnumber'] ?? '') > 0) {
            $number  =    $this->headerdata['outnumber']  ;
        }

        $payamount=$this->payamount;
        if(($this->headerdata['payedcard'] ??0) > 0) {
            $payamount =  $this->headerdata['payedcard'] ;
        }

    
 
        
 
        $url = "BCD\n002\n1\nUCT\n\n";
        $url = $url .  $payee ."\n";
        $url = $url .  $iban."\n";
        $url = $url .  "UAH". \App\Helper::fa($payamount)."\n";
        $url = $url .  $kod."\n\n\n";
        $url = $url .  $this->meta_desc ." ".$number." від ".  \App\Helper::fd($this->document_date) ."\n\n";

        $url = base64_encode($url);
        $url = str_replace("+", "-", $url) ;
        $url = str_replace("/", "_", $url) ;
        $url = str_replace("=", "", $url) ;

        $url = "https://bank.gov.ua/qr/".$url;

        $dataUri = \App\Util::generateQR($url, 240, 10)  ;
        $img = "<img style=\"width:260px\"  src=\"{$dataUri}\"  />";

        return array('qr'=>$img,
          'url'=>$url,
//         
          'link'=>"<a href=\"{$url}\">{$url}</a>"
        );
    }
   

    /**
    *    возвращает ссылку  на чек в  налоговой
    *    https://cabinet.tax.gov.ua/cashregs/check?fn=4000191957&id=165093488&date=20220105&time=132430&sum=840
    */
    public function getFiscUrl() {
        if(strlen($this->headerdata["tax_url"]??'')>0) {
            return $this->headerdata["tax_url"];
        }

        if(strlen($this->headerdata["fiscalnumber"]??'')==0) {
            return "";
        }

        $pos = \App\Entity\Pos::load($this->headerdata['pos']);

        $url = "https://cabinet.tax.gov.ua/cashregs/check?" ;
        $url .=  "fn=". $pos->fiscalnumber ;
        $url .=  "&id=". $this->headerdata["fiscalnumber"] ;
        $url .=   $this->headerdata["fiscdts"] ;
        $url .=  "&sm=". number_format($this->payamount, 2, '.', '') ;

        return $url;
    }
  
  
    public function getID() {
        return $this->document_id;
    }


    public function getAmountReg() {
        $am=$this->amount;
        if($this->payamount <> 0) {
            $am=$this->payamount;
        }

        return  $am;
    }

    /**
    * бонусы,  начисленные по  документу
    *
    * @param mixed $add  начисленые  иначе  списаные
    */
    public function getBonus($add=true) {
        $conn = \ZDB\DB::getConnect();
        if($add) {
            $sql = "select coalesce(sum(amount),0) as bonus from custacc where optype=1 and amount > 0 and document_id =" . $this->document_id;
        } else {
            $sql = "select coalesce(sum(0-amount),0) as bonus from custacc where optype=1 and  amount < 0 and document_id =" . $this->document_id;
        }

        return $conn->GetOne($sql);

    }

    protected function beforeDelete() { 
        $this->Cancel();
    }
 
     /**
     * актуальное  значение оплат
     * 
     */
    public function getPayed() { 
        $conn = \ZDB\DB::getConnect();

        $sql = "select coalesce(abs(sum(amount)),0) from paylist_view where paytype < 1000  and  document_id=" . $this->document_id;
        $payed = doubleval($conn->GetOne($sql));
        return $payed;
    }    
    
   /**
   * кастомный  экспорт в  ексель вместо автоматического  преобразования  с HTML
   * возвращает  готовый файл
   * реализация производится  в  соответствующемклассе-наследнике
   */
   public function customExportExcel() { 
       return '';
   }   
   /**
   * кастомный  экспорт в  pdf вместо автоматического  преобразования  с HTML
   * возвращает  готовый файл
   * реализация производится  в  соответствующемклассе-наследнике
   */
   public function customExportPDF() { 
       return ''; 
   }   
    
    /**
    * открыт на  редактирование
    * 
    * @param mixed $document_id
    */
    public static function checkout($document_id ) {
        if(intval($document_id)==0)  return;
        
        $cat =Helper::STAT_DOC_ISEDITED;
        $conn = \ZDB\DB::getConnect();
        $dt = $conn->DBTimeStamp(strtotime('-2 hour'));
        $conn->Execute("delete from stats where  category ={$cat} and dt < {$dt} ");
      
      
        $user_id= intval($conn->GetOne("select vald from stats where  category ={$cat} and keyd = {$document_id} limit 0,1  ") );
        if($user_id > 0) {
            $user= \App\Entity\User::load($user_id) ;
            \App\System::setWarnMsg("Документ  редагується  користувачем  ".$user->username)  ;
            return;
        }
       
        $user_id = \App\System::getUser()->user_id;
        $dt = $conn->DBTimeStamp(time());
        $conn->Execute("insert into stats  ( category, keyd,vald,dt)  values ({$cat},{$document_id},{$user_id},{$dt})");


    }
    
    /**
    * закончил редактирование
    * 
    * @param mixed $document_id
    */
    public static function checkin($document_id ) {
        if(intval($document_id)==0)  return;
    
        $cat =Helper::STAT_DOC_ISEDITED;
        $user_id = \App\System::getUser()->user_id;
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from stats where  category ={$cat} and keyd = {$document_id} and vald = {$user_id} ");
      
 
    }
    
    public   function DoAcc() {
         
    } 
      
}
