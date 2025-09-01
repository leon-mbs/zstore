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
use ZCL\DB\EntityDataSource;
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
    public $_proc     = null;
    public $_stage    = null;
    public $_prodlist = array();
    public $_itemlist = array();
    public $_emplist = array();


    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('ProdProcList')) {
            \App\Application::RedirectHome() ;
        }

        $this->add(new Panel("listpan"));
    
        $proclist = $this->listpan->add(new DataView('proclist', new PProcListDataSource($this), $this, 'proclistOnRow'));

        $this->listpan->add(new Paginator('pag', $proclist));
        $proclist->setPageSize(H::getPG());

        $this->listpan->add(new ClickLink('addnewproc', $this, "OnAddProc"));

        $this->add(new Form('editproc'))->setVisible(false);
        $this->editproc->add(new TextInput('editname'));
        $this->editproc->add(new TextInput('editbasedoc'));
        $this->editproc->add(new DropDownChoice('editstore', \App\Entity\Store::getList('disabled<>1'), H::getDefStore()));

        $this->editproc->add(new Date('editstartdateplan',strtotime('+1 day',time())));
        $this->editproc->add(new Date('editenddateplan',strtotime('+3 month',time())));
        $this->editproc->add(new TextArea('editnotes'));

        $this->editproc->add(new SubmitButton('save'))->onClick($this, 'OnSave');
        $this->editproc->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        //  $this->editproc->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

        //продукция
        $this->add(new Panel("prodspan"))->setVisible(false);
        $this->prodspan->add(new ClickLink('cancelprods'))->onClick($this, 'onCancelProds');
        $this->prodspan->add(new ClickLink('saveprods'))->onClick($this, 'onSaveProds');
        $this->prodspan->add(new Form('addprodform'))->onSubmit($this, 'onAddProd');
        $this->prodspan->addprodform->add(new DropDownChoice('additem', Item::findArray("itemname", "disabled<> 1 and item_type=" . Item::TYPE_PROD, "itemname"), 0));
        $this->prodspan->addprodform->add(new TextInput('addqty'));
        $this->prodspan->addprodform->add(new TextInput('addsnumber'));

        $this->prodspan->add(new DataView('proditemlist', new ArrayDataSource($this, "_prodlist"), $this, 'prodlistOnRow'));

        $this->add(new Panel("stagespan"))->setVisible(false);
        $this->stagespan->add(new ClickLink('backtoproc'))->onClick($this, 'onCancelProds');
        $this->stagespan->add(new ClickLink('addstage'))->onClick($this, 'onAddStage');
        $this->stagespan->add(new DataView('stagelist', new EntityDataSource("\\App\\Entity\\ProdStage", "", "stagename"), $this, 'stagelistOnRow'));


        $this->add(new Form('editstage'))->setVisible(false);
        $this->editstage->add(new TextInput('editstagename'));
 

        $this->editstage->add(new TextArea('editstagenotes'));

        $this->editstage->add(new DropDownChoice('editstagearea', \App\Entity\ProdArea::findArray('pa_name', "disabled<>1","pa_name")));
        $this->editstage->add(new SubmitButton('savestage'))->onClick($this, 'OnSaveStage');
        $this->editstage->add(new Button('cancelstage'))->onClick($this, 'onCanceStage');


        $this->add(new Form('editcardform'))->setVisible(false);
        $this->editcardform->add(new Label('stagenameh4'));
        $this->editcardform->add(new TextArea('editcard'));
        $this->editcardform->add(new SubmitButton('savecard'))->onClick($this, 'OnSaveCard');
        $this->editcardform->add(new Button('cancelcard'))->onClick($this, 'onCanceStage');


        $this->listpan->add(new Panel("showpan"))->setVisible(false);
        $this->listpan->showpan->add(new ClickLink('btnstinprocess', $this, 'onProcStatus'));
        $this->listpan->showpan->add(new ClickLink('btnstsuspend', $this, 'onProcStatus'));
        $this->listpan->showpan->add(new ClickLink('btnstcancel', $this, 'onProcStatus'));
        $this->listpan->showpan->add(new ClickLink('btnstclose', $this, 'onProcStatus'));

        
        $this->add(new Panel("itemspan"))->setVisible(false);
        $this->itemspan->add(new Label('stagename5')) ;
        $this->itemspan->add(new Form('edititemsform')) ;
        $this->itemspan->edititemsform->add(new DropDownChoice('edititem',[],0));
        $this->itemspan->edititemsform->add(new TextInput('editqty'));
        $this->itemspan->edititemsform->add(new SubmitButton('saveitem'))->onClick($this, 'saveitemOnClick');
        $this->itemspan->add(new DataView('detailitem', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailitemOnRow'))->Reload();
        $this->itemspan->add(new ClickLink('saveitems', $this, 'onSaveItems'));
        $this->itemspan->add(new ClickLink('cancelitems', $this, 'onCanceltems'));

        
        $this->add(new Panel("empspan"))->setVisible(false);
        $this->empspan->add(new Form('editempsform')) ;
        $this->empspan->add(new Label('stagename6')) ;
        $this->empspan->editempsform->add(new DropDownChoice('editemp',[],0));
        $this->empspan->editempsform->add(new TextInput('editktu'));
        $this->empspan->editempsform->add(new SubmitButton('saveemp'))->onClick($this, 'saveempOnClick');
        $this->empspan->add(new DataView('detailemp', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_emplist')), $this, 'detailEmpOnRow'))->Reload();
        $this->empspan->add(new ClickLink('saveemps', $this, 'onSaveEmps'));
        $this->empspan->add(new ClickLink('cancelemps', $this, 'onCanceEmps'));

        
        $this->listpan->add(new ClickLink('options'))->onClick($this, 'optionsfOnClick');
        
        $this->add(new Form('optionsform'))->setVisible(false);        
        $this->optionsform->onSubmit($this, 'saveopt');
        $this->optionsform->add(new DropDownChoice('editrole', \App\Entity\UserRole::findArray('rolename', "rolename <> 'admins' && disabled<>1 ", 'rolename'),0));          
        $this->optionsform->add(new ClickLink('cancelopt'))->onClick($this, 'cancelopt');
        
        
        
        $this->listpan->proclist->Reload();

    }

    public function proclistOnRow(\Zippy\Html\DataList\DataRow $row) {

        $p = $row->getDataItem();

        $row->add(new Label('name', $p->procname));
        $row->add(new Label('basedoc', $p->basedoc));
     
        $row->add(new Label('state', ProdProc::getStateName($p->state)));

        $row->add(new Label('startdate', H::fd($p->startdateplan)));
        $row->add(new Label('enddate', H::fd($p->enddateplan)));

        $row->add(new ClickLink('edit', $this, 'OnEdit'))->setVisible($p->state == 0);
        $row->add(new ClickLink('view'))->onClick($this, 'onView');
        $row->add(new ClickLink('stages'))->onClick($this, 'OnStages');
        $row->add(new ClickLink('copy'))->onClick($this, 'OnCopy');
        $row->add(new ClickLink('prods'))->onClick($this, 'OnProds');  
        $row->add(new ClickLink('delete', $this, 'deleteOnClick'))->setVisible($p->stagecnt == 0);
        $row->add(new Label('hasnotes'))->setVisible(strlen($p->notes) > 0);
        $row->hasnotes->setAttribute('title', $p->notes);

        if ($p->pp_id == ($this->_proc->pp_id ?? 0)) {
            $row->setAttribute('class', 'table-success');
        }

    }


    public function OnCopy($sender) {
        $proc = $sender->getOwner()->getDataItem();

        $this->_proc = $proc->clone();

        $this->listpan->proclist->Reload();

    }

    //новый процесс
    public function OnAddProc($sender) {

        $this->listpan->setVisible(false);
        $this->editproc->setVisible(true);
        $this->editproc->clean();
        $this->editproc->editstartdateplan->setDate(time()  + (3600*24));
        $this->editproc->editenddateplan->setDate(time()  + (15 *3600*24));
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

        $this->editproc->editname->setText($this->_proc->procname);
        $this->editproc->editstore->setValue($this->_proc->store);
         
        $this->editproc->editbasedoc->setText($this->_proc->basedoc);

        $this->editproc->editstartdateplan->setDate($this->_proc->startdateplan);
        $this->editproc->editenddateplan->setDate($this->_proc->enddateplan);
        $this->editproc->editnotes->setText($this->_proc->notes);


    }

    public function OnSave($sender) {

        $this->_proc->procname = $this->editproc->editname->getText();
        $this->_proc->store =(int) $this->editproc->editstore->getValue();
        $this->_proc->basedoc = $this->editproc->editbasedoc->getText();

        $this->_proc->notes = $this->editproc->editnotes->getText();
        $this->_proc->startdateplan = $this->editproc->editstartdateplan->getDate();
        $this->_proc->enddateplan = $this->editproc->editenddateplan->getDate();

        if($this->_proc->store==0){
            $this->setError('Не вибрано склад')  ;
            return;
        }
        
        $this->_proc->state= $this->_proc->pp_id >0 ? ProdProc::STATE_STOPPED: ProdProc::STATE_NEW ; 
       
        $this->_proc->save();

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

        $it = Item::load($sender->additem->getValue());
        if ($item == null) {
            return;
        }
        
        $item = new \App\DataItem() ;
  
        $item->item_id = $it->item_id;
        $item->itemname = $it->itemname;
        $item->item_code = $it->item_code;
        $item->qty = $sender->addqty->getText();
        $item->snumber = $sender->addsnumber->getText();
        if (($item->qty > 0) == false) {
            return;
        }
        $this->_prodlist[$item->item_id] = $item;

        $this->prodspan->proditemlist->Reload();
        $sender->clean();

    }

    public function prodlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $p = $row->getDataItem();
        $row->add(new Label('proditemname', $p->itemname));
        $row->add(new Label('proditemqty', $p->qty));
        $row->add(new Label('proditemsnumber', $p->snumber));
        $row->add(new ClickLink('proditemdel'))->onClick($this, 'OnProdDel');

    }

    public function OnProdDel($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->_prodlist = array_diff_key($this->_prodlist, array($item->item_id => $this->_prodlist[$item->item_id]));

        $this->prodspan->proditemlist->Reload();

    }

    public function onSaveProds($sender) {
        $this->_proc->prodlist = $this->_prodlist;
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
        $this->stagespan->stagelist->getDataSource()->setWhere("pp_id=".$this->_proc->pp_id);
        $this->stagespan->stagelist->Reload();
    }

    public function stagelistOnRow($row) {
        $s = $row->getDataItem();

        $row->add(new Label('stagename', $s->stagename));
        $row->add(new Label('stageareaname', $s->pa_name));
        $row->add(new Label('stagestate', ProdStage::getStateName($s->state)));

        $row->add(new ClickLink('stageedit', $this, 'OnStageEdit'))->setVisible($s->state != ProdStage::STATE_FINISHED && $s->state != ProdStage::STATE_INPROCESS );
        $row->add(new ClickLink('stagedel', $this, 'OnStageDel'))->setVisible($s->state != ProdStage::STATE_FINISHED);
        $row->add(new ClickLink('stagecard', $this, 'OnCard'));
        $row->add(new ClickLink('stageitems', $this, 'OnItems'))->setVisible($s->state != ProdStage::STATE_FINISHED );
        $row->add(new ClickLink('stageemps', $this, 'OnEmps'))->setVisible($s->state != ProdStage::STATE_FINISHED);


    }

    public function OnSaveStage($sender) {

        $this->_stage->stagename = $this->editstage->editstagename->getText();
        $this->_stage->notes = $this->editstage->editstagenotes->getText();
        $this->_stage->pa_id = $this->editstage->editstagearea->getValue();
  
        if ($this->_stage->pa_id == 0) {
            $this->setError("Не обрано виробничу ділянку");
            return;
        }
        $this->_stage->state= $this->_stage->st_id >0 ? ProdStage::STATE_STOPPED: ProdStage::STATE_NEW ; 
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

        $this->editstage->editstagename->setText($this->_stage->stagename);
        $this->editstage->editstagenotes->setText($this->_stage->notes);
        $this->editstage->editstagearea->setValue($this->_stage->pa_id);


    }

    public function OnStageDel($sender) {
        $stage = $sender->getOwner()->getDataItem();

        
        //проверка на  доки
        $cnt = \App\Entity\doc\Document::findCnt("meta_name in ('ProdMove','IncomeService', 'ProdReceipt','ProdIssue' ) and ( content  like '%<st_id>{$stage->st_id}</st_id>%' or content like '%<psto>{$stage->st_id}</psto>%' or content like '%<psfrom>{$stage->st_id}</psfrom>%'   )  ");
        if($cnt >0) {
            $this->setError('Вже  ствворено документи на  етап') ;
            return;
        }
        ProdStage::delete($stage->st_id);
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

        $this->_stage->card = $this->editcardform->editcard->getText();
        $this->_stage->save();

        $this->editcardform->setVisible(false);
        $this->editstage->setVisible(false);
        $this->stagespan->setVisible(true);

    }


    //сотрудники
    public function OnEmps($sender) {
        $this->_stage = $sender->getOwner()->getDataItem();
        $this->_emplist = $this->_stage->emplist;
        
        $this->empspan->editempsform->clean();
        
        $r="";
        if($this->_proc->role >0) {
            $r=" and employee_id in ( select employee_id from users_view where disabled<>1 and role_id= {$this->_proc->role}) "; 
        }
        $ems=  \App\Entity\Employee::findArray("emp_name", "disabled<>1 {$r}", "emp_name") ;
        
        $this->empspan->editempsform->editemp->setOptionList($ems) ;
        $this->empspan->editempsform->editemp->setValue(0);
        $this->empspan->setVisible(true);
        $this->stagespan->setVisible(false);
        $this->empspan->stagename6->setText($this->_stage->stagename);
        
        $this->empspan->detailemp->Reload() ;
        
       
    }

    public function saveempOnClick(  $sender) {
        $id = $this->empspan->editempsform->editemp->getValue();
        if ($id == 0) {
            return;
        }
       // $p = \App\EntityCommonMark\Employee::load($id);
        $emp = new \App\DataItem() ;
        $emp->employee_id = $id;
        $emp->emp_name = $this->empspan->editempsform->editemp->getValueName();
        $emp->ktu = doubleval($this->empspan->editempsform->editktu->getText() );
        
        $this->_emplist[$emp->employee_id] = $emp;
        $this->empspan->detailemp->Reload() ;

        $this->empspan->editempsform->clean();          
    }
    public function onDelEmp(  $sender) {
        $emp = $sender->getOwner()->getDataItem();
        $this->_emplist = array_diff_key($this->_emplist, array($emp->employee_id => $this->_emplist[$emp->employee_id]));
        $this->empspan->detailemp->Reload() ;   
    }
    public function onCanceEmps(  $sender) {
        $this->empspan->setVisible(false);
        $this->stagespan->setVisible(true);
        
    }
    public function onSaveEmps(  $sender) {
        $empids =[] ;
        $ktu = 0;
        foreach($this->_emplist as $emp) {
           $ktu += $emp->ktu; 
           $empids[] =  $emp->employee_id;       
        } 
        if($ktu != 1 && count($this->_emplist) >0) {
            $this->setError('Сумма  КТУ повинна дорiвнювати 1 ') ;
            return;
        }

        $this->_stage->empids='' ;
        if( count($this->_emplist) > 0) {
           $this->_stage->empids = '#'.implode('#',$empids).'#';   // для фильтра
         
        }
    
        
        $this->_stage->emplist = $this->_emplist;
        $this->_stage->save();
        $this->empspan->setVisible(false);
        $this->stagespan->setVisible(true);
    }
    
    public function detailEmpOnRow(  $row) {

            $emp = $row->getDataItem();

            $row->add(new Label('emp_name', $emp->emp_name));
            $row->add(new Label('empktu', $emp->ktu));
            $row->add(new ClickLink('deleteemp', $this, 'onDelEmp')) ;
         
    }
    //ТМЦ
    public function OnItems($sender) {
        $this->_stage = $sender->getOwner()->getDataItem();
        $this->_itemlist = $this->_stage->itemlist;
    
        $this->itemspan->edititemsform->clean();
        $this->itemspan->edititemsform->edititem->setOptionList(  Item::findArray("itemname", "disabled<>1 and item_type in(4,5) ", "itemname")) ;
        $this->itemspan->edititemsform->edititem->setValue(0);
         
        $this->itemspan->setVisible(true);
        $this->stagespan->setVisible(false);
        $this->itemspan->stagename5->setText($this->_stage->stagename);
        $this->itemspan->detailitem->Reload() ;
       
    }
    
    public function saveitemOnClick(  $sender) {
        $id = $this->itemspan->edititemsform->edititem->getValue();
        if ($id == 0) {
            return;
        }
        $it= Item::load($id) ;
        $item = new \App\DataItem() ;
        $item->item_id = $id;
        $item->itemname = $it->itemname;
        $item->item_code = $it->item_code;
        $item->quantity = H::fqty($this->itemspan->edititemsform->editqty->getText() );
        
        $this->_itemlist[$item->item_id] = $item;
        $this->itemspan->detailitem->Reload() ;

        $this->itemspan->edititemsform->clean();        
    }
    public function onDelItem(  $sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));
        $this->itemspan->detailitem->Reload() ;   
        
    }
    public function onCanceltems(  $sender) {
        $this->itemspan->setVisible(false);
        $this->stagespan->setVisible(true);       
    }
    public function onSaveItems(  $sender) {
    
        
        $this->_stage->itemlist = $this->_itemlist;
        $this->_stage->save();   
        $this->itemspan->setVisible(false);
        $this->stagespan->setVisible(true);       
       
       
    }
    
    
    public function detailItemOnRow(  $row) {

            $item = $row->getDataItem();

            $row->add(new Label('itemname', $item->itemname));
            $row->add(new Label('item_code', $item->item_code));
            $row->add(new Label('item_qty', $item->quantity));
            $row->add(new ClickLink('deleteitem', $this, 'onDelItem')) ;
          
    }
    
    
    //просмотр
    public function onView($sender) {
        $pan = $this->listpan->showpan;
        $pan->setVisible(true);
        $this->_proc = $sender->getOwner()->getDataItem();

        $pan->btnstinprocess->setVisible(false);
        $pan->btnstsuspend->setVisible(false);
        $pan->btnstcancel->setVisible(false);
        $pan->btnstclose->setVisible(false);
        if ($this->_proc->state == ProdProc::STATE_NEW) {
            $pan->btnstinprocess->setVisible(true);
        }
        if ($this->_proc->state == ProdProc::STATE_STOPPED) {
            $pan->btnstinprocess->setVisible(true);
            $pan->btnstclose->setVisible(true);
            $pan->btnstcancel->setVisible(true);
        }
        if ($this->_proc->state == ProdProc::STATE_INPROCESS) {
            $pan->btnstsuspend->setVisible(true);
            $pan->btnstclose->setVisible(true);
            $pan->btnstcancel->setVisible(true);
        }


        $conn = \ZDB\DB::getConnect();

        //этапы
        $stages = ProdStage::find('pp_id=' . $this->_proc->pp_id);
        $this->_tvars['stagelist'] = array();
        foreach ($stages as $st) {
            $this->_tvars['stagelist'][] = array(
                'stagename'   => $st->stagename,
                'stagestatus' => ProdStage::getStateName($st->state),
                'stagearea'   => $st->pa_name
            );
        }


        $sql = "
          select i.item_type,i.item_id,i.itemname  , sum(e.quantity) as qty,  sum((partion )*quantity) as summa
              from entrylist_view  e

              join items i on e.item_id = i.item_id
              join documents_view d on d.document_id = e.document_id
               where e.item_id >0   
               and d.meta_name in ('ProdIssue','ProdReceipt')
               and d.content like '%<pp_id>{$this->_proc->pp_id}</pp_id>%'  
               group by i.item_type,i.item_id, i.itemname 
               order  by i.itemname
        ";

        $items = $conn->Execute($sql);

        $this->_tvars['prodstuff'] = array();
        foreach ($items as $item) {
            if ($item['qty'] < 0 && $item['item_type'] != Item::TYPE_PROD) {

                $this->_tvars['prodstuff'][] = array(
                    'itemname'   => $item['itemname'],
                    'itemamount' => H::fa(0 - $item['summa']),
                    'itemqty'    => H::fqty(0 - $item['qty'])
                );

            }
        }


        $this->_prodlist = $this->_proc->prodlist;
       

        $this->_tvars['prodready'] = array();
        foreach ($items as $item) {
            if ($item['qty'] > 0 && $item['item_type'] == Item::TYPE_PROD) {

                
                $plan = 0;
                if ($this->_prodlist[$item['item_id']] instanceof Item) {
                    $plan = $this->_prodlist[$item['item_id']]->qty;
                }

                $this->_tvars['prodready'][] = array(
                    'itemname' => $item['itemname'],
                    'itemplan' => $plan,
                    'itemfact' => H::fqty($item['qty'])
                );
            }
        }

        foreach ($this->_prodlist as $id => $p) {
            

            $this->_tvars['prodready'][] = array(
                'itemname' => $p->itemname,
                'itemsnumber' => $p->snumber,
                'itemplan' => $p->qty,
                'itemfact' => H::fqty(0)
            );
        }

        $this->_tvars['prodservice'] = array();

        $this->listpan->proclist->Reload();
        $this->goAnkor('showpan');
    }
 
    public function onProcStatus($sender) {

        $stages = ProdStage::find('pp_id=' . $this->_proc->pp_id);


        if ($sender->id == "btnstinprocess") {
            $this->_proc->state = ProdProc::STATE_INPROCESS;
        }
        if ($sender->id == "btnstsuspend") {
            $this->_proc->state = ProdProc::STATE_STOPPED;

            foreach($stages as $st) {
                $st->state= ProdStage::STATE_STOPPED;
                $st->save();
            }

        }
        if ($sender->id == "btnstclose") {
            $this->_proc->state = ProdProc::STATE_FINISHED;
            foreach($stages as $st) {
                $st->state= ProdStage::STATE_FINISHED;
                $st->save();
            }



        }
        if ($sender->id == "btnstcancel") {
            $this->_proc->state = ProdProc::STATE_CANCELED;
            foreach($stages as $st) {
                $st->state= ProdStage::STATE_STOPPED;
                $st->save();
            }


        }
        $this->_proc->save();
        $this->listpan->showpan->setVisible(false);
        $this->listpan->proclist->Reload();
    }
    
  // настройки  
  public function optionsfOnClick($sender) {
        $options = System::getOptions('common');
        
        $this->optionsform->editrole->setValue($options['prodrole'] ?? 0);
        
        $this->listpan->setVisible(false);
       
        $this->optionsform->setVisible(true);           
  }  
 
  public function cancelopt($sender) {
     
        $this->listpan->setVisible(true);
        $this->optionsform->setVisible(false);        
     
 }
 
  public function saveopt($sender) {
        $options = System::getOptions('common');
         
        $options['prodrole'] = $this->optionsform->editrole->getValue()  ;
                                              
     
        System::setOptions('common', $options);        
        
        $this->listpan->setVisible(true);
        $this->optionsform->setVisible(false);        
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
        return ProdProc::find($this->getWhere(), " state asc  ");
    }

    public function getItem($id) {

    }

}
