<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Store;
use App\Entity\IOState;
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
 * Страница  ввода оприходование товаров
 */
class AdvanceRep extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_rowid     = -1;
    private $_basedocid = 0;

    /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');
        $this->docform->add(new Label('total'));
        $this->docform->add(new \Zippy\Html\Form\File('scan'));
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new DropDownChoice('storeemp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name"))) ;
        $this->docform->add(new DropDownChoice('emp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name"))) ;
        $this->docform->add(new DropDownChoice('exmf', \App\Entity\MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new TextInput('examount'));
        $this->docform->add(new TextInput('spentamount'));
        $this->docform->add(new DropDownChoice('spenttype', IOState::getTypeListAdv(), IOState::TYPE_COMMON_OUTCOME));
    
        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutocompleteItem');
        $this->editdetail->edititem->onChange($this, 'OnChangeItem', true);
 
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editsnumber'));
        $this->editdetail->add(new Date('editsdate'));

        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');


        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new TextInput('editnewbrand'));
        $this->editnewitem->add(new TextInput('editnewmsr'));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::getList(), 0));
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');

        $this->add(new Form('editsnitem'))->setVisible(false);
        $this->editsnitem->add(new AutocompleteTextInput('editsnitemname'))->onText($this, 'OnAutocompleteItem');
        $this->editsnitem->editsnitemname->onChange($this, 'OnChangeItem', true);
        $this->editsnitem->add(new TextInput('editsnprice'));
        $this->editsnitem->add(new TextArea('editsn'));
        $this->editsnitem->add(new Button('cancelsnitem'))->onClick($this, 'cancelrowOnClick');
        $this->editsnitem->add(new SubmitButton('savesnitem'))->onClick($this, 'savesnOnClick');
        $this->docform->add(new ClickLink('opensn', $this, "onOpensn"));


        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            if($this->_doc->state== Document::STATE_NEW) {
                $this->_doc->document_date = time() ;               
            }
            $this->docform->document_date->setDate($this->_doc->document_date);


            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->emp->setValue($this->_doc->headerdata['emp']);
            $this->docform->storeemp->setValue($this->_doc->headerdata['storeemp']);
            $this->docform->exmf->setValue($this->_doc->headerdata['exmf']);
            $this->docform->spenttype->setValue($this->_doc->headerdata['spenttype']);
            $this->docform->examount->setText($this->_doc->headerdata['examount']);
            $this->docform->spentamount->setText($this->_doc->headerdata['spentamount']);
            $this->docform->total->setText($this->_doc->headerdata['total']);
            $this->docform->notes->setText($this->_doc->notes);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('AdvanceRep');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                   
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }

        $this->docform->detail->Reload();
        $this->calcTotal();
       
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? \App\Helper::fd($item->sdate) : ''));

        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->edit->setVisible($item->old == false);
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));
        $this->calcTotal();

        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {

        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->_rowid = -1;

    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);

        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setValue($item->itemname);
        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editsnumber->setText($item->snumber);
        $this->editdetail->editsdate->setDate($item->sdate);

        $this->_rowid =  array_search($item, $this->_itemlist, true);
    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edititem->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getDouble();
        $item->price = $this->editdetail->editprice->getDouble();

        if ($item->price == 0) {
            $this->setWarn("Не вказана ціна");
        }

        $item->snumber = trim($this->editdetail->editsnumber->getText());
        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("Потрібна партія виробника");
            return;
        }
        $item->sdate = $this->editdetail->editsdate->getDate();
        if ($item->sdate == false) {
            $item->sdate = '';
        }
        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("Потрібна партія виробника");
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
        $this->calcTotal();
        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("");
        $this->editdetail->editsnumber->setText("");
        $this->editdetail->editsdate->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');
     $this->editsnitem->setVisible(false);
    
        $this->editdetail->editquantity->setText("1");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $this->_doc->notes = $this->docform->notes->getText();
        $file = $this->docform->scan->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("Файл більше 10 МБ!");
            return;
        }


        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storename'] = $this->docform->store->getValueName();
        $this->_doc->headerdata['emp'] = $this->docform->emp->getValue();
        $this->_doc->headerdata['empname'] = $this->docform->emp->getValueName();
        $this->_doc->headerdata['storeemp'] = $this->docform->storeemp->getValue();
        $this->_doc->headerdata['storeempname'] = $this->docform->storeemp->getValueName();
        $this->_doc->headerdata['exmf'] = $this->docform->exmf->getValue();
        $this->_doc->headerdata['spenttype'] = $this->docform->spenttype->getValue();
        $this->_doc->headerdata['spenttypename'] = $this->docform->spenttype->getValueName();
        $this->_doc->headerdata['examount'] = $this->docform->examount->getDouble();
        $this->_doc->headerdata['spentamount'] = $this->docform->spentamount->getDouble();
        $this->_doc->headerdata['total'] = $this->docform->total->getText();

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->amount =  doubleval( $this->docform->total->getText() ) + doubleval( $this->_doc->headerdata['examount'])+ doubleval( $this->_doc->headerdata['spentamount'] );
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


            if ($file['size'] > 0) {
                $id = H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
                $this->_doc->headerdata["scan"] = $id;
                $this->_doc->save();
                 
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

    private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;
            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (strlen(trim($this->docform->document_number->getText())) == 0) {
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
                                                           

        if (($this->docform->store->getValue() > 0) == false &&  doubleval( $this->docform->examount->getText() ) > 0) {
            $this->setError("Не обрано касу");
        }

        if (($this->docform->exmf->getValue() > 0) == false && count($this->_itemlist) > 0) {
            $this->setError("Не обрано склад");
        }

        if (($this->docform->emp->getValue() > 0) == false  ) {
            $this->setError("Не обрано співробітника");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutocompleteItem($sender) {

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }
 
    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $price = $item->getLastPartion($this->docform->store->getValue(), "", true);
        $this->editdetail->editprice->setText(H::fa($price));

    }
   
    public function onOpensn($sender) {
        $this->docform->setVisible(false) ;
        $this->editsnitem->setVisible(true) ;
        $this->editsnitem->editsnitemname->setKey(0);
        $this->editsnitem->editsnitemname->setText('');

        $this->editsnitem->editsn->setText("");
        $this->editsnitem->editsnprice->setText("");

    } 
    
    public function savesnOnClick($sender) {
        $common = \App\System::getOptions("common");

        $id = $this->editsnitem->editsnitemname->getKey();
        $name = trim($this->editsnitem->editsnitemname->getText());
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }
        
        $price = doubleVal($this->editsnitem->editsnprice->getText());
        if ($price == 0) {

            $this->setError("Не вказана ціна");
            return;
        }
        $sns =  $this->editsnitem->editsn->getText();

        $list = [];
        foreach(explode("\n", $sns) as $s) {
            $s = trim($s);
            if(strlen($s) > 0) {
                $list[] = $s;
            }
        }
        if (count($list) == 0) {

            $this->setError("Не вказані серійні номери");
            return;
        }
        
        
        if($common['usesnumber'] == 3 ){
            
            $temp_array = array_unique($list);
            if(sizeof($temp_array) < sizeof($list)) {
                $this->setError("Cерійний номер має бути унікальним для виробу");    
                return;
            }           
            
        }        
        
        
        $next = count($this->_itemlist) > 0 ? max(array_keys($this->_itemlist)) : 0;

        foreach($list as $s) {
            ++$next;
            $item = Item::load($id);

            $item->quantity = 1;
            $item->price = $price;
            $item->snumber = trim($s);
            $item->rowid = $next;
            $this->_itemlist[$next] = $item;

        }



        $this->docform->detail->Reload();
        $this->calcTotal();
      

        $this->editsnitem->setVisible(false);
        $this->docform->setVisible(true);


    }
     
    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');
    

        $item = Item::findBarCode($code );

        if ($item == null) {
            $this->setError('Не знайдено ТМЦ  з таким  кодом');
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $this->editdetail->setVisible(true);
            $this->docform->setVisible(false);
            $this->editdetail->edititem->setKey($item->item_id);
            $this->editdetail->edititem->setText($item->itemname);
            $this->editdetail->editsnumber->setText('');
            $this->editdetail->editquantity->setText('1');
            return;
        }
        if (!isset($this->_itemlist[$item->item_id])) {

            $this->_itemlist[$item->item_id] = $item;
            $item->quantity = 0;
        }

        $this->_itemlist[$item->item_id]->quantity += 1;

        $this->docform->detail->Reload();
    }

 
    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->clean();
        $this->editnewitem->editnewbrand->setDataList(Item::getManufacturers());
        $this->editnewitem->editnewitemcode->setText( Item::getNextArticle());
    }

    public function savenewitemOnClick($sender) {
        $itemname = trim($this->editnewitem->editnewitemname->getText());
        if (strlen($itemname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $item = new Item();
        $item->itemname = $itemname;
        $item->item_code = $this->editnewitem->editnewitemcode->getText();
        $item->msr = $this->editnewitem->editnewmsr->getText();

        if ($item->checkUniqueArticle()==false) {
              $this->setError('Такий артикул вже існує');
              return;
        }  

  


        $item->manufacturer = $this->editnewitem->editnewbrand->getText();
        $item->cat_id = $this->editnewitem->editnewcat->getValue();
        $item->save();
        $this->editdetail->edititem->setText($item->itemname);
        $this->editdetail->edititem->setKey($item->item_id);

        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }

    public function cancelnewitemOnClick($sender) {
        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }
}
