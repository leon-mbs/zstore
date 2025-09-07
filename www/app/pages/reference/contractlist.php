<?php

namespace App\Pages\Reference;

use App\Entity\Contract;
use App\Entity\Customer;
use App\Entity\User;
 
use App\Entity\Pay;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataRow;
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
    public $_states=[];
    public $_fileslist=[];
    public $_msglist=[];

    public function __construct($id = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ContractList')) {
            return;
        }

        $this->_states=Contract::getStates() ;
        
        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new CheckBox('showdis'));
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
 

        $this->add(new Panel('contracttable'))->setVisible(true);
        $this->contracttable->add(new DataView('contractlist', new ContractDataSource($this), $this, 'contractlistOnRow'));
        $this->contracttable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->contracttable->contractlist->setPageSize(H::getPG());
        $this->contracttable->add(new \Zippy\Html\DataList\Paginator('pag', $this->contracttable->contractlist));
        $this->contracttable->contractlist->Reload();

        $this->add(new Form('contractdetail'))->setVisible(false);
        $this->contractdetail->add(new Date('editcreatedon', time()));
        $this->contractdetail->add(new Date('editenddate', strtotime("+1 month", time())));
        $this->contractdetail->add(new TextInput('editshortdesc'));
        $this->contractdetail->add(new TextArea('editdesc'));
        $this->contractdetail->add(new TextInput('editcontract_number'));
        $this->contractdetail->add(new AutocompleteTextInput('editcust'))->onText($this, 'OnAutoCustomer');

        $this->contractdetail->add(new DropDownChoice('editemp', User::findArray('username', 'disabled<>1', 'username'), 0));
       
       
        $this->contractdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->contractdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel('docpan'))->setVisible(false);
        $this->docpan->add(new Label("cname"));

        $this->docpan->add(new Label('showdesc'));
        $this->docpan->add(new ClickLink('back'))->onClick($this, 'cancelOnClick');
 
        $statusform=$this->docpan->add(new Form('statusform'));
   
        $statusform->add(new DropDownChoice('mstates',   $this->_states, 0));
        $statusform->add(new DropDownChoice('musers',   User::findArray('username', 'disabled<>1', 'username'), 0));
        $statusform->add(new SubmitButton('bstatus'))->onClick($this, 'bstatusOnClick');
        $statusform->add(new SubmitButton('buser'))->onClick($this, 'buserOnClick');
     
        $this->docpan->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $this->docpan->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $this->docpan->addfileform->add(new TextInput('adddescfile'));
        $this->docpan->add(new DataView('dw_files', new ArrayDataSource(new Bind($this, '_fileslist')), $this, 'fileListOnRow'));

        $this->docpan->add(new Form('addmsgform'))->onSubmit($this, 'OnMsgSubmit');
        $this->docpan->addmsgform->add(new TextArea('addmsg'));
        $this->docpan->add(new DataView('dw_msglist', new ArrayDataSource(new Bind($this, '_msglist')), $this, 'msgListOnRow'));
          
      
        if ($id > 0) {
            $c = Contract::load($id);
            $this->filter->searchkey->setText($c->contract_number);
            $this->OnFilter($this->filter);
        }
    }

    public function contractlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new ClickLink('contract_number',$this, 'showOnClick'))->setValue($item->contract_number);

        $row->add(new Label('shortdesc', $item->shortdesc));
        $row->add(new Label('term', H::fd($item->createdon) . ' - ' . H::fd($item->enddate)));
        if ($item->enddate > 0 && $item->enddate < time()) {
            $row->term->setAttribute('class', 'text-danger');
        }

        $row->add(new Label('customer', $item->customer_name));
      
        $c = Customer::load($item->customer_id);
        $row->add(new Label('state', $this->_states[$item->state ?? 0] ));
    
     

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit',$this, 'editOnClick'))->setVisible($item->state< Contract::STATE_INWORK);
        $row->add(new ClickLink('delete',$this, 'deleteOnClick'))->setVisible($item->state< Contract::STATE_INWORK);;
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
   
        $this->contractdetail->editcust->setKey($this->_contract->customer_id);
        $this->contractdetail->editcust->setText($this->_contract->customer_name);

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
            $this->setError("Не введено номер");
            return;
        }
        $this->_contract->customer_id = $this->contractdetail->editcust->getKey();
        if ($this->_contract->customer_id == 0) {
            $this->setError("Не задано контрагента");
            return;
        }
      
        $user=\App\System::getUser() ;
   
        if($this->_contract->contract_id==0) {
             $this->_contract->creator_id = $user->user_id;
         
        }
        if($this->_contract->state==0) {
           $this->_contract->createdon = $this->contractdetail->editcreatedon->getDate();
        
        }

        $this->_contract->enddate = $this->contractdetail->editenddate->getDate();
        $this->_contract->shortdesc = $this->contractdetail->editshortdesc->getText();
        $this->_contract->desc = $this->contractdetail->editdesc->getText();

        $this->_contract->user_id = $this->contractdetail->editemp->getValue();
        $this->_contract->username = $this->contractdetail->editemp->getValueName();
     
        $this->_contract->save();
      


        $this->contractdetail->setVisible(false);
        $this->contracttable->setVisible(true);
        $this->contracttable->contractlist->Reload(false);
    }

    public function cancelOnClick($sender) {
        $this->contracttable->setVisible(true);
        $this->contractdetail->setVisible(false);
        $this->docpan->setVisible(false);
    }
    public function bstatusOnClick($sender) {
        $this->contracttable->setVisible(true);
        $this->docpan->setVisible(false);
        $oldatate =$this->_contract->state;
        $this->_contract->state = $this->docpan->statusform->mstates->getValue();
        if($this->_contract->state != $oldatate)  {
            $this->_contract->save();      
            $this->contracttable->contractlist->Reload(false);
            
            
            $msg = new \App\Entity\Message();
            $msg->message = " Змiна  статусу  на  ". $this->_states[$this->_contract->state];
            $msg->created = time();
            $msg->user_id = 0;
            $msg->item_id = $this->_contract->contract_id;
            $msg->item_type = \App\Entity\Message::TYPE_CONTRACT;
           
            $msg->save();  
                
        }
         
        
        
    }
    public function buserOnClick($sender) {
        $this->contracttable->setVisible(true);
        $this->docpan->setVisible(false);
        $user_id= $this->docpan->statusform->musers->getValue()  ;
        if($user_id > 0){
            $this->_contract->user_id = $user_id;
            $this->_contract->username = $this->docpan->statusform->musers->getValueName();
            $this->_contract->save();      
            $this->contracttable->contractlist->Reload(false);
        }
        
    }

    public function OnFilter($sender) {
        $this->contracttable->contractlist->Reload();
        $this->docpan->setVisible(false);
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText());
    }

    public function showOnClick($sender) {
        $this->_contract = $sender->owner->getDataItem();
        $this->contracttable->setVisible(false);
        $this->docpan->setVisible(true);
        $this->docpan->cname->setText($this->_contract->contract_number);
        $this->docpan->showdesc->setText($this->_contract->desc);

        $this->docpan->statusform->mstates->setValue($this->_contract->state);
        $this->docpan->statusform->musers->setValue($this->_contract->user_id);

        $dlist = $this->_contract->getDocs();
        $plist = $this->_contract->getPayments();

        $this->_tvars['dtable'] =[]  ;
        $this->_tvars['ptable'] =[]  ;
        foreach($dlist as $doc)  {
          $this->_tvars['dtable'] = array(
            "dnum" => $doc->document_number,
            "dtype" => $doc->meta_desc,
            "ddate" => H::fd($doc->document_date),
            "dsumma" => H::fa($doc->amount) 
          );  
        }
        
      foreach($plist as $doc)  {
          $this->_tvars['ptable'] = array(
            "pmfname" => $doc->mf_name,
            "pnotes" => $doc->notes,
            "pdate" => H::fd($doc->paydate),
            "psumma" => H::fa($doc->amount) 
          );  
        }
     
     
        $this->updateMessages() ; 
        $this->updateFiles() ; 
    }
  
    public function OnFileSubmit($sender) {

        $file = $this->docpan->addfileform->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("Файл більше 10 МБ!");
            return;
        }

        H::addFile($file, $this->_contract->contract_id, $this->docpan->addfileform->adddescfile->getText(), \App\Entity\Message::TYPE_CONTRACT);
        $this->docpan->addfileform->adddescfile->setText('');
        $this->updateFiles();
    }

    // обновление  списка  прикрепленных файлов
    private function updateFiles() {
        $this->_fileslist = H::getFileList($this->_contract->contract_id, \App\Entity\Message::TYPE_CONTRACT);
        $this->docpan->dw_files->Reload();
    }

    //вывод строки  прикрепленного файла
    public function filelistOnRow(DataRow $row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . 'loadfile.php?id=' . $item->file_id));
        $file->setValue($item->filename);
        $file->setAttribute('title', $item->description);
        $user= \App\System::getUser() ;
        $row->add(new ClickLink('delfile',$this, 'deleteFileOnClick'))->setVisible(   $item->user_id == $user->user_id || $user->rolename=='admins'  ); 
    }

    //удаление прикрепленного файла
    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        H::deleteFile($file->file_id);
        $this->updateFiles();
  
    }

    /**
     * добавление коментария
     *
     * @param mixed $sender
     */
    public function OnMsgSubmit($sender) {
        $msg = new \App\Entity\Message();
        $msg->message = $this->docpan->addmsgform->addmsg->getText();
        $msg->created = time();
        $msg->user_id = \App\System::getUser()->user_id;
        $msg->item_id = $this->_contract->contract_id;
        $msg->item_type = \App\Entity\Message::TYPE_CONTRACT;
        if (strlen($msg->message) == 0) {
            return;
        }
        $msg->save();

        $this->docpan->addmsgform->addmsg->setText('');
        $this->updateMessages();
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type = 7 and item_id=' . $this->_contract->contract_id, 'message_id');
        $this->docpan->dw_msglist->Reload();
    }

    //вывод строки  коментария
    public function msgListOnRow(DataRow $row) {
        $item = $row->getDataItem();

        $row->add(new Label("msgdata", nl2br($item->message)));
        $row->add(new Label("msgdate", H::fdt($item->created)));
        $row->add(new Label("msguser", $item->username));
        $user = \App\System::getUser() ;
        $row->add(new ClickLink('delmsg',$this, 'deleteMsgOnClick'))->setVisible( $item->user_id>0 &&( $item->user_id == $user->user_id || $user->rolename=='admins' ));
    }

    //удаление коментария
    public function deleteMsgOnClick($sender) {
        $msg = $sender->owner->getDataItem();
        \App\Entity\Message::delete($msg->message_id);
        $this->updateMessages();
       
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

       

        if ($showdis == 1) {

        } else {
             $where = $where . " and state <>  " . Contract::STATE_CLODED ;
         
        }
        if (strlen($text) > 0) {
            $text = Contract::qstr('%' . $text . '%');
            $where =   " and contract_number like {$text}   ";
        }
        
        $user=\App\System::getUser() ;
        if($user->rolename!='admins') {
           $where = $where .  " and (details like '%<creator_id>{$user->user_id}</creator_id>%' or  details like '%<user_id>{$user->user_id}</user_id>%' ) ";
           
        }
        return $where;
    }

    public function getItemCount() {
        return Contract::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Contract::find($this->getWhere(), "state", $count, $start);
    }

    public function getItem($id) {
        return Contract::load($id);
    }

}
