<?php

namespace App\Pages\Register;

use App\Entity\ProdProc;
use App\Entity\ProdStage;
use App\Entity\Item;
 
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;

/**
 * журнал производственные процессы
 */
class ProdProcList extends \App\Pages\Base
{
 
    public $_proc    = null;
    public $_stage    = null;
    public $_prodlist    = array() ;
   

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('ProdProcList')) {
            return;
        }

        $this->add(new Panel("listpan")) ;
        
        $proclist = $this->listpan->add(new DataView('proclist', new PProcListDataSource($this), $this, 'proclistOnRow'));

        $this->listpan->add(new Paginator('pag', $proclist));
        $proclist->setPageSize(H::getPG());

        $this->add(new ClickLink('addnewproc',$this,"OnAddProc")) ;
        
        $this->add(new Form('editproc'))->setVisible(false);
        $this->editproc->add(new TextInput('editname'));
        $this->editproc->add(new TextInput('editbasedoc'));
        $this->editproc->add(new TextInput('editsnumber'));
        $this->editproc->add(new TextArea('editnotes'));
           
        $this->editproc->add(new SubmitButton('save'))->onClick($this, 'OnSave');
        $this->editproc->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->editproc->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
      
        //продукция
        $this->add(new Panel("prodspan"))->setVisible(false) ;
        $this->prodspan->add(new ClickLink('cancelprods'))->onClick($this,'onCancelProds') ;
        $this->prodspan->add(new ClickLink('saveprods'))->onClick($this,'onSaveProds') ;
        $this->prodspan->add(new Form('addprodform'))->onSubmit($this,'onAddProd') ;
        $this->prodspan->addprodform->add(new DropDownChoice('additem',Item::findArray("itemname","disabled<> 1 and item_type=".Item::TYPE_PROD,"itemname"),0));
        $this->prodspan->addprodform->add(new TextInput('addqty'));
        $this->prodspan->add(new DataView('proditemlist', new ArrayDataSource($this,"_prodlist"), $this, 'prodlistOnRow'));
        
        $this->add(new Panel("stagespan"))->setVisible(false) ;
        $this->stagespan->add(new ClickLink('backtoproc'))->onClick($this,'onCancelProds') ;
        $this->stagespan->add(new ClickLink('addstage'))->onClick($this,'onAddStage') ;
        $this->stagespan->add(new DataView('stagelist', new PStageListDataSource($this ), $this, 'stagelistOnRow'));
         
         
         
         $this->add(new Form('editstage'))->setVisible(false);
         $this->editstage->add(new TextInput('editstagename'));
         $this->editstage->add(new TextInput('editstagehours'));
         $this->editstage->add(new TextInput('editstagesalary'));
         
         $this->editstage->add(new TextArea('editstagenotes'));
         
         $this->editstage->add(new DropDownChoice('editstagearea',\App\Entity\ProdArea::findArray('pa_name','')));
         $this->editstage->add(new SubmitButton('savestage'))->onClick($this, 'OnSaveStage');
         $this->editstage->add(new Button('cancelstage'))->onClick($this, 'onCanceStage');
         
         
         $this->add(new Form('editcardform'))->setVisible(false);
         $this->editcardform->add(new Label('stagenameh4'));
         $this->editcardform->add(new TextArea('editcard'));
         $this->editcardform->add(new SubmitButton('savecard'))->onClick($this, 'OnSaveCard');
         $this->editcardform->add(new Button('cancelcard'))->onClick($this, 'onCanceStage');

       
         $this->listpan->add(new Panel("showpan"))->setVisible(false) ;
          
         $this->listpan->proclist->Reload();
  
    }
        
    public function proclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        
        $p = $row->getDataItem();

        $row->add(new Label('name', $p->procname));
        $row->add(new Label('basedoc', $p->basedoc));
        $row->add(new Label('snumber', [HttpGet]->snumber));
        $row->add(new Label('state', ProdProc::getStateName($p->state)  ));
           
        $row->add(new Label('startdate',H::fd( $p->startdate)) );
        $row->add(new Label('enddate', H::fd( $p->enddate)) );
      
          $row->add(new ClickLink('edit'))->onClick($this, 'OnEdit');
          $row->add(new ClickLink('view' ))->onClick($this, 'onView');
          $row->add(new ClickLink('stages'))->onClick($this, 'OnStages');
          $row->add(new ClickLink('copy'))->onClick($this, 'OnCopy');
          $row->add(new ClickLink('prods'))->onClick($this, 'OnProds');
          $row->add(new ClickLink('delete',$this, 'deleteOnClick'))->setVisible($p->stagecnt==0);
      $row->add(new Label('hasnotes'))->setVisible(strlen($p->notes) > 0  );
      $row->hasnotes->setAttribute('title', $p->notes);
 
          if($p->pp_id==$this->_proc->pp_id) {
             $row->setAttribute('class','table-success');    
          }
           
    }

    
    public function OnCopy($sender){
        $proc = $sender->getOwner()->getDataItem();
        
        $this->_proc = $proc->clone();
     
        $this->listpan->proclist->Reload();
       
    }
    
    //новый процесс
    public function OnAddProc($sender) {
   
        $this->listpan->setVisible(false); 
        $this->editproc->setVisible(true); 
        $this->editproc->clean(); 
        $this->_proc = new ProdProc();
   
    }

    public function cancelOnClick($sender) {

        $this->listpan->setVisible(true); 
        $this->editproc->setVisible(false); 
    
    }
    public function deleteOnClick($sender) {
        $proc = $sender->getOwner()->getDataItem();
       
        ProdProc::delete($proc->pp_id);
  
 
        $this->listpan->proclist->Reload();
    }
    public function OnEdit($sender) {
      
        $this->listpan->setVisible(false); 
        $this->editproc->setVisible(true); 
        $this->_proc = $sender->getOwner()->getDataItem();
        
        $this->editproc->editname->setText($this->_proc->procname ) ;
        $this->editproc->editbasedoc->setText($this->_proc->basedoc ) ;
        $this->editproc->editsnumber->setText($this->_proc->snumber ) ;
        $this->editproc->editnotes->setText($this->_proc->notes ) ;
        
          
    }

    public function OnSave($sender) {

        $this->_proc->procname =   $this->editproc->editname->getText();
        $this->_proc->basedoc  =   $this->editproc->editbasedoc->getText();
        $this->_proc->snumber  =   $this->editproc->editsnumber->getText();
        $this->_proc->notes  =   $this->editproc->editnotes->getText();
         
        $this->_proc->save() ;
         
        $this->listpan->setVisible(true); 
        $this->editproc->setVisible(false); 
 
        $this->listpan->proclist->Reload();
    }
   
   //новая продукция
    public function OnProds($sender) {
         $this->prodspan->setVisible(true); 
         $this->listpan->setVisible(false); 
         $this->_proc = $sender->getOwner()->getDataItem();
         $this->_prodlist = $this->_proc->prodlist;
         $this->prodspan->proditemlist->Reload();
    }

    public function onAddProd($sender) {
 
         $item = Item::load($sender->additem->getValue());
         if($item==null) return;
         $item->qty =  $sender->addqty->getText();
         if(($item->qty >0)==false ) return; 
         $this->_prodlist[$item->item_id] = $item;
      
         $this->prodspan->proditemlist->Reload();
         $sender->clean(); 
         
    }

    public function prodlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $p = $row->getDataItem();
        $row->add(new Label('proditemname', $p->itemname));
        $row->add(new Label('proditemqty', $p->qty));
        $row->add(new ClickLink('proditemdel'))->onClick($this, 'OnProdDel');
    
    }   

    public function OnProdDel($sender) {
         $item = $sender->getOwner()->getDataItem();
         $this->_prodlist = array_diff_key($this->_prodlist, array($item->item_id   => $this->_prodlist[$item->item_id]));
 
         $this->prodspan->proditemlist->Reload();
 
    }
   
    public function onSaveProds($sender) {
          $this->_proc->prodlist =   $this->_prodlist;
         $this->_proc->save();
         //$this->listpan->proclist->Reload();
         $this->prodspan->setVisible(false); 
         $this->listpan->setVisible(true); 
    }
 
    public function onCancelProds($sender) {
         $this->stagespan->setVisible(false); 
         $this->prodspan->setVisible(false); 
         $this->listpan->setVisible(true); 
         $this->listpan->showpan->setVisible(false); 
     
         $this->listpan->proclist->Reload();
  
    }
   
    //производственные  этапы
   
    public function OnStages($sender) {
         $this->stagespan->setVisible(true); 
         $this->listpan->setVisible(false); 
         $this->_proc = $sender->getOwner()->getDataItem();
          
         $this->stagespan->stagelist->Reload();
    }
      
    public function stagelistOnRow($row) {
      $s = $row->getDataItem();

      $row->add(new Label('stagename', $s->stagename));
      $row->add(new Label('stageareaname', $s->pa_name));
      $row->add(new Label('stagestate', ProdStage::getStateName($s->state) ));
      
      $row->add(new ClickLink('stageedit'))->onClick($this, 'OnStageEdit');
      $row->add(new ClickLink('stagedel'))->onClick($this, 'OnStageDel');
      $row->add(new ClickLink('stagecard'))->onClick($this, 'OnCard');
     
     
  }
  
    public function OnSaveStage($sender) {
 
         $this->_stage->stagename = $this->editstage->editstagename->getText();
         $this->_stage->notes = $this->editstage->editstagenotes->getText();
         $this->_stage->pa_id = $this->editstage->editstagearea->getValue();
         $this->_stage->hours = $this->editstage->editstagehours->getText();
         $this->_stage->salary = $this->editstage->editstagesalary->getText();
       
         if($this->_stage->pa_id==0) {
             $this->setError('noselparea') ;
             return;
         }
        
         $this->_stage->save();
         $this->stagespan->stagelist->Reload();
          $this->editstage->setVisible(false); 
         $this->stagespan->setVisible(true); 
 
    }
  
    public function onAddStage($sender) {
         $this->editstage->setVisible(true); 
         $this->stagespan->setVisible(false); 
         $this->editstage->clean();
         
       
         $this->_stage = new ProdStage();
         $this->_stage->pp_id = $this->_proc->pp_id;
    }
  
    public function OnStageEdit($sender) {
         $this->editstage->setVisible(true); 
         $this->stagespan->setVisible(false); 
     
         $this->_stage = $sender->getOwner()->getDataItem(); 
         
         $this->editstage->editstagename->setText($this->_stage->stagename)   ;
         $this->editstage->editstagenotes->setText($this->_stage->notes)   ;
         $this->editstage->editstagehours->setText($this->_stage->hours)   ;
         $this->editstage->editstagesalary->setText($this->_stage->salary)   ;
         $this->editstage->editstagearea->setValue($this->_stage->pa_id)   ;
     
        
    }
  
    public function OnStageDel($sender) {
         $stage = $sender->getOwner()->getDataItem();
         
         $conn= \ZDb\DB::getConnect() ;
         
         //проверка на  доки
         
         $conn->Execute("delete from prodstageagenda where  st_id=".$stage->st_id);
          
         ProdStage::delete($stage->st_id) ;
         $this->stagespan->stagelist->Reload();
    }
  
    public function onCanceStage($sender) {
         $this->editcardform->setVisible(false); 
         $this->editstage->setVisible(false); 
         $this->stagespan->setVisible(true); 
   
     }
   //техкарта
    public function OnCard($sender) {
         $this->editcardform->setVisible(true); 
         $this->editstage->setVisible(false); 
         $this->stagespan->setVisible(false); 
         
         $this->_stage = $sender->getOwner()->getDataItem(); 
         
         $this->editcardform->editcard->setText($this->_stage->card);
         $this->editcardform->stagenameh4->setText($this->_stage->stagename);
     } 
                                             
    public function OnSaveCard($sender) {
 
         $this->_stage->card =  $this->editcardform->editcard->getText();
         $this->_stage->save();
         
         $this->editcardform->setVisible(false); 
         $this->editstage->setVisible(false); 
         $this->stagespan->setVisible(true);          
         
     } 
    //просмотр 
    public function onView($sender) {
       $this->listpan->showpan->setVisible(true); 
       $this->_proc = $sender->getOwner()->getDataItem();
       $this->listpan->proclist->Reload();
       
       $this->goAnkor('showpan') ;
    }
     
}

/**
 *  Источник  данных  для   списка  документов
 */
class PProcListDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        return "";
    }

    public function getItemCount() {
        return ProdProc::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
       return  ProdProc::find($this->getWhere()," pp_id desc  ");
    }

    public function getItem($id) {

    }

}

class PStageListDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }  
    
    private function getWhere() {
        return "pp_id=".$this->page->_proc->pp_id;
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
