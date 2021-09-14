<?php

namespace App\Pages\Register;

use App\Entity\ProdProc;
use App\Entity\ProdStage;
 
use App\Helper as H;
use App\System;
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
 
    private $_proc    = null;
   

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
        $this->editproc->add(new Button('delete'))->onClick($this, 'deleteOnClick');
      
        $proclist->Reload();
     
    }
 
    public function proclistOnRow(\Zippy\Html\DataList\DataRow $row) {
      
       
        $p = $row->getDataItem();

        $row->add(new Label('name', $p->procname));

        $row->add(new Label('basedoc', $p->basedoc));

        $row->add(new Label('snumber', $p->snumber));

        $row->add(new Label('state', ProdProc::getStateName($p->state)  ));

        
        
     //   $row->add(new Label('datestart', H::fd($doc->paydate)));
    //    $row->add(new Label('dateend', H::fd($doc->paydate)));
      
          $row->add(new ClickLink('edit'))->onClick($this, 'OnEdit');
          $row->add(new ClickLink('view' ))->onClick($this, 'onView');
          $row->add(new ClickLink('stages'))->onClick($this, 'OnStages');
          $row->add(new ClickLink('copy'))->onClick($this, 'OnCopy');

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
        
        ProdProc::delete($this->_proc->pp_id);
   //проверка
        $this->listpan->setVisible(true); 
        $this->editproc->setVisible(false); 
 
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
