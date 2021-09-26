<?php

namespace App\Pages\Register;

use App\Entity\ProdProc;
use App\Entity\ProdStage;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;

/**
 * журнал производственные этапы
 */
class ProdStageList extends \App\Pages\Base
{

    private $_stage    = null;
    public $_emps      = array();
    public $_dates     = array();
 

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('ProdStageList')) {
            return;
        }

        $this->add(new Panel("listpan")) ;
         
      
        $this->listpan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->listpan->filter->add(new DropDownChoice('fproc', ProdProc::findArray('procname', '', 'procname'), 0));
     
        $stlist = $this->listpan->add(new DataView('stlist', new ProcStageDataSource($this), $this, 'stlistOnRow'));

        $this->listpan->add(new Paginator('pag', $stlist));
        $stlist->setPageSize(H::getPG());
  
      
        $this->add(new Panel("cardpan"))->setVisible(false)  ;
        $this->cardpan->add(new Label("stagenamec")) ;
        $this->cardpan->add(new Label("carddata")) ;
        $this->cardpan->add(new ClickLink("backc",$this,"backOnClick")) ;
   
   
                                 
        $this->add(new Panel("userspan"))->setVisible(false) ;   
        $this->userspan->add(new Label("stageh5")) ;
        $this->userspan->add(new Form("useraddform"))->onSubmit($this,"onAddEmp");
        
        $this->userspan->useraddform->add(new DropDownChoice('adduser', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name")));
        $this->userspan->useraddform->add(new  TextInput("addktu")) ;   
        $this->userspan->add(new DataView('userslist', new  ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_emps')), $this, 'empOnRow')) ;
                                                
        $this->userspan->add(new Button("saveusers"))->onClick($this,"onSaveEmp") ;
        $this->userspan->add(new Button("cancelusers"))->onClick($this,"backOnClick") ;


        $this->add(new Panel("statuspan"))->setVisible(false)  ;
        $this->statuspan->add(new Label("stagenames")) ;
        $this->statuspan->add(new ClickLink("backs",$this,"backOnClick")) ;

        $this->add(new Panel("calpan"))->setVisible(false)  ;
        $this->calpan->add(new Label("stagenamed")) ;
        $this->calpan->add(new Label("planhours")) ;
        $this->calpan->add(new Label("facthours")) ;
        $this->calpan->add(new ClickLink("backd",$this,"backOnClick")) ;

 
        $stlist->Reload();
       
         
    }

    public function filterOnSubmit($sender) {
        
        $this->listpan->stlist->Reload();
    }

  

    public function stlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $st = $row->getDataItem();

        $row->add(new Label('sname', $st->stagename));
        $row->add(new Label('pname', $st->procname));
        $row->add(new Label('snumber', $st->snumber));

        $row->add(new Label('startdate', H::fd($st->startdate)));
        $row->add(new Label('enddate', H::fd($st->enddate)));
      $row->add(new Label('hasnotes'))->setVisible(strlen($st->notes) > 0  );
      $row->hasnotes->setAttribute('title', $st->notes);

      $row->add(new ClickLink('card', $this, 'cardOnClick'))->setVisible(strlen($st->card) > 0  );
       
        $row->add(new ClickLink('show', $this, 'showOnClick'));
        $row->add(new ClickLink('workers', $this, 'wOnClick'));
        $row->add(new ClickLink('agenda', $this, 'cOnClick'));
      

    }

    

   
    public function cardOnClick($sender) {
        $this->cardpan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_stage = $sender->getOwner()->getDataItem();
        
        $this->cardpan->stagenamec->setText($this->_stage->stagename); 
        $this->cardpan->carddata->setText($this->_stage->card,true); 
         
    }
    public function wOnClick($sender) {
        $this->userspan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_stage = $sender->getOwner()->getDataItem();
        $this->_emps = $this->_stage->emplist;
        
        $this->userspan->userslist->Reload();
        $this->userspan->stageh5->setText($this->_stage->stagename); 
        
         
    } 
    public function onAddEmp($sender) {
         $id = $sender->adduser->getValue() ;
         $ktu = $sender->addktu->getText() ;
         
         if($id > 0 && $ktu > 0) {
             $emp = \App\Entity\Employee::load($id) ;
             $emp->ktu = $ktu  ;
             $this->_emps[$id]=$emp; 
             $sender->clean();
             $this->userspan->userslist->Reload();
         }
         
         
         
    }
    public function empOnRow($row) {
        $e = $row->getDataItem();

        $row->add(new Label('username', $e->emp_name));
        $row->add(new Label('ktu', $e->ktu));
        $row->add(new ClickLink('deluser', $this, 'deluserOnClick'));
               
    }
    
    public function deluserOnClick($sender) {
         $e = $sender->getOwner()->getDataItem();
         $this->_emps = array_diff_key($this->_emps, array($e->employee_id   => $this->_emps[$e->employee_id]));
       
         $this->userspan->userslist->Reload();
    }
    
    public function onSaveEmp($sender) {
  
      if(count($this->_emps)>0) {
          $ktu = 0;    
          foreach($this->_emps as $emp){
              $ktu += doubleval($emp->ktu) ;   
          }
          if($ktu != 1) {
              $this->setError("ktu1") ;
              return;
          }
          
        }     
        
        $this->_stage->emplist = $this->_emps;
        $this->_stage->save() ;
        $this->userspan->setVisible(false);
        $this->listpan->setVisible(true);        
    }
  

    public function showOnClick($sender) {
        $this->statuspan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_stage = $sender->getOwner()->getDataItem();
 
        $this->statuspan->stagenames->setText($this->_stage->stagename); 
    }    
      
    
    public function cOnClick($sender) {
        $this->calpan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_stage = $sender->getOwner()->getDataItem();
 
        $this->calpan->stagenamed->setText($this->_stage->stagename); 
        $this->calpan->planhours->setText($this->_stage->hours); 
    }    
    
    public function backOnClick($sender) {
        $this->cardpan->setVisible(false);
        $this->userspan->setVisible(false);
        $this->statuspan->setVisible(false);
        $this->calpan->setVisible(false);
        $this->listpan->setVisible(true);
        
        $this->listpan->stlist->Reload() ;
         
    }

   
}

/**
 *  Источник  данных  для   списка  документов
 */
class ProcStageDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
  
        $where = "  1=1 ";
                                                           
        $proc = $this->page->listpan->filter->fproc->getValue();

        if ($proc > 0) {
            $where .= " and pp_id=" . $proc;
        }
      
        return $where;
    }

    public function getItemCount() {
        return ProdStage::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
       return  ProdStage::find($this->getWhere(),"");
    }

    public function getItem($id) {

    }

}
