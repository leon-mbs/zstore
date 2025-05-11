<?php

namespace App\Pages\Reference;

use App\Entity\Employee;
use App\Entity\Equipment;
use App\Entity\EqEntry;
use App\Entity\ProdArea;
use App\Helper;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

//справочник  оборудования
class EqList extends \App\Pages\Base
{
    private $_item;
 
    private $_blist;

    public function __construct($id=0) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('EqList')) {
            return;
        }
        $this->_blist = \App\Entity\Branch::getList(\App\System::getUser()->user_id);

        $types=[];
        $types[Equipment::IYPR_EQ] = Equipment::getTypeName(Equipment::IYPR_EQ)  ;
        $types[Equipment::IYPR_OS] = Equipment::getTypeName(Equipment::IYPR_OS)  ;
        $types[Equipment::IYPR_NMA] = Equipment::getTypeName(Equipment::IYPR_NMA)  ;
         
        
        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchemp', Employee::findArray("emp_name", "disabled<>1", "emp_name"), 0));
        $this->filter->add(new DropDownChoice('searchtype',  $types, 0));
        $this->filter->add(new CheckBox('showdis'));


      
        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new DataView('eqlist', new EQDS($this), $this, 'eqlistOnRow'));
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->itemtable->eqlist->setPageSize(Helper::getPG());
        $this->itemtable->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->eqlist));

        $this->itemtable->eqlist->Reload();

        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
        $this->itemdetail->add(new DropDownChoice('editemp', Employee::findArray("emp_name", "disabled<>1", "emp_name"), 0));

          
        $this->itemdetail->add(new TextInput('editserial'));
        $this->itemdetail->add(new DropDownChoice('edittype', $types, 0));
        $this->itemdetail->add(new TextInput('editinvnumber'));
        $this->itemdetail->add(new TextArea('editdescription'));
        $this->itemdetail->add(new CheckBox('editdisabled'));
     
        $this->itemdetail->add(new DropDownChoice('editbranch', $this->_blist, 0));
        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        
        $this->add(new Panel('infopan'))->setVisible(false);        
        $this->infopan->add(new ClickLink('backd',$this,'viewBack'));
        $this->infopan->add(new ClickLink('addop',$this,'createDoc'));
        $this->infopan->add(new ClickLink('showall',$this,'showAll'));
        $this->infopan->add(new Label('oname' ));
        
        if($id>0) {
            $this->show($id)  ;
        }
    }

    public function eqlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new Label('eq_name', $item->eq_name));
        $row->add(new Label('invnumber', $item->invnumber));
        
        $pa_name='';
        
        $eq= \App\Entity\EqEntry::getFirst('optype=5 and eq_id='.$item->eq_id,'id desc') ;
        
        if($eq != null) {
            $d = \App\Entity\Doc\Document::load($eq->document_id)  ;
            $pa_name = $d->headerdata['pa_name'] ??'';
        }
        
        $row->add(new Label('pa_name', $pa_name));
        $row->add(new Label('notes', $item->description));
      
        $row->add(new Label('branch', $this->_blist[$item->branch_id] ??''));

        $row->add(new ClickLink('barcode'))->onClick($this, 'printOnClick', true);
     
        $row->add(new ClickLink('view'))->onClick($this, 'viewOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {


        $item = $sender->owner->getDataItem();

        $del=  Equipment::delete($item->eq_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->itemtable->eqlist->Reload();
        $this->resetURL();
    }
  

    public function editOnClick($sender) {
        $this->_item = $sender->owner->getDataItem();
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editname->setText($this->_item->eq_name);

        $this->itemdetail->editdisabled->setChecked($this->_item->disabled);

        $this->itemdetail->editbranch->setValue($this->_item->branch_id);

        $this->itemdetail->editdescription->setText($this->_item->description);
        $this->itemdetail->editinvnumber->setText($this->_item->invnumber);
        $this->itemdetail->editserial->setText($this->_item->serial);
        $this->itemdetail->editemp->setValue($this->_item->resemp_id);
    }

    public function addOnClick($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        $b = \App\System::getBranch();
        $this->itemdetail->editbranch->setValue($b > 0 ? $b : 0);
        
       
        $this->_item = new Equipment();
    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
      
    }

    public function OnFilter($sender) {
        $this->itemtable->eqlist->Reload();
    }

    public function OnSubmit($sender) {
        if (false == \App\ACL::checkEditRef('EqList')) {
            return;
        }

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);

        $this->_item->eq_name = $this->itemdetail->editname->getText();
        $this->_item->resemp_id = $this->itemdetail->editemp->getValue();
        $this->_item->resemp_name = $this->itemdetail->editemp->getValueName();
      
        $this->_item->invnumber = $this->itemdetail->editinvnumber->getText();
        $this->_item->branch_id = $this->itemdetail->editbranch->getValue();
        if ($this->_tvars['usebranch'] == true && $this->_item->branch_id == 0) {
            $this->setError('Виберіть філію');
            return;
        }
        $this->_item->serial = $this->itemdetail->editserial->getText();
        $this->_item->description = $this->itemdetail->editdescription->getText();
        $this->_item->type = $this->itemdetail->edittype->getValue()  ;

        $this->_item->save();

        $this->itemtable->eqlist->Reload();
    }
    public function viewOnClick($sender) {
        $this->_item= $sender->getOwner()->getDataItem()  ;
        $this->show($this->_item->eq_id)  ;
    }
    public function show($id) {
        $this->infopan->setVisible(true);
        $this->itemtable->setVisible(false);
        
        $this->_item = Equipment::load($id) ;
        $this->infopan->oname->setText($this->_item->eq_name);
        
        $this->viewList($id);
        
        
    }
    public function showAll( ) {
       $this->viewList($this->_item->eq_id,true)  ;
    }
    public function viewList($id,$all=false) {
        $this->_tvars['oplist'] =[];
        $where="eq_id=".$id;
        if(!$all)  {
           $where  .= " and optype <> ". EqEntry::OP_MOVE; 
        }
        $total = 0;
        
        foreach(EqEntry::findYield($where,"document_date,id") as $ee )  {
         
           $det = ""; 
           
           $doc = \App\Entity\Doc\Document::load($ee->document_id)  ;
           
           if($doc->customer_id >0 ) {
              $det = $det. ' '. $doc->customer_name;  
           }
           if( ( $doc->headerdata['pa_id'] ??0) > 0 ) {
              $det = $det. ' '. $doc->headerdata['pa_name'];  
           }
           if( ($doc->headerdata['emp_id'] ??0) > 0 ) {
              $det = $det. ' '. $doc->headerdata['emp_name'];  
           }
           if( ( $doc->headerdata['item_id'] ??0) > 0 ) {
              $det = $det. ' '. $doc->headerdata['item_name'];  
           }
            
           $total += $ee->amount; 
            
           $this->_tvars['oplist'][]=array(
             'opdate'=>  Helper::fd($ee->document_date) , 
             'number'=>   $ee->document_number , 
             'amount'=>  Helper::fa($ee->amount) , 
             'opname'=> EqEntry::getOpName($ee->optype) , 
             'det'=> $det,   
             'notes'=> $ee->notes   
           ) ;
        }
        
       $this->_tvars['total']= Helper::fa($total)   ;
    }
    public function viewBack($sender) {
        $this->infopan->setVisible(false);
        $this->itemtable->setVisible(true);
    }
    public function createDoc($sender) {
        \App\Application::Redirect("\\App\\Pages\\Doc\\EQ",0,$this->_item->eq_id);
    }
  public function printOnClick($sender) {

        $printer = \App\System::getOptions('printer') ;
        $user = \App\System::getUser() ;

        $item = $sender->getOwner()->getDataItem();
        $header = [];
        if(intval($user->prtypelabel) == 0) {
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $data = " src=\"data:image/png;base64," . base64_encode($generator->getBarcode($item->invnumber, $printer['barcodetype'])) . "\"";
                                      
            $report = new \App\Report('eq.tpl');
            $header['src'] = $data;

            $html =  $report->generate($header);                  

            $this->addAjaxResponse("  $('#tag').html('{$html}') ; $('#pform').modal()");
            return;
        }
       
        try {
            $buf=[];
            if(intval($user->prtypelabel) == 1) {
                
               $report = new \App\Report('eq_ps.tpl');
               $header['barcode'] = $item->invnumber;

                $html =  $report->generate($header);              
                
                $buf = \App\Printer::xml2comm($html);
            }
            if(intval($user->prtypelabel) == 2) {
                $rows=[];
              
                $report = new \App\Report('eq_ts.tpl');
                $header['barcode'] = $item->invnumber;

                $text = $report->generate($header, false);
                $r = explode("\n", $text);
                foreach($r as $row) {
                    $row = str_replace("\n", "", $row);
                    $row = str_replace("\r", "", $row);
                    $row = trim($row);
                    if($row != "") {
                       $rows[] = $row;  
                    }
                   
                }           
                
                $buf = \App\Printer::arr2comm($rows);
            }
       
            $b = json_encode($buf) ;
            $this->addAjaxResponse(" sendPSlabel('{$b}') ");

        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }


    }

}

class EQDS implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $emp = $form->searchemp->getValue();
        $type = $form->searchtype->getValue();
        $showdis = $form->showdis->isChecked();

        if ($emp > 0) {
            $where  = $where . " and detail like '%<resemp_id>{$emp}</resemp_id>%' ";
        }
        if ($type > 0) {
            $where  = $where . " and type = ".$type;
        }
        if ($showdis > 0) {

        } else {
            $where  = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            $text = Equipment::qstr(  $text  );
            $_text = Equipment::qstr('%' . $text . '%');
            $where = $where . " and (invnumber = {$text} or eq_name like {$_text} or detail like {$_text} )  ";
        }
        
        
        
        
        return $where;
    }

    public function getItemCount() {
        return Equipment::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Equipment::find($this->getWhere(), "eq_name asc", $count, $start);
    }

    public function getItem($id) {
        return Equipment::load($id);
    }

}
