<?php

namespace App\Pages\Reference;

use App\Entity\Contract;
use App\Entity\Customer;
use App\Entity\Firm;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class ContractList extends \App\Pages\Base
{

    private $_contract;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ContractList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new CheckBox('showdis'));
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
        $this->filter->add(new DropDownChoice('searchcomp',Firm::findArray('firm_name','disabled<>1','firm_name'),0));

        $this->add(new Panel('contracttable'))->setVisible(true);
        $this->contracttable->add(new DataView('contractlist', new ContractDataSource($this), $this, 'contractlistOnRow'))->Reload();
        $this->contracttable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->contracttable->contractlist->setPageSize(H::getPG());
        $this->contracttable->add(new \Zippy\Html\DataList\Paginator('pag', $this->contracttable->contractlist));

        $this->add(new Form('contractdetail'))->setVisible(false);
        $this->contractdetail->add(new Date('editcreatedon',time()));
        $this->contractdetail->add(new TextInput('editshortdesc'));
        $this->contractdetail->add(new TextInput('editcontract_number'));
        $this->contractdetail->add(new AutocompleteTextInput('editcust'))->onText($this, 'OnAutoCustomer');
        $this->contractdetail->add(new DropDownChoice('editcomp',Firm::findArray('firm_name','disabled<>1','firm_name'),0));
        $this->contractdetail->add(new DropDownChoice('editpay',Contract::PayList() ,0));
        $this->contractdetail->add(new \Zippy\Html\Form\File('scan'));
    
        $this->contractdetail->add(new CheckBox('editdisabled'));

        $this->contractdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->contractdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function contractlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('contract_number', $item->contract_number));
        $row->add(new Label('shortdesc', $item->shortdesc));
        $row->add(new Label('createdon', H::fd($item->createdon)));
        $row->add(new Label('customer', $item->customer_name));
        $row->add(new Label('firm', $item->firm_name));
        $row->add(new Label('payname', $item->payname));
       
        $row->add(new \Zippy\Html\Link\BookmarkableLink('scanlink'))->setVisible(false);
        if($item->file_id>0) {
            $row->scanlink->setVisible(true);
            $row->scanlink->setLink(_BASEURL . 'loadfile.php?id=' . $item->file_id);
            
        }
        
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('ContractList')) {
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
        $this->contractdetail->editcontract_number->setText($this->_contract->contract_number);
        $this->contractdetail->editshortdesc->setText($this->_contract->shortdesc);
        $this->contractdetail->editdisabled->setChecked($this->_contract->disabled);
        $this->contractdetail->editcust->setKey($this->_contract->customer_id);
        $this->contractdetail->editcust->setText($this->_contract->customer_name);
        $this->contractdetail->editcomp->setValue($this->_contract->firm_id);
        $this->contractdetail->editpay->setValue($this->_contract->pay);
        
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
        
        $this->_contract->createdon = $this->contractdetail->editcreatedon->getDate();        
        $this->_contract->shortdesc = $this->contractdetail->editshortdesc->getText();        
        $this->_contract->firm_id = $this->contractdetail->editcomp->getValue();
        $this->_contract->pay = $this->contractdetail->editpay->getValue();
        $this->_contract->payname = $this->contractdetail->editpay->getValueName();
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
    }

    public function OnFilter($sender) {
        $this->contracttable->contractlist->Reload();
    }
    
    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and (customer_name like {$text}  or phone like {$text} )");
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
            $where = $where . " and customer_id = ". $cust;
        }

        $comp = $form->searchcomp->getValue();
        if ($comp > 0) {
            $where = $where . " and firm_id = ". $comp;
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
        return Contract::find($this->getWhere(), "createdon desc", $count, $start);
    }

    public function getItem($id) {
        return Contract::load($id);
    }

}
