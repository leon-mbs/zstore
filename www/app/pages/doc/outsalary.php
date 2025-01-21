<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Employee;
use App\Entity\MoneyFund;
use App\Entity\EmpAcc;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\SubmitLink;
use Zippy\Binding\PropertyBinding as Bind;

/**
 * Страница   выплата  зарплаты
 */
class OutSalary extends \App\Pages\Base
{
    private $_doc;
    public $_list = array();

    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new DropDownChoice('year', \App\Util::getYears(), round(date('Y'))));
        $this->docform->add(new DropDownChoice('month', \App\Util::getMonth(), round(date('m'))));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new CheckBox('advance'));
        $this->docform->add(new SubmitButton('tolist'))->onClick($this, 'tolistOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->add(new Form('listform'))->setVisible(false);

        $this->listform->add(new SubmitLink('edel'))->onClick($this, 'delOnClick');
        $this->listform->add(new SubmitLink('addemp'))->onClick($this, 'addOnClick');
        $this->listform->add(new DropDownChoice('newemp'));
        $this->listform->add(new Label('total'));


        $this->listform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->listform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->listform->add(new SubmitButton('todoc'))->onClick($this, 'todocOnClick');

       // $this->_list = Employee::find('disabled<>1', 'emp_name');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->year->setValue($this->_doc->headerdata['year']);
            $this->docform->month->setValue($this->_doc->headerdata['month']);
            $this->docform->advance->setChecked($this->_doc->headerdata['advance']);
            $this->docform->year->setAttribute('disabled','disabled');
            $this->docform->month->setAttribute('disabled','disabled');
       //     $this->docform->advance->setAttribute('disabled','disabled');
            $this->docform->notes->setText($this->_doc->notes);
            $this->listform->total->setText(H::fa($this->_doc->amount));
            $this->_list = $this->_doc->unpackDetails('detaildata');

        } else {
            $this->_doc = Document::create('OutSalary');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->listform->add(new DataView('elist', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_list')), $this, 'employeelistOnRow'));

     //   $this->Reload();


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function employeelistOnRow($row) {
        $emp = $row->getDataItem();
        $row->add(new Label('emp_name', $emp->emp_name));

        $row->add(new TextInput('amount', new Bind($emp, 'amount')));
        $row->add(new CheckBox('emp_ch', new Bind($emp, '_ch')));

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
        $this->_doc->headerdata['advance'] = $this->docform->advance->isChecked() ? 1 : 0;

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
            $this->setError("Не введено суму");
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
            App::Redirect("\\App\\Pages\\Register\\SalaryList");

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );

            return;
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


        return !$this->isError();
    }


    public function todocOnClick($sender) {
        $this->listform->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function tolistOnClick($sender) {
        $this->listform->setVisible(true);
        $this->docform->setVisible(false);
        
        if ($this->_doc->document_id == 0) {
            $this->_list =[];

            if ($this->docform->advance->isChecked()) {
                $this->_list = Employee::find('disabled<>1', 'emp_name');
                
                foreach ($this->_list as $emp) {
                    $emp->amount = $emp->advance;
                }
            } else {
                $y = $this->docform->year->getValue();
                $m = $this->docform->month->getValue();


                $rows = EmpAcc::getForPay($y,$m);
                foreach ($rows as $row) {
                    $emp= Employee::load($row['emp_id']) ;
                    $emp->amount = H::fa($row['am']);
                    $this->_list[$row['emp_id']] = $emp;


                }

            }
        }


        $this->Reload();
    }


    public function Reload() {
        $opt = System::getOptions("salary");

        $this->listform->elist->Reload();
        $this->updateAddList();


    }

    public function updateAddList() {

        $ids = array_keys($this->_list);
        $list = array();
        foreach (Employee::findArray('emp_name', 'disabled<>1', 'emp_name') as $id => $name) {
            if (in_array($id, $ids) == false) {
                $list[$id] = $name;
            }
        }
        $this->listform->newemp->setOptionList($list);
        $this->listform->newemp->setValue(0);
    }

    public function delOnClick($sender) {
        $_list = array();
        foreach ($this->_list as $id => $e) {
            if ($e->_ch == true) {
                continue;
            }
            $_list[$id] = $e;
        }

        $this->_list = $_list;
        $this->Reload();

    }

    public function addOnClick($sender) {
        $id = $this->listform->newemp->getValue();
        if ($id > 0) {
            $this->_list[$id] = Employee::load($id);
            if ($this->docform->advance->isChecked()) {
                $this->_list[$id]->amount = $this->_list[$id]->advance;
            } else {
                $y = $this->docform->year->getValue();
                $m = $this->docform->month->getValue();

                $rows = EmpAcc::getForPay($y,$m);
                foreach ($rows as $row) {
                    if ($id == $row['emp_id']) {
                        $this->_list[$row['emp_id']]->amount = H::fa($row['am']);
                    }
                }
            }


        }
        $this->Reload();
    }


    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

}
