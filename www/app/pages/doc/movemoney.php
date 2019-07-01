<?php

namespace App\Pages\Doc;

use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\Doc\Document;
use \App\Entity\MoneyFund;
use \App\Application as App;
use \App\Helper as H;

/**
 * Страница  ввода перемещения денег
 */
class MoveMoney extends \App\Pages\Base {

    private $_doc;

    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('mffrom', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new DropDownChoice('mfto', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('amount'));

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');


        if ($docid > 0) {    //загружаем   содержимок  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->mffrom->setValue($this->_doc->headerdata['mffrom']);
            $this->docform->mfto->setValue($this->_doc->headerdata['mfto']);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->amount->setText($this->_doc->amount);
        } else {
            $this->_doc = Document::create('MoveMoney');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }


        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function savedocOnClick($sender) {

        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['mffrom'] = $this->docform->mffrom->getValue();
        $this->_doc->headerdata['mffromname'] = $this->docform->mffrom->getValueName();
        $this->_doc->headerdata['mfto'] = $this->docform->mfto->getValue();
        $this->_doc->headerdata['mftoname'] = $this->docform->mfto->getValueName();

        $this->_doc->amount = $this->docform->amount->getText();
        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());

        if ($this->checkForm() == false) {
            return;
        }

        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            $conn->CommitTrans();
            App::RedirectBack();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return;
        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (strlen($this->_doc->document_number) == 0) {
            $this->setError("Не введен номер документа");
        }

        if ($this->docform->mfto->getValue() == $this->docform->mffrom->getValue()) {
            $this->setError("Выбран тот  же счет получатель");
        }
        if (($this->_doc->amount > 0) == false) {
            $this->setError("Не введена сумма");
        }

        return !$this->isError();
    }
   
    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

}
