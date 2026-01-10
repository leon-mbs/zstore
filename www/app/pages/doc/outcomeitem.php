<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Stock;
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
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода списание товаров
 */
class OutcomeItem extends \App\Pages\Base
{
    public $_itemlist = array();
    private $_doc;
    private $_rowid    = -1;

    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));
        $bid = \App\System::getBranch();
        $this->docform->add(new Label('amount'));
        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new DropDownChoice('storeemp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name"))) ;
  
        $tostore = array();
        $conn = \ZDB\DB::getConnect();
        if ($this->_tvars["usebranch"] ) {
            $rs = $conn->Execute("select  s.store_id,s.storename,b.branch_id ,b.branch_name from stores s join branches b on s.branch_id = b.branch_id where b.disabled <>  1 and b.branch_id <> {$bid}  order  by branch_name, storename");
            foreach ($rs as $it) {
                $tostore[$it['store_id']] = $it['branch_name'] . ", " . $it['storename'];
            }
        }

      
        $this->docform->add(new DropDownChoice('tostore', $tostore, 0));
    
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);
  
        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutocompleteItem');
        $this->editdetail->edititem->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editsnumber'))->setText("");

        $this->editdetail->add(new Label('qtystock'));
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            if($this->_doc->state== Document::STATE_NEW) {
                $this->_doc->document_date = time() ;               
            }
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->tostore->setValue($this->_doc->headerdata['tostore']??0);
            $this->docform->storeemp->setValue($this->_doc->headerdata['storeemp']??0);
       
            $this->docform->notes->setText($this->_doc->notes);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('OutcomeItem');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
        $this->total();
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('sum', H::fa($item->sum)));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        
        $this->_doc->amount += $item->sum;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));
        $this->total();
    }

    public function addrowOnClick($sender) {
        if ($this->docform->store->getValue() == 0) {
            $this->setError("Не обрано склад");
            return;
        }
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->editdetail->qtystock->setText('');
        $this->editdetail->editsnumber->setText('');
        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);

        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setValue($item->itemname);
        $this->editdetail->editsnumber->setValue($item->snumber);

        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity($this->docform->store->getValue())));

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

        $item->snumber = trim($this->editdetail->editsnumber->getText());
        $item->quantity = $this->editdetail->editquantity->getDouble();
        $item->sum = H::fa($item->quantity * $item->getPartion());
        
        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("Потрібна партія виробника");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($this->docform->store->getValue());

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
        $this->total();
        $this->wselitem->setVisible(false);
   
        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->editdetail->editquantity->setText("1");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');
        $this->wselitem->setVisible(false);
   
        $this->editdetail->editquantity->setText("1");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }


        $this->_doc->notes = $this->docform->notes->getText();


        $this->_doc->headerdata['tostore'] = $this->docform->tostore->getValue();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storename'] = $this->docform->store->getValueName();
        $this->_doc->headerdata['storeemp'] = $this->docform->storeemp->getValue();
        $this->_doc->headerdata['storeempname'] = $this->docform->storeemp->getValueName();
 
        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());

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

                // проверка на минус  в  количестве
                $allowminus = \App\System::getOption("common", "allowminus");
                if ($allowminus != 1) {

                    foreach ($this->_itemlist as $item) {
                        $qty = $item->getQuantity($this->_doc->headerdata['store']);
                        if ($qty < $item->quantity) {
                            $this->setError("На складі всього ".H::fqty($qty)." ТМЦ {$item->itemname}. Списання у мінус заборонено");
                            return;
                        }
                    }
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);

                $tostore = $this->docform->tostore->getValue();
                if ($sender->id == 'execdoc' && $tostore > 0) {    //создание  прихода
                    $ch = $this->_doc->getChildren('IncomeItem');
                    if (count($ch) > 0) {
                        $this->setWarn('Вже є прибутковий документ ');
                    } else {
                        if ($this->_doc->headerdata['store'] == $tostore) {
                            $this->setWarn('Вибрано той самий склад');
                        }
                        $indoc = Document::create('IncomeItem');

                        $indoc->headerdata['store'] = $tostore;
                        $indoc->headerdata['storename'] = $this->docform->tostore->getValueName();
                        $indoc->branch_id = 0;
                        if ($this->_tvars["usebranch"]) {
                            $st = Store::load($tostore);

                            $indoc->branch_id = $st->branch_id;
                        }
                        $indoc->document_number =  $indoc->nextNumber($indoc->branch_id);

                        $admin  =\App\Entity\User::getByLogin('admin') ;
                        $indoc->user_id = $admin->user_id;

                        $indoc->notes = "На підставі {$this->_doc->document_number}, зі складу " . $this->_doc->headerdata['storename'];
                        if ($this->_doc->branch_id > 0) {
                            $br = \App\Entity\Branch::load($this->_doc->branch_id);
                            $indoc->notes = "На підставі {$this->_doc->document_number}, зі складу {$this->_doc->headerdata['storename']}, філія " . $br->branch_name;
                        }

                        
                        $items = array();

                        foreach ($this->_itemlist as $it) {

                            //последняя партия
                            $stock = \App\Entity\Stock::getFirst("item_id = {$it->item_id} and store_id={$this->_doc->headerdata['store'] }", 'stock_id desc');
                            $it->price = $stock->partion;

                            $items[] = $it;
                        }
                        $indoc->packDetails('detaildata', $items);

                        $indoc->save();
                        $indoc->updateStatus(Document::STATE_NEW);

                        if ($indoc->branch_id == 0) {
                            $indoc->user_id = \App\System::getUser()->user_id;
                            $indoc->save();                            
                            $indoc->updateStatus(Document::STATE_EXECUTED);
                        }
                        if ($indoc->document_id > 0) {
                            $this->setSuccess("Створено документ " . $indoc->document_number);
                        }
                        
                        $this->_doc->notes = "Створено {$indoc->document_number}, на склад " . $indoc->headerdata['storename'];
          
                        $this->_doc->save();
                        
                    }
                }
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
        if (count($this->_itemlist) == 0) {
            $this->setError("Не введено товар");
        }


        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("Не обрано склад");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeItem($sender) {

        $item_id = $sender->getKey();
        $item = Item::load($item_id);
        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity($this->docform->store->getValue(),"",0,$this->docform->storeemp->getValue())));


    }


    public function OnItemType($sender) {
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");
    }

    public function OnAutocompleteItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text, $store_id);
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $code0 = $code;
        $code = ltrim($code, '0');
        $this->docform->barcode->setText('');
        $store_id = $this->docform->store->getValue();
        if ($store_id == 0) {
            $this->setError('Не обрано склад');
            return;
        }

        $code = Item::qstr($code);
        $code0 = Item::qstr($code0);

        $item = Item::getFirst(" item_id in(select item_id from store_stock where store_id={$store_id}) and     (item_code = {$code} or bar_code = {$code} or item_code = {$code0} or bar_code = {$code0} )");
        if ($item == null) {
            $this->setError('Товар не знайдено');
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
        //ищем  последню цену

        $where = "store_id = {$store_id} and item_id = {$item->item_id}    ";

        $s = Stock::getFirst($where, ' stock_id  desc ');
        if ($s instanceof Stock) {
            $item->price = $s->partion;
        }
        $this->total();
    }

    
    private function total(){
        $this->_doc->amount=0;
        $this->docform->detail->Reload();
        $this->docform->amount->setText(H::fa( $this->_doc->amount)) ;
    }
    
  public function onOpenItemSel($sender) {
        $this->wselitem->setVisible(true);
        $this->rowid  = 1;

        $this->wselitem->Reload();
    }

    public function onSelectItem($item_id, $itemname) {
        $this->editdetail->edititem->setKey($item_id);
        $this->editdetail->edititem->setText($itemname);
        $this->OnChangeItem($this->editdetail->edititem);
    }    
}
