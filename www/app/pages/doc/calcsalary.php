<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Employee;
use App\Entity\MoneyFund; 
use App\Entity\SalType;
use App\Entity\EmpAcc;
use App\Helper as H;
use App\System ;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Link\SubmitLink ;
use Zippy\Html\Label;
use \Zippy\Binding\PropertyBinding as Bind;

/**
 * Страница   начисление  зарплаты
 */
class CalcSalary extends \App\Pages\Base
{

    private $_doc;
    public  $_list = array();
    public  $_stlist = array();

    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
     
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        
        $this->docform->add(new DropDownChoice('year', \App\Util::getYears(), round(date('Y'))));
        $this->docform->add(new DropDownChoice('month', \App\Util::getMonth(), round(date('m'))));
        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new TextInput('dayscnt'));

        $this->docform->add(new SubmitButton('tocalc'))->onClick($this, 'tocalcOnClick');
        
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->_list = Employee::find('disabled<>1', 'emp_name');

        $this->add(new Form('calcform'))->setVisible(false);      
  
        $this->calcform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->calcform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->calcform->add(new SubmitButton('todoc'))->onClick($this, 'todocOnClick');
        $this->calcform->add(new SubmitLink('edel'))->onClick($this, 'delOnClick');
        $this->calcform->add(new SubmitLink('addemp'))->onClick($this, 'addOnClick');
        $this->calcform->add(new DropDownChoice('newemp')) ;
        $this->calcform->add(new TextInput('total')) ;
       
        
        if ($docid > 0) {    //загружаем   содержимок  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);

            
            $this->docform->year->setValue($this->_doc->headerdata['year']);
            $this->docform->month->setValue($this->_doc->headerdata['month']);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->dayscnt->setText($this->_doc->headerdata['dayscnt']);
         //   $this->docform->total->setText(H::fa($this->_doc->amount));
            $this->_list = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('CalcSalary');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }
        
        
        $this->calcform->add(new DataView('elist', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_list')), $this, 'employeelistOnRow'));

        $this->_stlist =  SalType::find("disabled<>1","salcode") ;
      
        $opt = System::getOptions("salary") ;
         
         
        $this->_tvars['colemps'] = count($this->_list);
        $this->_tvars['totst'] = $opt['coderesult'];
 
  
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }



    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['year'] = $this->docform->year->getValue();
        $this->_doc->headerdata['month'] = $this->docform->month->getValue();
        $this->_doc->headerdata['monthname'] = $this->docform->month->getValueName();
        $this->_doc->headerdata['dayscnt'] = $this->docform->dayscnt->getText();

        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());

        $this->_doc->packDetails('detaildata', $this->_list);
        $this->_doc->amount = $this->calcform->total->getText();
        foreach ($this->_list as $emp) {
            if ($emp->amount > 0) {
              //  $this->_doc->amount += $emp->amount;
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
            App::Redirect("\\App\\Pages\\Register\\SalaryList");

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
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
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('docnumbercancreated');
            }
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }
    public function todocOnClick($sender) {
       $this->calcform->setVisible(false);  
       $this->docform->setVisible(true);  
    }
    public function tocalcOnClick($sender) {
       $this->calcform->setVisible(true);  
       $this->docform->setVisible(false); 
       
       
      if($this->_doc->document_id == 0) {
                  $opt = System::getOptions("salary") ;

                  if($opt['codeadvance']>0) { //аванс
                      
                      $rows = EmpAcc::getAmountByType($this->_doc->headerdata['year'] ,$this->_doc->headerdata['month'] ,EmpAcc::ADVANCE) ;
                      foreach($rows as $row){
                          $c = '_c'.$opt['codeadvance'] ;
                          $this->_list[$row['emp_id']]->{$c}= 0-H::fa( $row['am'] );
                      }
                  }
                  if($opt['codebaseincom']>0)  {
                        $c = '_c'.$opt['codebaseincom'] ;
                        foreach( $this->_list as $emp) {
                           $emp->{$c} = 0 ;
                        }                        
                  }
                  
                  
         
          }
          
       
        
        $this->Reload();
         
    }
    public function delOnClick($sender) {
        $_list = array();
        foreach($this->_list as $id=>$e) {
            if($e->_ch==TRUE) continue;
            $_list[$id]=$e;
        }
        
        $this->_list =$_list;  
        $this->Reload();
    
    }
    
    public function addOnClick($sender) {
         $id = $this->calcform->newemp->getValue();
         if($id > 0){
             $this->_list[$id]= Employee::load($id);
             
                  $opt = System::getOptions("salary") ;

                  if($opt['codeadvance']>0) { //аванс
                      
                      $rows = EmpAcc::getAmountByType($this->_doc->headerdata['year'] ,$this->_doc->headerdata['month'] ,EmpAcc::ADVANCE) ;
                      foreach($rows as $row){
                          $c = '_c'.$opt['codeadvance'] ;
                          if($id==$row['emp_id']) {
                             $this->_list[$row['emp_id']]->{$c}= 0-H::fa( $row['am'] );
                          }
                      }
                  }             
             
                  if($opt['codebaseincom']>0)  {
                        $c = '_c'.$opt['codebaseincom'] ;
                        $this->_list[$id]->{$c}=  0;
                  }
             
         }
         $this->Reload();
    }
    public function updateAddList() {
          
        $ids =  array_keys($this->_list);
        $list = array();
        foreach(Employee::findArray('emp_name','disabled<>1', 'emp_name') as $id=>$name) {
            if( in_array($id,$ids) ==false){
                $list[$id]= $name;       
            }
        }
        $this->calcform->newemp->setOptionList($list);
        $this->calcform->newemp->setValue(0);
    }

    public function employeelistOnRow($row) {
        $emp = $row->getDataItem();
        $row->add(new Label('emp_name', $emp->emp_name));
        $row->add(new CheckBox('emp_ch', new Bind($emp, '_ch')));
        
        foreach($this->_stlist as $c=>$n){
            
           $ti = $row->add(new TextInput('v'.$n->salcode,new Bind($emp, '_c'.$n->salcode)));
           $ti->setAttribute("r-n",$row->getNumber());
           $ti->setAttribute("onblur","onCalc(".$row->getNumber().")"  );
        } 
        
        $row->add(new TextInput('invalid', $emp->invalid)) ;
      
    }    
    public function Reload() {
          $opt = System::getOptions("salary") ;
    
          $this->_tvars['stnums'] = array();
          $this->_tvars['stnames'] = array();
          $this->_tvars['colemps'] = count($this->_list);
          $this->_tvars['totst'] = $opt['coderesult'];
          $this->_tvars['calc'] = $opt['calc'];
       
          
          foreach($this->_stlist as $c=>$n){
             $this->_tvars['stnames'][]=array('name'=>$n->salshortname) ;
             $this->_tvars['stcodes'][]=array('code'=>$n->salcode) ;
             
          }      

         
          $this->calcform->elist->Reload();  
          $this->updateAddList();
            
           
    }    
    
}
