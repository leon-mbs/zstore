<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\TextArea;
use App\Entity\Firm;
use App\Entity\Customer;
use App\Entity\Employee;

/**
 * Страница   договор
 */
class Contract extends \App\Pages\Base
{

    private $_doc;

    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));
 
 
   
        $this->docform->add(new Date('enddate', strtotime("+1 month", time())));
        
        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new \Zippy\Html\Form\File('scan'));
     
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->add(new DropDownChoice('comp', Firm::findArray('firm_name', 'disabled<>1', 'firm_name'), H::getDefFirm()));
        $this->docform->add(new DropDownChoice('emp', Employee::findArray('emp_name', 'disabled<>1', 'emp_name'), 0));

        
        
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        


        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->enddate->setDate($this->_doc->headerdata['enddate']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->emp->setValue($this->_doc->headerdata['emp']);
            $this->docform->comp->setValue($this->_doc->firm_id);
 
            $this->docform->notes->setText($this->_doc->notes);
        
        } else {
            $this->_doc = Document::create('Contract');
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

        $this->_doc->firm_id = $this->docform->comp->getValue();
        $this->_doc->headerdata['emp'] = $this->docform->emp->getValue();
        $this->_doc->headerdata['emp_name'] = $this->docform->emp->getValueName();

     
        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date =  $this->docform->document_date->getDate() ;
        $this->_doc->headerdata['enddate'] =  $this->docform->enddate->getDate() ;
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->checkForm() == false) {
            return;
        }
        $file = $this->docform->scan->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("filemore10M");
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
                $this->_doc->updateStatus(Document::STATE_INPROCESS);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }       
            
            if ($file['size'] > 0) {
                $id = H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
                $imagedata = getimagesize($file["tmp_name"]);
                if ($imagedata[0] > 0) {
                    $this->_doc->headerdata["scan"] = $id;
                    $this->_doc->save();
                }
            }            
            $conn->CommitTrans();
             App::Redirect("\\App\\Pages\\Register\\ContractList");
        } catch(\Throwable $ee) {
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
            $next = $this->_doc->nextNumber() ;
            $this->docform->document_number->setText($next);
            $this->_doc->document_number =  $next;
            if(strlen($next)==0) {
                $this->setError('docnumbercancreated');    
            }
        }

        if (($this->_doc->customer_id > 0) == false) {
            $this->setError("noselcust");
        }
       if (($this->_doc->firm_id > 0) == false) {
            $this->setError("noselfirm");
        }
    

        return !$this->isError();
    }

    

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 2);
    }
}
