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
    const STATE_NEW         = 1;     //Новый
    const STATE_EDITED      = 2;  //Отредактирован
    const STATE_CANCELED    = 3;      //Отменен
    const STATE_EXECUTED    = 5;      // Проведен
    const STATE_DELETED     = 6;       //  Удален
    const STATE_INPROCESS   = 7; // в  работе
    const STATE_WA          = 8; // ждет подтверждения
    const STATE_CLOSED      = 9; // Закрыт , доставлен, выполнен
    const STATE_INSHIPMENT  = 11; // В доставке
    const STATE_DELIVERED   = 14; // доставлен
    const STATE_REFUSED     = 15; // отклонен
    const STATE_SHIFTED     = 16; // отложен
    const STATE_FAIL        = 17; // Аннулирован
    const STATE_FINISHED    = 18; // Закончен
    const STATE_APPROVED    = 19;      //  Готов к выполнению
    const STATE_READYTOSHIP = 20; // готов к отправке   

    // типы  экспорта
    const EX_WORD  = 1; //  Word
    const EX_EXCEL = 2;    //  Excel
    const EX_PDF   = 3;    //  PDF
    const EX_POS   = 4;    //  POS терминал
    const EX_MAIL  = 5;    //  Отправка  email
    // const EX_XML_GNAU = 4;
    //доставка
    const DEL_SELF    = 1;    //  самовывоз
    const DEL_BOY     = 2;    //  курьер
    const DEL_SERVICE = 3;    //  служба доставки
    const DEL_NP      = 4;         //  новая почта

    /**
     * Ассоциативный массив   с атрибутами заголовка  документа
     *
     * @var mixed
     */
    public $headerdata = array();

    /**
     * Массив  ассоциативных массивов (строк) содержащих  строки  детальной части (таблицы) документа
     *
     * @var mixed
     */
    public         $detaildata = array();
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
        $this->notes = '';

        $this->document_date = time();
        $this->user_id = 0;

        $this->headerdata = array();
        $this->detaildata = array();
        $this->headerdata['contract_id'] = 0;
    }

    /**
     * возвращает метаданные  чтобы  работало в  дочерних классах
     *
     */
    protected static function getMetadata() {
        return array('table' => 'documents', 'view' => 'documents_view', 'keyfield' => 'document_id');
    }

    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
        $this->unpackData();
    }

    protected function beforeSave() {


        if (false == $this->checkUniqueNumber()) {
            System::setWarnMsg(\App\Helper::l('nouniquedocnumber'));
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


            if (strpos($value, '[CDATA[') !== false) {
                \App\System::setWarnMsg('CDATA в  поле  обьекта');
                \App\Helper::log(' CDATA в  поле  обьекта');
                continue;
            }

            if (is_numeric($value) || strlen($value) == 0) {

            } else {
                $value = "<![CDATA[" . $value . "]]>";
            }
            $this->content .= "<{$key}>{$value}</{$key}>";
        }
        $this->content .= "</header>";

        $this->content .= "</doc>";
    }
    //   select ExtractValue(@xml, '//doc/header/customer_id') from  documents

    /**
     * распаковка из  XML
     *
     */
    private function unpackData() {

        $this->headerdata = array();
        if (strlen($this->content) == 0) {
            return;
        }

        try {
            $xml = new \SimpleXMLElement($this->content);
        } catch(\Exception $ee) {
            global $logger;
            $logger->error("Документ " . $this->document_number . " " . $ee->getMessage());
            return;
        }
        foreach ($xml->header->children() as $child) {
            $ch = (string)$child;
            /*   if(is_numeric($ch)) {
                      if(ctype_digit($ch))  $ch = intval($ch);
                      else $ch = doubleval($ch)  ;
                }
             */
            $this->headerdata[(string)$child->getName()] = $ch;
        }

        /*
        $this->detaildata = array();

        //deprecated
        if (isset($xml->detail)) {

            foreach ($xml->detail->children() as $row) {
                $_row = array();
                foreach ($row->children() as $item) {
                    $_row[(string)$item->getName()] = (string)$item;
                }
                $this->detaildata[] = $_row;
            }
        }
        //перепаковываем в новый вариант
        if (count($this->detaildata) > 0) {
            $detaildata = array();

            foreach ($this->detaildata as $row) {
                if ($row['service_id'] > 0) {
                    $detaildata[$row['service_id']] = new \App\Entity\Service($row);
                } else {
                    if ($row['stock_id'] > 0) {
                        $detaildata[$row['stock_id']] = new \App\Entity\Stock($row);
                    } else {
                        $id = (strlen($row['item_id']) > 0 ? $row['item_id'] : '');
                        $detaildata[$id] = new \App\Entity\Item($row);
                    }
                }
            }
            $this->packDetails('detaildata', $detaildata);
            
        }  */


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
     *
     */
    public function generatePosReport() {
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
     * Отмена  документа
     *
     */
    protected function Cancel() {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();
        try {
            // если  метод не переопределен  в  наследнике удаляем  документ  со  всех  движений
            $conn->Execute("delete from entrylist where document_id =" . $this->document_id);

            //удаляем освободившиеся стоки
            $conn->Execute("delete from store_stock where stock_id not in (select  stock_id  from entrylist) ");

            //отменяем оплаты   
            $conn->Execute("delete from paylist where document_id = " . $this->document_id);


            $conn->Execute("delete from iostate where document_id=" . $this->document_id);

            $conn->Execute("delete from empacc where document_id=" . $this->document_id);


            $conn->CompleteTrans();
        } catch(\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            \App\System::setErrorMsg($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return false;
        }
        return true;
    }

    /**
     * создает  экземпляр  класса  документа   в   соответсии  с  именем  типа
     *
     * @param mixed $classname
     */
    public static function create($classname, $branch_id = 0) {
        $arr = explode("\\", $classname);
        $classname = $arr[count($arr) - 1];
        $conn = \ZDB\DB::getConnect();
        $sql = "select meta_id from  metadata where meta_type=1 and meta_name='{$classname}'";
        $meta = $conn->GetRow($sql);
        $fullclassname = '\App\Entity\Doc\\' . $classname;

        $doc = new $fullclassname();
        $doc->meta_id = $meta['meta_id'];
        $doc->user_id = \App\System::getUser()->user_id;

        $doc->branch_id = $branch_id;
        if ($branch_id == 0) {
            $doc->branch_id = \App\Acl::checkCurrentBranch();
        }

        return $doc;
    }

    /**
     * Приведение  типа и клонирование  документа
     */
    public function cast() {

        if (strlen($this->meta_name) == 0) {
            $metarow = Helper::getMetaType($this->meta_id);
            $this->meta_name = $metarow['meta_name'];
        }
        $class = "\\App\\Entity\\Doc\\" . $this->meta_name;
        $doc = new $class($this->getData());
        $doc->unpackData();
        return $doc;
    }

    /**
     * Обновляет состояние  документа
     *
     * @param mixed $state
     */
    public function updateStatus($state) {


        if ($this->document_id == 0) {
            return false;
        }

        //если нет права  выполнять    
        if ($state >= self::STATE_EXECUTED && \App\Acl::checkExeDoc($this, false, false) == false) {

            $this->headerdata['_state_before_approve_'] = $state;  //целевой статус
            if ($state == self::STATE_WA) {   //если на утверждение   то  ждем  утверждения
                $this->headerdata['_state_before_approve_'] = self::STATE_APPROVED;
            }

            $state = self::STATE_WA;   //переводим на   ожидание  утверждения
        } else {
            if ($state == self::STATE_CANCELED) {
                $this->Cancel();
            } else {
                if ($state == self::STATE_EXECUTED) {
                    $this->Execute();
                }
            }
        }


        $oldstate = $this->state;
        $this->state = $state;
        $this->insertLog($state);
    
        $this->save();

        if ($oldstate != $state) {
            $doc = $this->cast();
            $doc->onState($state);

            \App\Entity\Subscribe::onDocumentState($doc->document_id, $state);
        }        
        
        return true;
    }

    /**
     * обработчик  изменения  статусов
     * переопределяется в  дочерних документах
     *
     * @param mixed $state новый  статус
     */
    protected function onState($state) {

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
                return Helper::l('st_new');
            case Document::STATE_EDITED:
                return Helper::l('st_edit');
            case Document::STATE_CANCELED:
                return Helper::l('st_canceled');
            case Document::STATE_EXECUTED:
                return Helper::l('st_executed');
            case Document::STATE_CLOSED:
                return Helper::l('st_closed');
            case Document::STATE_APPROVED:
                return Helper::l('st_approved');
            case Document::STATE_DELETED:
                return Helper::l('st_deleted');

            case Document::STATE_WA:
                return Helper::l('st_wa');
            case Document::STATE_INSHIPMENT:
                return Helper::l('st_inshipment');
            case Document::STATE_FINISHED:
                return Helper::l('st_finished');
            case Document::STATE_DELIVERED:
                return Helper::l('st_delivered');
            case Document::STATE_REFUSED:
                return Helper::l('st_refused');
            case Document::STATE_SHIFTED:
                return Helper::l('st_shifted');
            case Document::STATE_FAIL:
                return Helper::l('st_fail');
            case Document::STATE_INPROCESS:
                return Helper::l('st_inprocess');
            case Document::STATE_READYTOSHIP:
                return Helper::l('st_rdshipment');

            default:
                return Helper::l('st_unknow');
        }
    }

    public static function getStateList() {
        $list = array();
        $list[Document::STATE_NEW] = Helper::l('st_new');
        $list[Document::STATE_EDITED] = Helper::l('st_edit');
        $list[Document::STATE_CANCELED] = Helper::l('st_canceled');
        $list[Document::STATE_EXECUTED] = Helper::l('st_executed');
        $list[Document::STATE_CLOSED] = Helper::l('st_closed');
        $list[Document::STATE_APPROVED] = Helper::l('st_approved');
        $list[Document::STATE_WA] = Helper::l('st_wa');
        $list[Document::STATE_INSHIPMENT] = Helper::l('st_inshipment');
        $list[Document::STATE_FINISHED] = Helper::l('st_finished');
        $list[Document::STATE_DELIVERED] = Helper::l('st_delivered');
        $list[Document::STATE_REFUSED] = Helper::l('st_refused');
        $list[Document::STATE_SHIFTED] = Helper::l('st_shifted');
        $list[Document::STATE_FAIL] = Helper::l('st_fail');
        $list[Document::STATE_INPROCESS] = Helper::l('st_inprocess');
        $list[Document::STATE_READYTOSHIP] = Helper::l('st_rdshipment');

        return $list;
    }

    /**
     * проверка  номера  на  уникальность
     *
     */
    public function checkUniqueNumber() {
        $this->document_number = trim($this->document_number);
        $branch = "";
        if ($this->branch_id > 0) {
            $branch = " and branch_id=" . $this->branch_id;
        }
        $doc = Document::getFirst("meta_id={$this->meta_id}  and  document_number = '{$this->document_number}' {$branch}");
        if ($doc instanceof Document) {
            if ($this->document_id != $doc->document_id) {
                return false;
            }
        }
        return true;
    }

    public function nextNumber($branch_id = 0) {

        $conn = \ZDB\DB::getConnect();
        $branch = "";
        if ($this->branch_id > 0) {
            $branch = " and branch_id=" . $this->branch_id;
        }
        if ($branch_id > 0) {
            $branch = " and branch_id=" . $branch_id;
        }

        $sql = "select document_number from  documents  where   meta_id='{$this->meta_id}'   {$branch}  order  by document_id desc limit 0,1";
        $prevnumber = $conn->GetOne($sql);
        if (strlen($prevnumber) == 0) {
            $prevnumber = $this->getNumberTemplate();
        }

        if (strlen($prevnumber) == 0) {
            return '';
        }
        $number = preg_replace('/[^0-9]/', '', $prevnumber);
        if (strlen($number) == 0) {
            $number = 0;
        }

        $letter = preg_replace('/[0-9]/', '', $prevnumber);
        for ($i = 0; $i < 10; $$i++) {
            $next = $letter . sprintf("%05d", ++$number);

            $ch = $conn->GetOne("select count(*) from documents     where   meta_id='{$this->meta_id}'   {$branch} and document_number=" . $conn->qstr($next));
            if ($ch == 0) {
                return $next;
            }
        }


        return '';
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
        //   $hasPayment = $conn->GetOne("select count(*)  from paylist where   document_id=" . $this->document_id);

        $conn->Execute("delete from docstatelog where document_id=" . $this->document_id);

        $conn->Execute("delete from messages where item_type=" . \App\Entity\Message::TYPE_DOC . " and item_id=" . $this->document_id);
        $conn->Execute("delete from files where item_type=" . \App\Entity\Message::TYPE_DOC . " and item_id=" . $this->document_id);
        $conn->Execute("delete from filesdata where   file_id not in (select file_id from files)");

        //   if(System::getUser()->userlogin =='admin') return;
        if ($hasExecuted) {

            $n = new \App\Entity\Notify();
            $n->user_id = \App\Entity\Notify::SYSTEM;

            $n->message = Helper::l('deleteddoc', System::getUser()->username, $this->document_number);
            $n->save();
        }
    }

    /**
     *
     *  запись состояния в  лог документа
     * @param mixed $state
     */
    public function insertLog($state) {
        $conn = \ZDB\DB::getConnect();
        $host = $conn->qstr($_SERVER["REMOTE_ADDR"]);
        $user = \App\System::getUser();

        $sql = "insert into docstatelog (document_id,user_id,createdon,docstate,hostname) values({$this->document_id},{$user->user_id},now(),{$state},{$host})";
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
        foreach ($rc as $row) {
            $row['createdon'] = strtotime($row['createdon']);
            $states[] = new \App\DataItem($row);
        }

        return $states;
    }

    /**
     *  проверка  был ли документ в  таких состояниях
     *
     * @param mixed $states
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

    public static function getConstraint() {
        $c = \App\ACL::getBranchConstraint();
        $user = System::getUser();
        if ($user->rolename != 'admins') {
            if (strlen($c) == 0) {
                $c = "1=1 ";
            }
            if ($user->onlymy == 1) {

                $c .= " and user_id  = " . $user->user_id;
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

        return $conn->GetOne("select coalesce(sum(amount),0) from paylist where   document_id = {$this->document_id}  ");
    }

    /**
     * put your comment there...
     *
     */
    public function hasEntry() {
        $conn = \ZDB\DB::getConnect();

        return $conn->GetOne("select coalesce(sum(amount),0) from paylist where   document_id = {$this->document_id}  ");
    }

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
     * распаковываем данные  детализации
     *
     */
    public function unpackDetails($dataname) {
        $list = @unserialize(@base64_decode($this->headerdata[$dataname]));
        if (is_array($list)) {
            return $list;
        } else {
            return array();
        }
    }

    public function packDetails($dataname, $list) {
        $data = base64_encode(serialize($list));
        $this->headerdata[$dataname] = $data;
        //для поиска
        $s = array();
        foreach ($list as $it) {
            if (strlen($it->itemname) > 0) {
                $s[] = $it->itemname;
            }
            if (strlen($it->item_code) > 0) {
                $s[] = $it->item_code;
            }
            if (strlen($it->bar_code) > 0) {
                $s[] = $it->bar_code;
            }
            if (strlen($it->service_name) > 0) {
                $s[] = $it->service_name;
            }

        }
        $this->headerdata["__searchdata__"] = serialize($s);
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
        $list[self::DEL_SELF] = Helper::l('delself');
        $list[self::DEL_BOY] = Helper::l('delboy');
        if ($np == true) {
            $list[self::DEL_NP] = Helper::l('delnp');
        }

        $list[self::DEL_SERVICE] = Helper::l('delservice');

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
            $mail->setFrom($_config['smtp']['user'], '');
            $mail->addAddress($customer->email);
            $mail->Subject = $doc->getEmailSubject();
            $mail->msgHTML($doc->getEmailBody());
            $mail->CharSet = "UTF-8";
            $mail->IsHTML(true);
            $mail->AddAttachment($f, $filename, 'base64', 'application/pdf');
            if ($mail->send() === false) {
                System::setErrorMsg($mail->ErrorInfo);
            } else {
                System::setSuccessMsg(Helper::l('email_sent'));
            }
        } catch(\Exception $e) {
            System::setErrorMsg($e->getMessage());
        }


        // @unlink($f);
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
        $sql = "select coalesce(sum(amount),0) from paylist where   document_id=" . $this->document_id;
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
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $img = '<img style="max-width:200px" src="data:image/png;base64,' . base64_encode($generator->getBarcode($this->document_number, 'C128')) . '">';

        return $img;
    }

    /**
     * возвращает  тэг <img> со QR кодом ссылки на  документ
     *
     */
    protected function getQRCodeImage() {
        $print = System::getOption('common', 'printoutqrcode');
        if ($print == 0) {
            return '';
        }
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        
        $url = _BASEURL . "?p=App/Pages/Register/DocList&arg=" . $this->document_id;
        $qrCode = new \Endroid\QrCode\QrCode($url);
        $qrCode->setSize(100);
        $qrCode->setMargin(5);
       // $qrCode->setWriterByName('png');

        $result = $writer->write($qrCode );
     
        $dataUri = $result->getDataUri();
        $img = "<img   src=\"{$dataUri}\"  />";

        return $img;
    }

    /**
     * проверка  может  ли  быть  отменен
     * Возвращает  текст ошибки если  нет
     */
    public function canCanceled() {
        return "";
    }

     public   function getID() {
        return $this->document_id;
    }  
    
    
    public  function getAmountReg(){
        $am=$this->amount;
        if($this->payamount <> 0) {
           $am=$this->payamount;    
        }
        
        return  $am;
    }
    
}
