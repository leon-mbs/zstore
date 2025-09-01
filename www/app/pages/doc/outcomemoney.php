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
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\AutocompleteTextInput;
use App\Entity\Customer;
use App\Entity\Employee;

/**
 * Страница    расходный ордер
 */
class OutcomeMoney extends \App\Pages\Base
{
    private $_doc;

   /**
    * @param mixed $docid     редактирование
    * @param mixed $pcustomer_id  контрагент (для  нового документа)
    * @param mixed $pamount       сума
    * @param mixed $det           детализация
    * @return mixed
    */
    public function __construct($docid = 0, $pcustomer_id = 0, $pamount = 0,$det=2) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('detail', array(), 2))->onChange($this, 'OnDetail');
        $this->docform->add(new DropDownChoice('mtype', \App\Entity\IOState::getTypeListOutM(),0));

        $this->docform->add(new DropDownChoice('contract', array(), 0));
        $this->docform->add(new DropDownChoice('emp', Employee::findArray('emp_name', 'disabled<>1', 'emp_name'), 0));

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new TextInput('amount'));
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnCustomer');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->detail->setValue($this->_doc->headerdata['detail']);

            if($this->_doc->headerdata['detail']==1 || $this->_doc->headerdata['detail']==2 ) {
               $this->docform->customer->setKey($this->_doc->customer_id);
               $this->docform->customer->setText($this->_doc->customer_name);
            }
            if($this->_doc->headerdata['detail']==3) {
               $this->docform->emp->setValue($this->_doc->headerdata['emp']);
            }
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->mtype->setValue($this->_doc->headerdata['type']);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->amount->setText(H::fa($this->_doc->amount));
        } else {
            $this->_doc = Document::create('OutcomeMoney');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($pcustomer_id > 0) {
                $c = Customer::load($pcustomer_id);
                $this->docform->customer->setKey($c->customer_id);
                $this->docform->customer->setText($c->customer_name);
                $this->docform->amount->setText(H::fa($pamount));
                $this->docform->mtype->setValue(\App\Entity\IOState::TYPE_BASE_OUTCOME);
                $this->docform->detail->setValue($det ==-1 ?2:$det );
            }

        }


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
        $this->OnDetail($this->docform->detail);
        $this->OnCustomer($this->docform->customer);
        $this->docform->contract->setValue($this->_doc->headerdata['contract_id']);
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['paymentname'] = $this->docform->payment->getValueName();
        $this->_doc->headerdata['type'] = $this->docform->mtype->getValue();
        $this->_doc->headerdata['detail'] = $this->docform->detail->getValue();
        $this->_doc->headerdata['contract_id'] = $this->docform->contract->getValue();
        $this->_doc->headerdata['contract_number'] = $this->docform->contract->getValueName();

        $this->_doc->headerdata['emp'] = $this->docform->emp->getValue();
        $this->_doc->headerdata['emp_name'] = $this->docform->emp->getValueName();

        $this->_doc->amount = H::fa($this->docform->amount->getText());
        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->customer_id = $this->docform->customer->getKey();
        $this->_doc->payed = 0;
        $this->_doc->payamount = $this->_doc->amount;
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

        if (($this->_doc->amount > 0) == false) {
            $this->setError("Не введено суму");
        }
        if ($this->docform->mtype->getValue() == 0) {
            $this->setError("Не обрано тип витрат");
        }


        if ($this->docform->detail->getValue() == 1 || $this->docform->detail->getValue() == 2) {

            if ($this->_doc->customer_id == 0) {
                $this->setError("Не задано контрагента");
            }
        }
        if ($this->docform->detail->getValue() == 3) {

            if ($this->_doc->headerdata['emp'] == 0) {
                $this->setError("Не обрано співробітника");
            }
        }

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText());
    }

    public function OnCustomer($sender) {
        $c = $this->docform->customer->getKey();

        $ar = \App\Entity\Contract::getList($c);
        $this->docform->contract->setOptionList($ar);
    }

    public function OnDetail($sender) {
        $this->docform->emp->setVisible(false);
        $this->docform->customer->setVisible(false);
        $this->docform->contract->setVisible(false);
        $this->docform->mtype->setVisible(false);
        if ($sender->getValue() == 1 || $sender->getValue() == 2) {
            $this->docform->contract->setVisible(true);
            $this->docform->customer->setVisible(true);
            $this->docform->mtype->setValue(\App\Entity\IOState::TYPE_BASE_OUTCOME) ;            
         }
        if ($sender->getValue() == 3) {
            $this->docform->emp->setVisible(true);
            $this->docform->mtype->setValue(\App\Entity\IOState::TYPE_COMMON_OUTCOME) ;            
        }
        if ($sender->getValue() == 0   ) {
            $this->docform->mtype->setVisible(true);
            $this->docform->mtype->setValue(0) ;            
        }        
    }

}
