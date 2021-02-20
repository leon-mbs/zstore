<?php

namespace App\Pages\Reference;

use App\Entity\Contract;
use App\Entity\Customer;
use App\Entity\Employee;
use App\Entity\Firm;
use App\Entity\Pay;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class ContractList extends \App\Pages\Base
{

    private $_contract;

    public function __construct($id = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ContractList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new CheckBox('showdis'));
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
        $this->filter->add(new DropDownChoice('searchcomp', Firm::findArray('firm_name', 'disabled<>1', 'firm_name'), 0));

        $this->add(new Panel('contracttable'))->setVisible(true);
        $this->contracttable->add(new DataView('contractlist', new ContractDataSource($this), $this, 'contractlistOnRow'))->Reload();
        $this->contracttable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->contracttable->contractlist->setPageSize(H::getPG());
        $this->contracttable->add(new \Zippy\Html\DataList\Paginator('pag', $this->contracttable->contractlist));

        $this->add(new Form('contractdetail'))->setVisible(false);
        $this->contractdetail->add(new Date('editcreatedon', time()));
        $this->contractdetail->add(new Date('editenddate', strtotime("+1 month", time())));
        $this->contractdetail->add(new TextInput('editshortdesc'));
        $this->contractdetail->add(new TextArea('editdesc'));
        $this->contractdetail->add(new TextInput('editcontract_number'));
        $this->contractdetail->add(new AutocompleteTextInput('editcust'))->onText($this, 'OnAutoCustomer');
        $this->contractdetail->add(new DropDownChoice('editcomp', Firm::findArray('firm_name', 'disabled<>1', 'firm_name'), 0));
        $this->contractdetail->add(new DropDownChoice('editemp', Employee::findArray('emp_name', 'disabled<>1', 'emp_name'), 0));
        
        $this->contractdetail->add(new \Zippy\Html\Form\File('scan'));

        $this->contractdetail->add(new CheckBox('editdisabled'));

        $this->contractdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->contractdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel('docpan'))->setVisible(false);
        $this->docpan->add(new Label("cname"));
        $this->docpan->add(new Label("totsumma"));
        $this->docpan->add(new Label("totdolg"));

        $this->docpan->add(new ClickLink('back'))->onClick($this, 'cancelOnClick');
        $this->docpan->add(new DataView('dtable', new ArrayDataSource(array()), $this, 'doclistOnRow'));
        $this->docpan->dtable->setPageSize(H::getPG());
        $this->docpan->add(new \Zippy\Html\DataList\Paginator('dpag', $this->docpan->dtable));

        $this->docpan->add(new Form('payform'))->onSubmit($this, 'payOnSubmit');
        $this->docpan->payform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(), H::getDefMF()));
        $this->docpan->payform->add(new TextInput('pamount'));
        $this->docpan->payform->add(new TextInput('pcomment'));
        $this->docpan->payform->add(new Date('pdate', time()));
        $this->docpan->payform->setVisible(false);

        if ($id > 0) {
            $c = Contract::load($id);
            $this->filter->searchkey->setText($c->contract_number);
            $this->OnFilter($this->filter);
        }

    }

    public function contractlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('contract_number', $item->contract_number));
        $row->add(new Label('shortdesc', $item->shortdesc));
        $row->add(new Label('term', H::fd($item->createdon) . ' - ' . H::fd($item->enddate)));
        $row->add(new Label('customer', $item->customer_name));
        $row->add(new Label('firm', $item->firm_name));
  
        $row->add(new Label('emp', $item->emp_name));
        $row->add(new Label('hasnotes'))->setVisible(strlen($item->desc) > 0);
        $row->hasnotes->setAttribute('title', $item->desc);

          
        $row->add(new \Zippy\Html\Link\BookmarkableLink('scanlink'))->setVisible(false);
        if ($item->file_id > 0) {
            $row->scanlink->setVisible(true);
            $row->scanlink->setLink(_BASEURL . 'loadfile.php?id=' . $item->file_id);

        }
   
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('ContractList')) {
            return;
        }

        $contract_id = $sender->owner->getDataItem()->contract_id;

        $del = Contract::delete($contract_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->contracttable->contractlist->Reload();
    }


    public function editOnClick($sender) {
        $this->_contract = $sender->owner->getDataItem();
        $this->contracttable->setVisible(false);
        $this->contractdetail->setVisible(true);
        $this->contractdetail->editcreatedon->setDate($this->_contract->createdon);
        $this->contractdetail->editenddate->setDate($this->_contract->enddate);
        $this->contractdetail->editcontract_number->setText($this->_contract->contract_number);
        $this->contractdetail->editshortdesc->setText($this->_contract->shortdesc);
        $this->contractdetail->editdesc->setText($this->_contract->desc);
        $this->contractdetail->editdisabled->setChecked($this->_contract->disabled);
        $this->contractdetail->editcust->setKey($this->_contract->customer_id);
        $this->contractdetail->editcust->setText($this->_contract->customer_name);
        $this->contractdetail->editcomp->setValue($this->_contract->firm_id);
        $this->contractdetail->editemp->setValue($this->_contract->emp_id);
    
    }

    public function addOnClick($sender) {
        $this->contracttable->setVisible(false);
        $this->contractdetail->setVisible(true);
        // Очищаем  форму
        $this->contractdetail->clean();
        $this->contractdetail->editcreatedon->setDate(time());

        $this->_contract = new Contract();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('ContractList')) {
            return;
        }

        $this->_contract->contract_number = $this->contractdetail->editcontract_number->getText();
        if ($this->_contract->contract_number == '') {
            $this->setError("notnumber");
            return;
        }
        $this->_contract->customer_id = $this->contractdetail->editcust->getKey();
        if ($this->_contract->customer_id == 0) {
            $this->setError("noselcust");
            return;
        }
        $this->_contract->firm_id = $this->contractdetail->editcomp->getValue();
        if ($this->_contract->firm_id == 0) {
            $this->setError("noselfirm");
            return;
        }


        $this->_contract->createdon = $this->contractdetail->editcreatedon->getDate();
        $this->_contract->enddate = $this->contractdetail->editenddate->getDate();
        $this->_contract->shortdesc = $this->contractdetail->editshortdesc->getText();
        $this->_contract->desc = $this->contractdetail->editdesc->getText();

        $this->_contract->emp_id = $this->contractdetail->editemp->getValue();
        $this->_contract->emp_name = $this->contractdetail->editemp->getValueName();
        $this->_contract->disabled = $this->contractdetail->editdisabled->isChecked() ? 1 : 0;

        $this->_contract->save();

        $file = $this->contractdetail->scan->getFile();
        if ($file['size'] > 0) {
            $this->_contract->file_id = H::addFile($file, $this->_contract->contract_id, 'Скан ', \App\Entity\Message::TYPE_CONTRACT);
            $this->_contract->save();
        }


        $this->contractdetail->setVisible(false);
        $this->contracttable->setVisible(true);
        $this->contracttable->contractlist->Reload(false);
    }

    public function cancelOnClick($sender) {
        $this->contracttable->setVisible(true);
        $this->contractdetail->setVisible(false);
        $this->docpan->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->contracttable->contractlist->Reload();
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText());
    }

    public function showOnClick($sender) {
        $this->_contract = $sender->owner->getDataItem();
        $this->contracttable->setVisible(false);
        $this->docpan->setVisible(true);
        $this->docpan->cname->setText($this->_contract->contract_number);


        $dlist = $this->_contract->getDocs();
        $totsumma=0;
        $totdolg=0;
        foreach($dlist as $d){
           $totsumma += $d->payamount;
           $totdolg += ($d->payamount - $d->payed);
        }
        $this->docpan->totsumma->setText(H::fa($totsumma));
        $this->docpan->totdolg->setText(H::fa($totdolg));
     
        $this->docpan->dtable->getDataSource()->setArray($dlist);
        $this->docpan->dtable->Reload();
        $this->docpan->payform->setVisible($totdolg>0);
        $this->docpan->payform->pamount->setText(H::fa($totdolg)) ;
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();
        $row->add(new Label("dnum", $doc->document_number));
        $row->add(new Label("dtype", $doc->meta_desc));
        $row->add(new Label("ddate", H::fd($doc->document_date)));
        $row->add(new Label("dsumma", H::fa($doc->payamount)));
        $row->add(new Label("ddolg", H::fa($doc->payamount - $doc->payed)));
    }

    public function payOnSubmit($sender) {
          
          $amount = $sender->pamount->getText();
          $pdate = $sender->pdate->getDate();
          $comment = $sender->pcomment->getText();
          if(strlen($comment)==0)$comment= H::l('bycontract', $this->_contract->contract_number);
          
             foreach($this->_contract->getDocs()  as $doc){
                
                  
                 if($doc->payamount >0 && $doc->payamount > $doc->payed )  {
                     $p = $doc->payamount - $doc->payed;
                     if($amount  >$p) {
        
                        $amount  -= $p;
                     } else {
                         
                        $p = $amount;   
                        $amount =0;
                     }
                     if(in_array($doc->meta_name,array('GoodsReceipt','InvoiceCust' )) ) {
                          Pay::addPayment($doc->document_id, $pdate, 0-$p, $sender->payment->getValue(),  Pay::PAY_BASE_OUTCOME, $comment);    
                     } else {
                          Pay::addPayment($doc->document_id, $pdate, $p, $sender->payment->getValue(), Pay::PAY_BASE_INCOME, $comment);    
                         
                     }
                      
                     if($amount == 0)  break;          
                 }
                
            }
            
        $this->contracttable->contractlist->Reload(false);  
        $this->contracttable->setVisible(true);
     
        $this->docpan->setVisible(false);     
    }
    

}

class ContractDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $showdis = $form->showdis->isChecked();
        $cust = $form->searchcust->getKey();


        if ($cust > 0) {
            $where = $where . " and customer_id = " . $cust;
        }

        $comp = $form->searchcomp->getValue();
        if ($comp > 0) {
            $where = $where . " and firm_id = " . $comp;
        }

        if ($showdis > 0) {

        } else {
            $where = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            $text = Contract::qstr('%' . $text . '%');
            $where = $where . " and contract_number like {$text}   ";
        }
        return $where;
    }

    public function getItemCount() {
        return Contract::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Contract::find($this->getWhere(), "contract_number", $count, $start);
    }

    public function getItem($id) {
        return Contract::load($id);
    }

}
