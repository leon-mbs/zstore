<?php

namespace App\Pages\Doc;

use App\Application as App;
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

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');


        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->edittitle->setText($this->_doc->notes);

            $d = $this->_doc->unpackDetails('detaildata')  ;
            $this->docform->doccontent->setText($d['data']??''); 
            
         } else if($copyid>0){
            $cdoc = Document::load($copyid)->cast();
            $this->docform->edittitle->setText($cdoc->notes); 
            $d= $cdoc->unpackDetails('detaildata')  ;
            $this->docform->doccontent->setText($d['data']??''); 
             
         
         } else {
            $this->_doc = Document::create('OfficeDoc');
            $this->docform->document_number->setText($this->_doc->nextNumber());
         
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

}
