<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Account;
use App\Entity\AccEntry;

use App\Helper as H;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Link\SubmitLink; 
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\DataList\DataView; 

/**
 * Страница  ручная проводка
 */
class ManualEntry extends \App\Pages\Base
{
    private $_doc;
    private $_acclist;
    public $_itemlist = array();
 
    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new CheckBox('reloaddoc'));
        $this->docform->add(new CheckBox('removedoc'));
        $this->docform->add(new Date('document_date', time()));
        $this->docform->add(new TextInput('notes'));                 

        $list = Account::getList(true,true);
        $this->_acclist = Account::getList(true );

  
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
   
        $this->docform->add(new SubmitButton('generate'))->onClick($this, 'generatecOnClick');
    
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new DropDownChoice('editdt', $list, 0));
        $this->editdetail->add(new DropDownChoice('editct', $list, 0));
   
        $this->editdetail->add(new TextInput('editamount'));
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');
     
        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'));
          
        
        
        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->reloaddoc->setChecked($this->_doc->headerdata['reload']);
            $this->docform->removedoc->setChecked($this->_doc->headerdata['remove']);
      
 
            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
            $this->docform->detail->Reload();            
            
        } else {
            $this->_doc = Document::create('ManualEntry');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    
  public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('accdt', $this->_acclist[$item->accdt] ??'' ));
        $row->add(new Label('accct', $this->_acclist[$item->accct] ??''));

        $row->add(new Label('amount', H::fa($item->amount )));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }


    
    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));


        $this->docform->detail->Reload();
    }
    public function addrowOnClick($sender) {
  
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = -1;
        $this->editdetail->clean();
        
    }    
    public function saverowOnClick($sender) {
  
        $item = new AccEntry();


        $item->accdt = $this->editdetail->editdt->getValue();
        $item->accct = $this->editdetail->editct->getValue();
        $item->amount = $this->editdetail->editamount->getDouble();

        if ($item->amount== 0) {
            $this->setError("Не введено суму");
            return;
        }        
        if ($item->accdt== 0 && $item->accct== 0) {
            $this->setError("Не введено рахунок");
            return;
        }        
        if ($item->accdt==   $item->accct ) {
            $this->setError("Однаковi рахунки");
            return;
        }        
        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }



        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
  
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
      
    }
    
    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->packDetails('detaildata', $this->_itemlist);
       
        $this->_doc->notes = trim($this->docform->notes->getText());
        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date =  $this->docform->document_date->getDate();
        $this->_doc->payment = 0;
        $this->_doc->payed = 0;
        if ($this->checkForm() == false) {
            return;
        }
        $this->_doc->headerdata['reload'] = $this->docform->reloaddoc->isChecked()?1:0;
        $this->_doc->headerdata['remove'] = $this->docform->removedoc->isChecked()?1:0;
      
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
            App::Redirect("\\App\\Pages\\Register\\AccountEntryList");
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

     
        if (count($this->_itemlist)==0) {
            $this->setError("Не введено проводки");
        }

        return !$this->isError();
    }

    
    public function generatecOnClick($sender) {
         $this->docform->removedoc->setChecked(true)   ;
        
         $this->_itemlist =[] ;
         $brf="";
         $conn = \ZDB\DB::getConnect();
         $date=$conn->DBDate($this->docform->document_date->getDate());
         $w="document_id in (select document_id from documents where document_date<={$date} )   " ; 
      
         $b= \App\system::getBranch()  ;
         if($b > 0){
            $w="and document_id in (select document_id from documents where branch_id = {$b} ) and " ; 
         }
         //тмц
         $ia =   Account::getAccCode();
       
         $sql="select coalesce(sum(e.quantity * e.partion ),0) as am, item_type from entrylist_view e join items i on e.item_id=i.item_id  where {$w} and i.disabled<>1 group by i.item_type ";
         foreach($conn->Execute($sql) as $row) {
            
            $item = new AccEntry();


            $item->accdt = $ia[$row['item_type']] ?? '28';
            $item->accct = '40';
            
            $item->amount = H::fa($row['am']);
            
            $this->_itemlist[] = $item;
             
         }         
         //каса
        $sql = "select coalesce(sum(amount),0)  from paylist_view where  paytype <=1000 and mf_id  in (select mf_id  from mfund where detail not like '%<beznal>1</beznal>%' {$brf})";
        $am = H::fa($conn->GetOne($sql));
       
        $item = new AccEntry();
        $item->accdt = '30';
        $item->accct = '40';
         
        $item->amount = H::fa($am);
        $this->_itemlist[] = $item;   
        
        $sql = "select coalesce(sum(amount),0)  from paylist_view where  paytype <=1000 and mf_id  in (select mf_id  from mfund where detail like '%<beznal>1</beznal>%' {$brf})";
        $am = H::fa($conn->GetOne($sql));
        $item = new AccEntry();
        $item->accdt = '31';
        $item->accct = '40';
  
        $item->amount = H::fa($am);
        $this->_itemlist[] = $item;   
         
      
       $cust_acc_view = \App\Entity\CustAcc::get_acc_view()  ;
       
 
       
    //поставщики
    $sql = "SELECT  
     COALESCE( sum(a.s_passive), 0) AS pas,
     COALESCE( sum(a.s_active), 0) AS act
FROM ({$cust_acc_view} ) a
  JOIN customers c
    ON a.customer_id = c.customer_id
    AND c.status = 0 AND a.s_passive <> a.s_active 
      "; 
     foreach($conn->Execute($sql) as $row) {
        if($row['pas'] > $row['act']) {
            $am = H::fa($row['pas'] - $row['act']);  
            $item = new AccEntry();
            $item->accdt = '63';
            $item->accct = '40';
      
            $item->amount = H::fa($am);
            $this->_itemlist[] = $item;       
        }  
        if($row['pas'] < $row['act']) {
           $am = H::fa($row['act'] - $row['pas']  );  
            $item = new AccEntry();
            $item->accdt = '40';
            $item->accct = '63';
      
            $item->amount = H::fa($am);
            $this->_itemlist[] = $item;       
         }  
     }    
  
     
      
      //покупатели
    $sql = "SELECT    
     COALESCE( sum(a.b_passive), 0) AS pas,
     COALESCE( sum(a.b_active), 0) AS act
FROM ({$cust_acc_view} ) a
  JOIN customers c
    ON a.customer_id = c.customer_id
    AND c.status = 0 AND a.b_passive <> a.b_active   
      "; 
    foreach($conn->Execute($sql) as $row) {
        if($row['pas'] > $row['act']) {
           $am = H::fa($row['pas'] - $row['act']); 
            $item = new AccEntry();
            $item->accdt = '36';
            $item->accct = '40';
      
            $item->amount = H::fa($am);
            $this->_itemlist[] = $item;              
        }  
        if($row['pas'] < $row['act']) {
           $am = H::fa($row['act'] - $row['pas']  );  
           $item = new AccEntry();
            $item->accdt = '40';
            $item->accct = '36';
      
            $item->amount = H::fa($am);
            $this->_itemlist[] = $item;             
        }           
     }     
       $w="";
       if($b > 0){
            $w="  and branch_id = {$b}  " ; 
         }         
         //сотрудники
         $sql = "select coalesce(sum(0-amount),0) from empacc where emp_id in (select employee_id  from employees where disabled<>1 {$w}) and  optype = 105 " ;   
         $am=doubleval($conn->GetOne($sql))  ;
         if($am >0) {
            $item = new AccEntry();
            $item->accdt = '372';  
            $item->accct = '40';
      
            $item->amount = H::fa($am);
            $this->_itemlist[] = $item;              
         }
         $sql = "select coalesce(sum(amount),0) from empacc where emp_id in (select employee_id  from employees where disabled<>1 {$w}) and  optype in (3,4)  " ;   
         $am=doubleval($conn->GetOne($sql))  ;
         if($am >0) {
            $item = new AccEntry();
            $item->accdt = '40';  
            $item->accct = '66';
      
            $item->amount = H::fa($am);
            $this->_itemlist[] = $item;              
         }
       
         //ОС
         $sql = "select coalesce(sum(amount),0) from eqentry where eq_id in (select eq_id  from equipments where disabled<>1 {$w}) and  optype in (1,4)  " ;   
         $am=doubleval($conn->GetOne($sql))  ;
         if($am >0) {
            $item = new AccEntry();
            $item->accdt = '10';  
            $item->accct = '40';
      
            $item->amount = H::fa($am);
            $this->_itemlist[] = $item;              
         }      
  
         $sql = "select coalesce(sum(amount),0) from eqentry where eq_id in (select eq_id  from equipments where disabled<>1 {$w}) and  optype in (3)  " ;   
         $am=doubleval($conn->GetOne($sql))  ;
         if($am >0) {
            $item = new AccEntry();
            $item->accdt = '13';  
            $item->accct = '40';
      
            $item->amount = H::fa($am);
            $this->_itemlist[] = $item;              
         }      
  
  
  
         $this->docform->detail->Reload();
        
    }    
    
    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

}
