<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Employee;
use App\Entity\MoneyFund;
use App\Helper as H;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;

/**
 * Страница   выплата  зарплаты
 */
class OutSalary extends \App\Pages\Base
{

    private $_doc;
    public $_list = array();

    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new Label('total'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new DropDownChoice('year', \App\Util::getYears(), round(date('Y'))));
        $this->docform->add(new DropDownChoice('month', \App\Util::getMonth(), round(date('m'))));
        $this->docform->add(new TextInput('notes'));


        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');


        $this->_list = Employee::find('disabled<>1', 'emp_name');


        if ($docid > 0) {    //загружаем   содержимок  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->year->setValue($this->_doc->headerdata['year']);
            $this->docform->month->setValue($this->_doc->headerdata['month']);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->total->setText(H::fa($this->_doc->amount));
            $this->_list = $this->_doc->unpackDetails('detaildata');

        } else {
            $this->_doc = Document::create('OutSalary');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->docform->add(new DataView('elist', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_list')), $this, 'employeelistOnRow'))->Reload();

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function employeelistOnRow($row) {
        $emp = $row->getDataItem();
        $row->add(new Label('emp_name', $emp->emp_name));
        $row->add(new TextInput('amount', new \Zippy\Binding\PropertyBinding($emp, 'amount')));

    }


    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['year'] = $this->docform->year->getValue();
        $this->_doc->headerdata['month'] = $this->docform->month->getValue();
        $this->_doc->headerdata['monthname'] = $this->docform->month->getValueName();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['paymentname'] = $this->docform->payment->getValueName();


        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());

        $this->_doc->packDetails('detaildata', $this->_list);
        $this->_doc->amount = 0;
        foreach ($this->_list as $emp) {
            if ($emp->amount > 0) {
                $this->_doc->amount += $emp->amount;
            }
        }
        if ($this->_doc->amount == 0) {
            $this->setError("noentersum");
            return;
        }
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
            $this->setError("enterdocnumber");
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $this->docform->document_number->setText($this->_doc->nextNumber());
            $this->setError('nouniquedocnumber_created');
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

}
