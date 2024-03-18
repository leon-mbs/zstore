<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\System;
use App\Entity\Doc\Document;
use App\Helper as H;
use Zippy\Html\Label;
use Zippy\Html\Panel;
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
use Zippy\Html\Link\SubmitLink;

/**
 * Страница   офисный документ
 */
class OfficeDoc extends \App\Pages\Base
{
    private $_doc;
    
    public function __construct($docid = 0,$copyid=0){
        parent::__construct();

        $conn = \ZDB\DB::getConnect();
        $names = $conn->GetCol("select distinct notes from documents_view where  meta_name='OfficeDoc' order  by notes");
       
  
        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('edittitle'));
        
        $this->docform->edittitle->setDataList($names);
        $this->docform->add(new TextArea('doccontent'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));
        $this->docform->add(new \ZCL\BT\Tags("doctags"));
        
        $userlist = \App\Entity\User::findArray("username", "disabled<>1", "username") ;
        $this->docform->add(new DropDownChoice("user",$userlist,0));


        $emplist = \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name") ;
        $this->docform->add(new DropDownChoice("emp",$emplist,0))->onChange($this,'onEmp');

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');
        
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new \Zippy\Html\Link\BookmarkableLink('cinfo'))->setVisible(false);
        
        
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editemail'));
        $this->editcust->add(new DropDownChoice('edittype'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');
        
        $user = System::getUser()->user_id;

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->edittitle->setText($this->_doc->notes);
            $customer_id= $this->_doc->headerdata['customer']??0 ;
            if($customer_id >0) {
                $this->docform->customer->setKey($this->_doc->headerdata['customer']??0);
                $this->docform->customer->setText($this->_doc->headerdata['customer_name']??'');
                $this->OnChangeCustomer($this->docform->customer);
                
            }
            $emp = $this->_doc->headerdata['employee']??0;
            if($emp>0) {
               $this->docform->emp->setValue($emp);
               $this->onEmp($this->docform->emp)  ;
            }

            $d = $this->_doc->unpackDetails('detaildata')  ;
            $this->docform->doccontent->setText($d['data']??''); 
            $this->docform->user->setValue($this->_doc->user_id); 
            
         } else if($copyid>0){
            $cdoc = Document::load($copyid)->cast();
            $this->docform->edittitle->setText($cdoc->notes); 
            $d= $cdoc->unpackDetails('detaildata')  ;
            $this->docform->doccontent->setText($d['data']??''); 
            $this->docform->user->setValue($user);
             
         
         } else {
            $this->_doc = Document::create('OfficeDoc');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            $this->docform->user->setValue($user);
         
        }
        if($this->_doc->document_id >0) {
           $this->docform->doctags->setTags(\App\Entity\Tag::getTags(\App\Entity\Tag::TYPE_OFFICEDCO,(int)$this->_doc->document_id));
        }
        $this->docform->doctags->setSuggestions(\App\Entity\Tag::getSuggestions(\App\Entity\Tag::TYPE_OFFICEDCO));
   


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->notes = $this->docform->edittitle->getText();
        if(strlen($this->_doc->notes)==0) {
            $this->setError('Не введено назву');
            return;            
        }
  
        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $data=$this->docform->doccontent->getText();
        if(strlen($data)==0) {
            $this->setError('Не введено текст');
            return;            
        }
        $this->_doc->packDetails('detaildata', array('data'=> $data));
                         
        $customer_id = $this->docform->customer->getKey();
        if($customer_id >0) {
            $this->_doc->headerdata['customer'] = $customer_id;  
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();  
        }                 
                         
                         
        $emp_id = $this->docform->emp->getValue();
        if($emp_id >0) {
            $this->_doc->headerdata['employee'] = $emp_id;  
            $this->_doc->headerdata['employee_name'] = $this->docform->emp->getValueName();  
        }                 
                         
                         
                                                                      
        $isEdited = $this->_doc->document_id > 0;
        if(!$isEdited) {
           $this->_doc->headerdata['author']= System::getUser()->user_id;   
        }
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
            
            $tags = $this->docform->doctags->getTags() ;
        
            \App\Entity\Tag::updateTags($tags,\App\Entity\Tag::TYPE_OFFICEDCO,(int)$this->_doc->document_id) ;
            
            
            $conn->CommitTrans();
            App::Redirect("\\App\\Pages\\Register\\OfficeList");
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());
            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);


        }
    }

 
    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        return \App\Entity\Customer::getList($sender->getText());
    }

    public function OnChangeCustomer($sender) {
   
        $customer_id = $this->docform->customer->getKey();
 
        $this->docform->addcust->setVisible(false) ;
        $this->docform->cinfo->setVisible(true) ;
        $this->docform->cinfo->setAttribute('onclick', "customerInfo({$customer_id});") ;


    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);
        $this->editcust->clean();
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $cust = new \App\Entity\Customer() ;
        $cust->customer_name = $custname;
        $cust->email = $this->editcust->editemail->getText();
        $cust->phone = $this->editcust->editphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
            $this->setError("Довжина номера телефона повинна бути ".\App\Helper::PhoneL()." цифр");
            return;
        }

        $c = \App\Entity\Customer::getByPhone($cust->phone);
        if ($c != null) {
            if ($c->customer_id != $cust->customer_id) {

                $this->setError("Вже існує контрагент з таким телефоном");
                return;
            }
        }
        $cust->type = $this->editcust->edittype->getValue();
        if($cust->type==3) {
            $cust->type=0;
            $cust->status=2;
        }
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);

    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }
    public function onEmp($sender) {
        $emp=$sender->getValue();
    }
    
    
}
