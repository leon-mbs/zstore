<?php

namespace App\Entity\Doc;

use App\System;
use App\Helper;

/**
 * Класс-сущность документ
 *
 */
class Document extends \ZCL\DB\Entity
{

    // состояния  документа
    const STATE_NEW = 1;     //Новый
    const STATE_EDITED = 2;  //Отредактирован
    const STATE_CANCELED = 3;      //Отменен
    const STATE_EXECUTED = 5;      // Проведен, выполнен
    const STATE_APPROVED = 4;      //  Утвержден
    const STATE_DELETED = 6;       //  Удален
    const STATE_INPROCESS = 7; // в  работе
    const STATE_WA = 8; // ждет подтверждения
    const STATE_CLOSED = 9; // Закрыт , доставлен, выполнен
    const STATE_WP = 10; // Ждет оплату
    const STATE_INSHIPMENT = 11; // Отгружен
    const STATE_PAYED = 12; // оплачен
    const STATE_PART_PAYED = 13; // частично оплачен
    const STATE_DELIVERED = 14; // доставлен
    const STATE_REFUSED = 15; // отклонен
    const STATE_SHIFTED = 16; // отложен
    // типы  экспорта
    const EX_WORD = 1; //  Word
    const EX_EXCEL = 2;    //  Excel
    const EX_PDF = 3;    //  PDF

    // const EX_XML_GNAU = 4;

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
    public $detaildata = array();

    /**
     * массив статусов
     * 
     * @var mixed
     */
    private $logdata = array();

    protected function init() {
        $this->document_id = 0;
        $this->state = 0;
        $this->customer_id = 0;
        $this->datatag = 0;
        $this->document_number = '';
        $this->notes = '';

        $this->document_date = time();
        $this->user_id = \App\System::getUser()->user_id;

        $this->basedoc = '';
        $this->headerdata = array();
        $this->headerdata['incredit'] = 0; //оплата в  долг
        $this->headerdata['inshipment'] = 0; //товары в  пути
        $this->headerdata['planned'] = 0; //запланированный
    }

