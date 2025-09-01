<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода документа перемещение между этапами
 */
class ProdMove extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = -1;

    /**
    * @param mixed $docid      редактирование
    * @param mixed $basedocid  создание на  основании

    */
    public function __construct($docid = 0, $basedocid = 0,$st_id=0 ) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

         
        $stlist = \App\Entity\ProdProc::findArray('procname','state = 1','procname' );
                     
        
        $this->docform->add(new DropDownChoice('pp', $stlist, 0))->onChange($this,'onPP');
        $this->docform->add(new DropDownChoice('psfrom', [], 0));
        $this->docform->add(new DropDownChoice('psto', [], 0));
        $this->docform->add(new DropDownChoice('emp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name"))) ;
  
        $this->docform->add(new TextArea('notes'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");

        $this->editdetail->add(new TextInput('editserial'));

        $this->editdetail->add(new DropDownChoice('edititem', Item::findArray('itemname', 'disabled<>1 and  item_type in(4,5)', 'itemname')));
     
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);


            $this->docform->pp->setValue($this->_doc->headerdata['pp']);
            $this->onPP($this->docform->pp)  ;            
            $this->docform->psfrom->setValue($this->_doc->headerdata['psfrom']);
            $this->docform->psto->setValue($this->_doc->headerdata['psto']);
            $this->docform->emp->setValue($this->_doc->headerdata['emp']);
         
            $this->docform->notes->setText($this->_doc->notes);
            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
            
          
                 
        } else {
            $this->_doc = Document::create('ProdMove');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
            }
            
            if ($st_id > 0) {
                $st = \App\Entity\ProdStage::load($st_id);
                $this->docform->pp->setValue($st->pp_id);
                $this->onPP($this->docform->pp)  ;
                $this->docform->psfrom->setValue($st_id);
                $this->docform->emp->setVisible(false) ;
                $st= \App\Entity\ProdStage::load($st_id);
                $i=1;
                foreach($st->itemlist as $it){
                    $item = Item::load($it->item_id) ;
                    $item->quantity = $it->quantity;
                    $this->_itemlist[$i++]=$item;
                }
                
            }   


        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function onPP($sender) {
       $stlist = [];
       foreach( \App\Entity\ProdStage::find('state = 1 and pp_id='.$sender->getValue(),'stagename' ) as $i=>$v){
          $stlist[$i] = $v->stagename ." {$v->pa_name}"; 
       }
       $this->docform->psfrom->setOptionList($stlist);
       $this->docform->psto->setOptionList($stlist);
                    
    }
    
    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));

        $row->add(new Label('quantity', H::fqty($item->quantity)));

        $row->add(new Label('snumber', $item->snumber));

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
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
        $this->editdetail->editquantity->setText("1");

        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);

       
        $this->editdetail->edititem->setValue($item->item_id);
        $this->editdetail->editserial->setValue($item->snumber);

        $this->_rowid =  array_search($item, $this->_itemlist, true);

    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edititem->getValue();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }
        
        $st_id=$this->docform->psfrom->getValue();
        
        if($st_id >0) {
            $st= \App\Entity\ProdStage::load($st_id);
            
            if( count($st->itemlist)>0) {
               $ids= array_keys($st->itemlist) ; 
               
               if(!in_array($id,$ids)) {
                    $this->setError( "ТМЦ не в перелiку  на  етапi");
                    return;
               }
            }
        }    
        $item = Item::load($id);


        $item->quantity = $this->editdetail->editquantity->getText();
        $item->snumber = $this->editdetail->editserial->getText();


        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("Потрібна партія виробника");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($store_id);

            if (in_array($item->snumber, $slist) == false) {
                $this->setError('Невірний номер серії');
                return;
            }
        }


        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }
 
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edititem->setValue(0);
      
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editserial->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edititem->setValue(0);
    

        $this->editdetail->editquantity->setText("1");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();


        $this->_doc->headerdata['pp'] = $this->docform->pp->getValue();
        $this->_doc->headerdata['ppname'] = $this->docform->pp->getValueName();
        $this->_doc->headerdata['psfrom'] = $this->docform->psfrom->getValue();
        $this->_doc->headerdata['psfromname'] = $this->docform->psfrom->getValueName();
        $this->_doc->headerdata['psto'] = $this->docform->psto->getValue();
        $this->_doc->headerdata['pstoname'] = $this->docform->psto->getValueName();
        $this->_doc->headerdata['emp'] = $this->docform->emp->getValue();
        $this->_doc->headerdata['empname'] = $this->docform->emp->getValueName();
     
          
        if($this->_doc->headerdata['psfrom'] >0) {
            $st= \App\Entity\ProdStage::load($this->_doc->headerdata['psfrom']);
            $this->_doc->headerdata['parea'] = $st->pa_id;
        }     
       
 
        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->amount = 0;
        $this->_doc->payamount = 0;
        if ($this->checkForm() == false) {
            return;
        }

        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
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
            App::RedirectBack();
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );
            return;
        }
    }

   
    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введіть номер документа');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("Не введено ТМЦ");
        }
        if (($this->_doc->headerdata['pp'] > 0) == false) {
            $this->setError("Не обрано процесс");
        }
        if (($this->_doc->headerdata['psfrom'] > 0) == false) {
            $this->setError("Не обрано етапи");
        }
        if (($this->_doc->headerdata['psto'] > 0) == false) {
            $this->setError("Не обрано етапи");
        }
        if ($this->_doc->headerdata['psto'] > 0  &&  $this->_doc->headerdata['psto']==$this->_doc->headerdata['psfrom'] ) {
            $this->setError("Етапи мають бути різні");
        }

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoItem($sender) {

        $text = trim($sender->getText());
        $like = Item::qstr('%' . $text . '%');

        $criteria = " disabled <> 1 and  item_type   in (2,5) and  (itemname like {$like} or item_code like {$like}   )";
        
        return Item::findArray("itemname",$criteria,"itemname");
    }

}
