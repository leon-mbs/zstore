<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\MoneyFund;
use App\Entity\Pay;
use App\Helper as H;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\AutocompleteTextInput;
use App\Entity\Customer;
use App\Entity\Employee;

/**
 * Страница   перемещение  денег
 */
class MoveMoney extends \App\Pages\Base
{
    private $_doc;

    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));
        $balance = MoneyFund::Balance();

        $list = array();
        foreach (MoneyFund::getList() as $id => $mf) {
            $list[$id] = $mf . ", " . H::fa($balance[$id]);
        }

        $this->docform->add(new DropDownChoice('paymentfrom', $list, H::getDefMF()));
        $this->docform->add(new DropDownChoice('paymentto', $list, H::getDefMF()));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('amount'));
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->paymentfrom->setValue($this->_doc->headerdata['paymentfrom']);
            $this->docform->paymentto->setValue($this->_doc->headerdata['paymentto']);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->amount->setText($this->_doc->amount);
        } else {
            $this->_doc = Document::create('MoveMoney');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['paymentto'] = $this->docform->paymentto->getValue();
        $this->_doc->headerdata['paymenttoname'] = $this->docform->paymentto->getValueName();
        $this->_doc->headerdata['paymentfrom'] = $this->docform->paymentfrom->getValue();
        $this->_doc->headerdata['paymentfromname'] = $this->docform->paymentfrom->getValueName();

        $this->_doc->amount = H::fa($this->docform->amount->getText());
        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->payment = 0;
        $this->_doc->payed = 0;
        if ($this->checkForm() == false) {
            return;
        }

        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();

            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            $conn->CommitTrans();
            App::Redirect("\\App\\Pages\\Register\\PayList");
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());
            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );


        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (strlen($this->_doc->document_number) == 0) {
            $this->setError("Введіть номер документа");
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }

        if (($this->_doc->amount > 0) == false) {
            $this->setError("Не введено суму");
        }

        if ($this->_doc->headerdata['paymentto'] == 0 || $this->_doc->headerdata['paymentfrom'] == 0) {
            $this->setError("Не обрано рахунок");
        }
        if ($this->_doc->headerdata['paymentto'] == $this->_doc->headerdata['paymentfrom']) {
            $this->setError("Рахунки однакові");
        }

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

}
