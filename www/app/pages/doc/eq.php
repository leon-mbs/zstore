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
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Label;
use App\Entity\Equipment;
use App\Entity\EqEntry; 

/**
 * Страница  документа операции с ОС и НМА
 */
class EQ extends \App\Pages\Base
{
    private $_doc;

    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0,$eq_id=0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

 
        
        $this->docform->add(new DropDownChoice('optype',EqEntry::getOpList(),0 ))->onChange($this,'onType');

        $this->docform->add(new DropDownChoice('store',\App\Entity\Store::findArray('storename','disabled<>1','storename'),H::getDefStore() ))->onChange($this,"onStore");
        $this->docform->add(new DropDownChoice('emp',\App\Entity\Employee::findArray('emp_name','disabled<>1','emp_name'),0 ));
        $this->docform->add(new DropDownChoice('parea',\App\Entity\ProdArea::findArray('pa_name','disabled<>1','pa_name'),0 ));
        $this->docform->add(new TextInput('amount'));
        $this->docform->add(new Label('tip'));
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this,'onCust');
        $this->docform->add(new AutocompleteTextInput('eq'))->onText($this,'oneqItem');
        $this->docform->eq->onChange($this,"onEQSelect");
        
        $this->docform->add(new AutocompleteTextInput('item'))->onText($this,'onItem');
        $this->docform->item->onChange($this,"onItemSelect");
        if($eq_id > 0) {
           $eq= Equipment::load($eq_id);
           if($eq != null) {
               $this->docform->eq->setKey($eq_id);
               $this->docform->eq->setText($eq->eq_name);
           }
        }
        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->amount->setText( H::fa( $this->_doc->amount));
            $this->docform->optype->setValue($this->_doc->headerdata['optype']);
            $this->docform->emp->setValue($this->_doc->headerdata['emp_id']??0);
            $this->docform->store->setValue($this->_doc->headerdata['store_id']??0);
            $this->docform->parea->setValue($this->_doc->headerdata['pa_id']??0);
            $this->docform->item->setKey($this->_doc->headerdata['item_id']??0);
            $this->docform->item->setText($this->_doc->headerdata['item_name']??'');
            $this->docform->customer->setKey($this->_doc->customer_id??0);
            $this->docform->customer->setText($this->_doc->customer_name??'');
            $this->docform->eq->setKey($this->_doc->headerdata['eq_id']);
            $this->docform->eq->setText($this->_doc->headerdata['eq_name']);
       } else {
            $this->_doc = Document::create('EQ');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->onType( $this->docform->optype) ;

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }
  
      
    public function onType($sender) {
         $this->docform->store->setVisible(false);
         $this->docform->emp->setVisible(false);
         $this->docform->parea->setVisible(false);
         $this->docform->amount->setVisible(false);
         $this->docform->customer->setVisible(false);
         $this->docform->item->setVisible(false);
         $this->docform->tip->setText("");
         
         $op=intval($sender->getValue());
 
         $this->docform->amount->setVisible(true);
         $this->docform->amount->setAttribute('readonly',"on");
         $this->docform->amount->setText('');
         
         if($op==EqEntry::OP_INCOME){
            $this->docform->parea->setVisible(true);
            $this->docform->emp->setVisible(true);
            $this->docform->amount->setVisible(false);
        
             
         }
         if($op==EqEntry::OP_OUTCOME){
   
            $this->docform->amount->setAttribute('readonly',"on");
            $this->docform->amount->setText();
              
         }
         if($op==EqEntry::OP_AMOR){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly' );
        
         }
         if($op==EqEntry::OP_REPAIR){
          
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly' );
         }
         if($op==EqEntry::OP_MOVE){
            $this->docform->amount->setVisible(false);
            $this->docform->parea->setVisible(true);
            $this->docform->emp->setVisible(true);
         }
         if($op==EqEntry::OP_BUY){
            $this->docform->amount->setAttribute('readonly' );
       
            $this->docform->customer->setVisible(true);
            $this->docform->tip->setText("Оплата через журнал розрахункiв  ");
         }
         if($op==EqEntry::OP_PROD){
           $this->docform->amount->setAttribute('readonly' );
     
         }
         if($op==EqEntry::OP_STORE){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly',null);
            $this->docform->store->setVisible(true);
            $this->docform->item->setVisible(true);            

         }
         if($op==EqEntry::OP_SELL){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly' );
            $this->docform->customer->setVisible(true);
            $this->docform->tip->setText("Оплата через журнал розрахункiв  ");
       
         }
         if($op==EqEntry::OP_TOSTORE){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly','on');
            $this->docform->store->setVisible(true);
            $this->docform->item->setVisible(true);
         }
         if($op==EqEntry::OP_LOST){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly','on');
           
         }
        
         $eq=  Equipment::load($this->docform->eq->getKey());         
         if(in_array($op,[2,9,10,11]) && $eq != null) {
    
           $this->docform->amount->setText(H::fa($eq->getBalance()));
         }          
    }
    
    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        
        if ($this->checkForm() == false) {
            return;
        }        
        $this->_doc->notes = $this->docform->notes->getText();
         
        $eq_id= $this->docform->eq->getKey();
        $this->_doc->headerdata = [];
        $this->_doc->headerdata['optype'] = $this->docform->optype->getValue();
        $this->_doc->headerdata['optypename'] = $this->docform->optype->getValueName();
            
        $this->_doc->headerdata['eq_id'] = $eq_id;
        $this->_doc->headerdata['eq_name'] = $this->docform->eq->getText();
        $this->_doc->headerdata['emp_id'] = $this->docform->emp->getValue();
        if($this->_doc->headerdata['emp_id'] >0) {
            $this->_doc->headerdata['emp_name'] = $this->docform->emp->getValueName();
        }
        
        $this->_doc->headerdata['store_id'] = $this->docform->store->getValue();
        if($this->_doc->headerdata['store_id'] >0) {
            $this->_doc->headerdata['store_name'] = $this->docform->store->getValueName();            
        }
        $this->_doc->headerdata['pa_id'] = $this->docform->parea->getValue();
        if($this->_doc->headerdata['pa_id'] >0) {
            $this->_doc->headerdata['pa_name'] = $this->docform->parea->getValueName();            
        }
      
        $this->_doc->headerdata['item_id'] = $this->docform->item->getKey();
        if($this->_doc->headerdata['item_id'] >0) {
            $this->_doc->headerdata['item_name'] = $this->docform->item->getText();            
            if($this->_doc->headerdata['optype'] ==3 ) {
               $st = \App\Entity\Stock::load($this->_doc->headerdata['item_id']);
               $this->_doc->headerdata['item_name'] = $st->itemname.' '. $st->snumber; 
            }
        }
       

        $this->_doc->customer_id = $this->docform->customer->getKey();
        $this->_doc->amount = H::fa($this->docform->amount->getDouble());
        
        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date =   $this->docform->document_date->getDate();
        $this->_doc->payment = 0;
        $this->_doc->payed = 0;
      
   

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
            App::Redirect("\\App\\Pages\\Reference\\EqList",$eq_id);
            return;
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
  
            $this->setError($ee->getMessage());
            $logger->error( $ee->getMessage()  );


        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
         $eq = intval($this->docform->eq->getKey() );
         if($eq==0)  {
             $this->setError('Не вибрано ОЗ') ;
             return false;
         }        
         $isincome = 0< intval( EqEntry::findCnt(" eq_id = {$eq}  and  optype=".EqEntry::OP_INCOME) ); // введен в эксплуатацию
         $isoutcome =0< intval( EqEntry::findCnt(" eq_id = {$eq}  and  optype=".EqEntry::OP_OUTCOME) ); //снят
    
                 
         $op = intval($this->docform->optype->getValue() );
         
         if(!in_array($op,[1,6,7,8 ]) && !$isincome ){
             $this->setError('Не введено в експлуатацію')  ;
         }
         if(in_array($op,[1,6,7,8]) && $isincome  ){
             $this->setError('Вже введено в експлуатацію')  ;
         }
         if( in_array($op,[  9,10,11]) && !$isoutcome  ){
             $this->setError('Не виведено з експлуатації')  ;
         }
         if(!in_array($op,[  9,10,11 ]) && $isoutcome  ){
             $this->setError('Вже виведено з експлуатації')  ;
         }
          
         $amount = doubleval($this->docform->amount->getText() );
         $c = intval($this->docform->customer->getKey() );
         $item = intval($this->docform->item->getKey() );

      
         if($op==6 || $op==9){
             if($c==0)  {
                 $this->setError('Не вибрано контрагента') ;
             }
     
 
         }
         if($op==8 ||$op==10   ){
             if($item==0)  {
                 $this->setError('Не вибрано ТМЦ') ;
             }
    
         }
         if($op==4){
            $parea = intval($this->docform->parea->getValue() );
            $emp = intval($this->docform->emp->getValue() );
            if($parea==0 && $emp==0)   {
               $this->setError('Не вибрано дільницю та/або  відповідального ') ;
            
            }
             
         }
         if(in_array($op,[3,4,6.7] )){
             if($amount==0)  {
                 $this->setError('Не вказано суму  ') ;
             }
         }
   
  
  
        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function onItemSelect($sender) {
        
        $op = $this->docform->optype->getValue();
        if($op==3) {
           $st=  \App\Entity\Stock::load($sender->getKey());
           $this->docform->amount->setText(H::fa($st->partion));
        }
      
   
    }
    public function onEQSelect($sender) {
        
        $op = intval($this->docform->optype->getValue());
     
        if(in_array($op,[2,9,10,11])) {
           $eq=  Equipment::load($sender->getKey());
           $this->docform->amount->setText(H::fa($eq->getBalance()));
        }
   
    }
    public function OnItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        $op = $this->docform->optype->getValue();
        if($op==EqEntry::OP_STORE) {
           return   \App\Entity\Stock::findArrayAC($store_id,$text) ;
        }
        if($op==EqEntry::OP_TOSTORE) {
            return \App\Entity\Item::findArrayAC($text, $store_id);            
        }
        

    }

    public function onCust($sender) {
        return \App\Entity\Customer::getList($sender->getText(), 1, true);
    } 
    public function oneqItem($sender) {
        return \App\Entity\Equipment::getList(trim($sender->getText() )   );
    } 
    public function onStore($sender) {
           $this->docform->item->setKey(0);
           $this->docform->item->setText('');
    } 
       
}