    protected static function getMetadata() {
        return array('table' => 'documents', 'view' => 'documents_view', 'keyfield' => 'document_id');
    }

    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
        $this->unpackData();
    }

    protected function beforeSave() {
        $this->document_number = trim($this->document_number);
        $this->packData();
        $doc = Document::getFirst("   document_number = '{$this->document_number}' ");
        if ($doc instanceof Document) {
            if ($this->document_id != $doc->document_id) {

                throw new \Exception('Не уникальный номер документа ');
                return false;
            }
        }
        //todo  отслеживание  изменений
    }

    /**
     * Упаковка  данных  в  XML
     *
     */
    private function packData() {


        $this->content = "<doc><header>";

        foreach ($this->headerdata as $key => $value) {
            if ($key > 0)
                continue;

            if (strpos($value, '[CDATA[') !== false) {
                \App\System::getWarnMsg('CDATA в  поле  обьекта');
                \App\Helper::log(' CDATA в  поле  обьекта');
                continue;
            }

            if (is_numeric($value) || strlen($value) == 0) {
                $value = $value;
            } else {
                $value = "<![CDATA[" . $value . "]]>";
            }
            $this->content .= "<{$key}>{$value}</{$key}>";
        }
        $this->content .= "</header><detail>";
        foreach ($this->detaildata as $row) {
            $this->content .= "<row>";
            foreach ($row as $key => $value) {
                if ($key > 0)
                    continue;

                if (strpos($value, '[CDATA[') !== false) {
                    \App\System::getWarnMsg('CDATA в  поле  обьекта');
                    \App\Helper::log(' CDATA в  поле  обьекта');
                    continue;
                }


                if (is_numeric($value) || strlen($value) == 0) {
                    $value = $value;
                } else {
                    $value = "<![CDATA[" . $value . "]]>";
                }

                $this->content .= "<{$key}>{$value}</{$key}>";
            }

            $this->content .= "</row>";
        }
        $this->content .= "</detail> ";
        $this->content .= "<states> ";
        foreach ($this->logdata as $state) {
            $this->content .= "<staterow>";
            $this->content .= "<stateno>{$state->state}</stateno>";
            $this->content .= "<stateuser>{$state->user}</stateuser>";
            $this->content .= "<stateusername><![CDATA[{$state->username}]]></stateusername>";
            $this->content .= "<statehost>{$state->hostname}</statehost>";
            $this->content .= "<statedt>{$state->updatedon}</statedt>";

            $this->content .= "</staterow>";
        }
        $this->content .= "</states></doc>";
    }

    /**
     * распаковка из  XML
     *
     */
    private function unpackData() {

        $this->headerdata = array();
        if (strlen($this->content) == 0) {
            return;
        }
        $xml = new \SimpleXMLElement($this->content);
        foreach ($xml->header->children() as $child) {
            $this->headerdata[(string) $child->getName()] = (string) $child;
        }
        $this->detaildata = array();
        foreach ($xml->detail->children() as $row) {
            $_row = array();
            foreach ($row->children() as $item) {
                $_row[(string) $item->getName()] = (string) $item;
            }
            $this->detaildata[] = $_row;
        }
        $this->logdata = array();
        $result = $xml->xpath('//states');

        if ((bool) (count($result))) {
            foreach ($xml->states->children() as $row) {
                $state = new \App\DataItem();
                $state->state = (int) $row->stateno;
                $state->hostname = (string) $row->statehost;
                $state->user = (int) $row->stateuser;
                $state->username = (string) $row->stateusername;
                $state->updatedon = (int) $row->statedt;
                $this->logdata[] = $state;
            }
        }
    }

    /**
     * Генерация HTML  для  печатной формы
     *
     */
    public function generateReport() {
        return "";
    }

    /**
     * Выполнение документа - обновление склада, бухгалтерские проводки и  т.д.
     *
     */
    public function Execute() {

        if (trim(get_class($this), "\\") == 'App\Entity\Doc\Document') {
            //если  екземпляр  базового типа Document приводим  к  дочернему  типу
            $this->cast()->Execute();
        }
    }

    /**
     * Отмена  документа
     *
     */
    protected function Cancel() {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();
        // если  метод не переопределен  в  наследнике удаляем  документ  со  всех  движений
        $conn->Execute("delete from entrylist where document_id =" . $this->document_id);

        $conn->CompleteTrans();



        return true;
    }

    /**
     * создает  экземпляр  класса  документа   в   соответсии  с  именем  типа
     *
     * @param mixed $classname
     */
    public static function create($classname) {
        $arr = explode("\\", $classname);
        $classname = $arr[count($arr) - 1];
        $conn = \ZDB\DB::getConnect();
        $sql = "select meta_id from  metadata where meta_type=1 and meta_name='{$classname}'";
        $meta = $conn->GetRow($sql);
        $fullclassname = '\App\Entity\Doc\\' . $classname;

        $doc = new $fullclassname();
        $doc->meta_id = $meta['meta_id'];
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

    protected function beforeDelete() {



        return true;
    }

    protected function afterSave($update) {

        //  if ($update == false) {   //новый  документ
        //    $this->updateStatus(self::STATE_NEW);
        // }
        // else {
        //    if ($this->state == self::STATE_NEW)
        //    $this->updateStatus(self::STATE_EDITED);
        //  }
    }

    /**
     * добавление связанного  документа
     *
     * @param mixed $id
     */
    public function AddConnectedDoc($id) {
        if ($id > 0) {
            $conn = \ZDB\DB::getConnect();
            $conn->Execute("delete from docrel  where (doc1={$this->document_id} and doc2={$id} )  or (doc2={$this->document_id} and doc1={$id})");
            $conn->Execute("insert  into docrel (doc1,doc2) values({$id},{$this->document_id})");
        }
    }

    /**
     * удаление  связанного  документа
     *
     * @param mixed $id
     */
    public function RemoveConnectedDoc($id) {
        if ($id > 0) {
            $conn = \ZDB\DB::getConnect();
            $conn->Execute("delete from  docrel  where (doc1={$this->document_id} and doc2={$id} )  or (doc2={$this->document_id} and doc1={$id})");
        }
    }

    /**
     * список  связанных  документов
     *
     */
    public function ConnectedDocList() {

        $where = "document_id in (select doc1 from  docrel where doc2={$this->document_id}) or document_id in (select doc2 from  docrel where doc1={$this->document_id})";
        return Document::find($where);
    }

    /**
     * Обновляет состояние  документа
     *
     * @param mixed $state
     */
    public function updateStatus($state) {


        if ($this->document_id == 0)
            return false;

        if ($state == self::STATE_CANCELED) {
            $this->Cancel();
        }
        if ($state == self::STATE_EXECUTED) {
            $this->Execute();
        }

        $this->state = $state;
        $this->insertLog($state);
        $this->save();

        //  $conn = \ZDB\DB::getConnect();
        //   $sql = "update documents set  state={$this->state}  where document_id = {$this->document_id}";
        //   $conn->Execute($sql);



        return true;
    }

    /**
     * Возвращает название  статуса  документа
     *
     * @param mixed $state
     * @return mixed
     */
    public static function getStateName($state) {

        switch ($state) {
            case Document::STATE_NEW:
                return "Новый";
            case Document::STATE_EDITED:
                return "Отредактирован";
            case Document::STATE_CANCELED:
                return "Отменен";
            case Document::STATE_EXECUTED:
                return "Проведен";
            case Document::STATE_CLOSED:
                return "Закрыт";
            case Document::STATE_APPROVED:
                return "Утвержден";
            case Document::STATE_DELETED:
                return "Удален";
            case Document::STATE_WP:
                return "Ожидает оплату";
            case Document::STATE_WA:
                return "Ожидает утверждения";
            case Document::STATE_INSHIPMENT:
                return "В доставке";
            case Document::STATE_PAYED:
                return "Оплачен";
            case Document::STATE_PART_PAYED:
                return "Частично оплачен";
            case Document::STATE_DELIVERED:
                return "Доставлен";
            case Document::STATE_REFUSED:
                return "Отклонен";
            case Document::STATE_SHIFTED:
                return "Отложен";
            case Document::STATE_INPROCESS:
                return "Выполняется";
            default:
                return "Неизвестный статус";
        }
    }

    /**
     * Возвращает  следующий  номер  при  автонумерации
     *
     */
    public function nextNumber() {


        $class = explode("\\", get_called_class());
        $metaname = $class[count($class) - 1];
        $doc = Document::getFirst("meta_name='" . $metaname . "'", "document_id desc");
        if ($doc == null)
            return '';
        $prevnumber = $doc->document_number;
        if (strlen($prevnumber) == 0)
            return '';
        $number = preg_replace('/[^0-9]/', '', $prevnumber);
        if (strlen($number) == 0)
            $number = 0;

        $letter = preg_replace('/[0-9]/', '', $prevnumber);

        return $letter . sprintf("%05d", ++$number);
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
     * Список  доступных сстояний в зависимости  от текузего
     * может  переружатся  для  уточнения  в  зависимости  от типа  документа
     */
    /* public function getStatesList() {
      $list = array();
      if ($this->state == self::STATE_CANCELED || $this->state == self::STATE_EDITED || $this->state == self::STATE_NEW) {

      }

      return $list;
      } */

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
        ;
        $where = "state= " . Document::STATE_EXECUTED;

        if (strlen($type) > 0) {
            if ($type > 0) {
                $where = $where . " and  meta_id ={$type}";
            } else {
                $where = $where . " and  meta_name='{$type}'";
            }
        }

        if ($from > 0)
            $where = $where . " and  document_date >= " . $conn->DBDate($from);
        if ($to > 0)
            $where = $where . " and  document_date <= " . $conn->DBDate($to);
        foreach ($header as $key => $value) {
            $where = $where . " and  content like '%<{$key}>{$value}</{$key}>%'";
        }

        return Document::find($where);
    }

    /**
     * может быть удален
     * 
     */
    public function canDeleted() {
        $conn = \ZDB\DB::getConnect();

        $cnt = $conn->GetOne("select  count(*) from entrylist where  document_id = {$this->document_id}  ");
        if ($cnt > 0) {
            System::setErrorMsg("У докуинта  есть записи в аналитике");
            return false;
        }


        $cnt = $conn->GetOne("select  count(*) from docrel where  doc1 = {$this->document_id}  or  doc2 = {$this->document_id}");
        if ($cnt > 0) {
            System::setErrorMsg("Есть связаные документы");
            return false;
        }


        $f = $this->checkStates(array(Document::STATE_PAYED, Document::STATE_PART_PAYED, Document::STATE_INSHIPMENT, Document::STATE_DELIVERED));
        if ($f) {
            System::setErrorMsg("У документа были оплаты или доставки");
            return false;
        }

        return true;
    }

    /**
     * может быть отменен
     * 
     */
    public function canCanceled() {
        $f = $this->checkStates(array(Document::STATE_CLOSED, Document::STATE_PART_PAYED, Document::STATE_PART_PAYED, Document::STATE_INSHIPMENT, Document::STATE_DELIVERED));
        if ($f) {
            System::setWarnMsg("У документа были оплаты или доставки");
            return true;
        }
        return true;
    }

    //добавляет оплату
    public function addPayment($user, $amount, $comment = '') {
        $list = $this->getPayments();
        $item = new \App\DataItem();
        $item->user = $user;
        $item->amount = $amount;
        $item->comment = $comment;
        $item->date = time();
        $list[] = $item;

        $this->headerdata['pays'] = base64_encode(serialize($list));
    }

    //возвращает список оплат
    public function getPayments() {
        if (strlen($this->headerdata['pays']) > 0) {
            return @unserialize(base64_decode($this->headerdata['pays']));
        }

        return array();
    }

    /**
     *
     *  запись состояния в  лог документа
     * @param mixed $state
     */
    public function insertLog($state) {

        $host = Document::qstr($_SERVER["REMOTE_ADDR"]);
        $user = \App\System::getUser();

        $item = new \App\DataItem();
        $item->state = $state;
        $item->hostname = $host;
        $item->user = $user->user_id;
        $item->username = $user->username;
        $item->updatedon = time();
        $this->logdata[] = $item;
    }

    /**
     * список записей   в  логе   состояний
     *
     */
    public function getLogList() {



        return $this->logdata;
    }

    /**
     *  проверка  был ли документ в  таких состояниях
     * 
     * @param mixed $states
     */
    public function checkStates(array $states) {



        foreach ($this->logdata as $srow) {
            foreach ($states as $state) {
                if ($srow->state == $state)
                    return true;
            }
        }

        return false;
    }

}
