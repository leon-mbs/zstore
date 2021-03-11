<?php

namespace App\Pages\Reference;

use App\Entity\Customer;
use App\Helper;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;
use Zippy\Html\Link\SortLink;

/**
 * Страница контрагентов
 */
class CustomerList extends \App\Pages\Base
{

    private $_customer   = null;
    public  $_fileslist  = array();
    public  $_msglist    = array();
    public  $_eventlist  = array();
    public  $_contrtlist = array();
    public  $_leadsourceslist = array();

    public function __construct($id = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CustomerList')) {
            return;
        }

        
        $this->add(new Form('leadf'));
        $this->leadf->add(new CheckBox('chleads'))->onChange($this, 'OnLeadMode');
       
        
        $this->add(new Form('filter'))->onSubmit($this, 'OnSearch');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchtype', array(Customer::TYPE_BAYER => Helper::l("bayers"), Customer::TYPE_SELLER => Helper::l("sellers"), 5 => Helper::l("holdings")), 0));
        $this->filter->add(new DropDownChoice('searchholding', Customer::getHoldList(), 0));
     
        $this->filter->add(new DropDownChoice('searchleadsource', Customer::getLeadSources(), "0"));
        $this->filter->add(new DropDownChoice('searchleadstatus', Customer::getLeadStatuses(), "0"));
    

        $this->add(new Panel('customertable'))->setVisible(true);
        $this->customertable->add(new DataView('customerlist', new CustomerDataSource($this), $this, 'customerlistOnRow'));
        $this->customertable->customerlist->setPageSize(Helper::getPG());
        $this->customertable->add(new \Zippy\Html\DataList\Paginator('pag', $this->customertable->customerlist));
        $this->customertable->customerlist->setSelectedClass('table-success');
        $this->customertable->customerlist->Reload();
        $this->customertable->add(new SortLink("sortdoc", "docs", $this, "onSort"));
        $this->customertable->add(new SortLink("sortname", "customer_name", $this, "onSort"));
        $this->customertable->add(new SortLink("sortleadstatus", "leadstatus", $this, "onSort"));

        $this->customertable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
         
        $this->add(new Form('customerdetail'))->setVisible(false);
        $this->customerdetail->add(new TextInput('editaddress'));
        $this->customerdetail->add(new TextInput('editcity'));
        $this->customerdetail->add(new TextInput('editcustomername'));
        $this->customerdetail->add(new TextInput('editphone'));
        $this->customerdetail->add(new TextInput('editviber'));
        $this->customerdetail->add(new TextInput('editemail'));
        $this->customerdetail->add(new CheckBox('editjurid'));
        $this->customerdetail->add(new CheckBox('editisholding'));
        $this->customerdetail->add(new DropDownChoice('editholding', Customer::getHoldList(), 0));
        $this->customerdetail->add(new DropDownChoice('edittype', array(1 => Helper::l("bayer"), 2 => Helper::l("seller")), 0));
        
        $this->customerdetail->add(new CheckBox('editdisabled'  ));
        
        $this->customerdetail->add(new TextInput('discount'));
        $this->customerdetail->add(new TextInput('bonus'));
        $this->customerdetail->add(new TextArea('editcomment'));
        $this->customerdetail->add(new DropDownChoice('editleadsource',Customer::getLeadSources(),"0"));
        $this->customerdetail->add(new DropDownChoice('editleadstatus',Customer::getLeadStatuses(),"0"));

        $this->customerdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->customerdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel('contentview'))->setVisible(false);
        $this->contentview->add(new ClickLink('back'))->onClick($this, 'cancelOnClick');
        $this->contentview->add(new Label('concname')) ;
        $this->contentview->add(new Label('concreated')) ;
        $this->contentview->add(new Label('conlastdoc')) ;
        $this->contentview->add(new Label('conphone')) ;
        $this->contentview->add(new Label('conemail')) ;
        $this->contentview->add(new ClickLink('convert',$this,'onConvert')) ;
        $this->contentview->add(new Form('conlstform' )) ;
        $this->contentview->conlstform->add(new DropDownChoice('conleadstatus',Customer::getLeadStatuses()  ))->onChange($this,'OnSelStatus') ;
        
    
        $this->contentview->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $this->contentview->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $this->contentview->addfileform->add(new TextInput('adddescfile'));
        $this->contentview->add(new DataView('dw_files', new ArrayDataSource(new Bind($this, '_fileslist')), $this, 'fileListOnRow'));

        $this->contentview->add(new Form('addmsgform'))->onSubmit($this, 'OnMsgSubmit');
        $this->contentview->addmsgform->add(new TextArea('addmsg'));
        $this->contentview->add(new DataView('dw_msglist', new ArrayDataSource(new Bind($this, '_msglist')), $this, 'msgListOnRow'));

        $this->contentview->add(new Form('addeventform'))->onSubmit($this, 'OnEventSubmit');
        $this->contentview->addeventform->add(new \ZCL\BT\DateTimePicker('addeventdate', time()));
        $this->contentview->addeventform->add(new TextInput('addeventtitle'));
        $this->contentview->addeventform->add(new TextArea('addeventdesc'));

        $this->contentview->addeventform->add(new DropDownChoice('addeventnotify', array(1 => "1 час", 2 => "2 часа", 4 => "4 часа", 8 => "8 часов", 16 => "16 часов", 24 => "24 часа"), 0));
        $this->contentview->add(new DataView('dw_eventlist', new ArrayDataSource(new Bind($this, '_eventlist')), $this, 'eventListOnRow'));

        $this->contentview->dw_eventlist->setPageSize(10);
        $this->contentview->add(new \Zippy\Html\DataList\Paginator('eventpag', $this->contentview->dw_eventlist));


        $this->contentview->add(new DataView('dw_contr', new ArrayDataSource(new Bind($this, '_contrlist')), $this, 'contrListOnRow'));

        
        $this->add(new ClickLink('leadsourcesedit',$this,'onLeadSourcesList'));
        $this->add(new ClickLink('leadstatusesedit',$this,'onLeadStatusesList'));
        
        $this->add(new Form('leadsourcesform'))->setVisible(false) ;
        $this->leadsourcesform->add(new SubmitButton('leadsourcesave'))->onClick($this, 'OnSaveLeadSource');
        $this->leadsourcesform->add(new SubmitLink('addnewsource'))->onClick($this, 'OnAddLeadSource');
        $this->leadsourcesform->add(new ClickLink('leadsourcecancel'))->onClick($this, 'OnCancelLeadSource');
        
        $this->leadsourcesform->add(new DataView('leadsourceslist', new ArrayDataSource(new Bind($this, '_leadsourceslist')), $this, 'leadsourceListOnRow'));
        
        
        
        
        if ($id > 0) {
            $this->_customer = Customer::load($id);
            if ($this->_customer instanceof Customer) {
                $this->show();
            }
        }
        
        $this->_tvars['leadmode']= false;
    }

    public function OnLeadMode($sender) {
        
        $this->_tvars['leadmode'] = $sender->isChecked();
        $this->filter->clean();
        $this->customertable->customerlist->Reload();
        
    }
   public function OnSearch($sender) {
        
        $this->customertable->customerlist->Reload();
        $this->contentview->setVisible(false);
    }

    public function customerlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('customername', $item->customer_name));
        $row->add(new Label('customerphone', $item->phone));
        $row->add(new Label('customeremail', $item->email));
        $row->add(new Label('leadstatus', $item->leadstatus));
        $row->add(new Label('docs', $item->docs))->setVisible($item->docs>0);
   
        $row->add(new Label('customercomment'))->setVisible(strlen($item->comment) > 0 && $item->comment == strip_tags($item->comment));
        $row->customercomment->setAttribute('title', $item->comment);
        
        $row->add(new Label('hasmsg'))->setVisible($item->mcnt > 0);
        $row->add(new Label('hasfiles'))->setVisible($item->fcnt > 0);
        $row->add(new Label('isplanned'))->setVisible($item->ecnt > 0);
       
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('contentlist'))->onClick($this, 'editContentOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        
        $row->setAttribute('style', $item->status == 1 ? 'color: #aaa' : null);
        
    }
 
   public function onSort($sender) {
        $sortfield = $sender->fileld;
        $sortdir = $sender->dir;

        $this->customertable->sortdoc->Reset();
 


        $this->customertable->customerlist->setSorting($sortfield, $sortdir);


        $sender->fileld = $sortfield;
        $sender->dir = $sortdir;
        $this->customertable->customerlist->Reload();
    }

    public function editOnClick($sender) {
        $this->_customer = $sender->owner->getDataItem();
        $this->show();
    }

    public function show() {

        $this->customertable->setVisible(false);
        $this->customerdetail->setVisible(true);
        $this->contentview->setVisible(false);
        $this->customerdetail->editholding->setOptionList(Customer::getHoldList());

        $this->customerdetail->editcustomername->setText($this->_customer->customer_name);
        $this->customerdetail->editphone->setText($this->_customer->phone);
        $this->customerdetail->editviber->setText($this->_customer->viber);
        $this->customerdetail->editemail->setText($this->_customer->email);
        $this->customerdetail->editaddress->setText($this->_customer->address);
        $this->customerdetail->editcity->setText($this->_customer->city);
        $this->customerdetail->discount->setText($this->_customer->discount);
        $this->customerdetail->bonus->setText($this->_customer->bonus);
        $this->customerdetail->editcomment->setText($this->_customer->comment);
        $this->customerdetail->edittype->setValue($this->_customer->type);
        $this->customerdetail->editleadsource->setValue($this->_customer->leadsource);
        $this->customerdetail->editleadstatus->setValue($this->_customer->leadstatus);
        $this->customerdetail->editholding->setValue($this->_customer->holding);
        $this->customerdetail->editdisabled->setChecked($this->_customer->status==1);
        $this->customerdetail->editjurid->setChecked($this->_customer->jurid);
        $this->customerdetail->editisholding->setChecked($this->_customer->isholding);

    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('CustomerList')) {
            return;
        }


        $del = Customer::delete($sender->owner->getDataItem()->customer_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }


        $this->customertable->customerlist->Reload();
    }

    public function addOnClick($sender) {
        $this->customertable->setVisible(false);
        $this->customerdetail->setVisible(true);
        // Очищаем  форму
        $this->customerdetail->clean();
  
        $this->contentview->setVisible(false);
        
        $this->_customer = new Customer();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CustomerList')) {
            return;
        }

        $this->_customer->customer_name = $this->customerdetail->editcustomername->getText();
        if ($this->_customer->customer_name == '') {
            $this->setError("entername");
            return;
        }
        $this->_customer->phone = $this->customerdetail->editphone->getText();
        $this->_customer->viber = $this->customerdetail->editviber->getText();
        $this->_customer->email = $this->customerdetail->editemail->getText();
        $this->_customer->address = $this->customerdetail->editaddress->getText();
        $this->_customer->city = $this->customerdetail->editcity->getText();
        $this->_customer->discount = $this->customerdetail->discount->getText();
        $this->_customer->bonus = $this->customerdetail->bonus->getText();
        $this->_customer->comment = $this->customerdetail->editcomment->getText();
        $this->_customer->type = $this->customerdetail->edittype->getValue();
        $this->_customer->holding = $this->customerdetail->editholding->getValue();
        $this->_customer->holding_name = $this->customerdetail->editholding->getValueName();
        
        if($this->_tvars['leadmode']==true) {
           $this->_customer->leadsource = $this->customerdetail->editleadsource->getValue();
           $this->_customer->leadstatus = $this->customerdetail->editleadstatus->getValue();
           $this->_customer->status = 2;  
           if( $this->_customer->leadsource=="0") {
              $this->setError("enterleadsource");
              return;
           }
           if( $this->_customer->leadstatus=="0") {
              $this->setError("enterleadstatus");
              return;
           }
           
           
        }  else {
           $this->_customer->status = $this->customerdetail->editdisabled->isChecked() ?1:0;    
        }
        
        $this->_customer->isholding = $this->customerdetail->editisholding->isChecked() ? 1 : 0;

        if ($this->_customer->isholding == 1 && $this->_customer->holding > 0) {
            $this->setError('nothold');
            return;
        }


        $c = Customer::getByEmail($this->_customer->email);
        if ($c != null) {
            if ($c->customer_id != $this->_customer->customer_id) {
                $this->setError("existcustemail");
                return;
            }
        }
        $this->_customer->phone = \App\Util::handlePhone($this->_customer->phone);

        if (strlen($this->_customer->phone) > 0 && strlen($this->_customer->phone) != Helper::PhoneL()) {
            $this->setError("tel10", Helper::PhoneL());
            return;
        }
        $c = Customer::getByPhone($this->_customer->phone);
        if ($c != null) {
            if ($c->customer_id != $this->_customer->customer_id) {
                $this->setError("existcustphone");
                return;
            }
        }

        if($this->_customer->customer_id==0){ //новый
           $this->_customer->createdon = time(); 
           $this->_customer->user_id = System::getUser()->user_id; 
        }
        $this->_customer->save();
        $this->customerdetail->setVisible(false);
        $this->customertable->setVisible(true);
        $this->customertable->customerlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->customertable->setVisible(true);
        $this->customerdetail->setVisible(false);
        $this->contentview->setVisible(false);
    }

    //просмотр контента
    public function editContentOnClick($sender) {
        $this->_customer = $sender->owner->getDataItem();
        $this->customerdetail->setVisible(false);
        $this->contentview->setVisible(true);
        $this->customertable->setVisible(false);
        $this->contentview->concname->setText($this->_customer->customer_name);
        $created ='';
        $lastdoc ='';
        if($this->_customer->created >0) {
            $user = \App\Entity\User::load($this->_customer->user_id);
            $created = Helper::l('custcreated',Helper::fd($this->_customer->createdon),$user->username) ;
        }
        $doc = \App\Entity\Doc\Document::getFirst("customer_id=".$this->_customer->customer_id, 'document_id desc');
        if($doc  instanceof \App\Entity\Doc\Document) {
              $lastdoc = Helper::l('custlastdoc',$doc->document_number,Helper::fd($doc->document_date),$this->_customer->docs) ;
         
        }
                
        $this->contentview->concreated->setText($created);
        $this->contentview->conlastdoc->setText($lastdoc);
        $this->contentview->conphone->setText($this->_customer->phone);
        $this->contentview->conemail->setText($this->_customer->email);
        $this->contentview->conphone->setVisible(strlen($this->_customer->phone)>0);
        $this->contentview->conemail->setVisible(strlen($this->_customer->email)>0);
        $this->contentview->conlstform->conleadstatus->setValue($this->_customer->leadstatus);
    
        $this->updateFiles();
        $this->updateMessages();
        $this->updateEvents();
        $this->updateContrs();
        $this->customertable->customerlist->setSelectedRow($sender->getOwner());
        $this->customertable->customerlist->Reload();
       // $this->goAnkor('contentviewlink');
    }

    //контент
    public function OnFileSubmit($sender) {

        $file = $this->contentview->addfileform->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("filemore10M");
            return;
        }

        Helper::addFile($file, $this->_customer->customer_id, $this->contentview->addfileform->adddescfile->getText(), \App\Entity\Message::TYPE_CUST);
        $this->contentview->addfileform->adddescfile->setText('');
        $this->updateFiles();
        $this->goAnkor('contentviewlink');
        $this->customertable->customerlist->Reload(false);
    }

    // обновление  списка  прикрепленных файлов
    private function updateFiles() {
        $this->_fileslist = Helper::getFileList($this->_customer->customer_id, \App\Entity\Message::TYPE_CUST);
        $this->contentview->dw_files->Reload();
    }

    //вывод строки  прикрепленного файла
    public function filelistOnRow($row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . 'loadfile.php?id=' . $item->file_id));
        $file->setValue($item->filename);
        $file->setAttribute('title', $item->description);

        $row->add(new ClickLink('delfile'))->onClick($this, 'deleteFileOnClick');
    }

    //удаление прикрепленного файла
    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        Helper::deleteFile($file->file_id);
        $this->updateFiles();
        $this->customertable->customerlist->Reload(false);
    }

    /**
     * добавление коментария
     *
     * @param mixed $sender
     */
    public function OnMsgSubmit($sender) {
        $msg = new \App\Entity\Message();
        $msg->message = $this->contentview->addmsgform->addmsg->getText();
        $msg->created = time();
        $msg->user_id = System::getUser()->user_id;
        $msg->item_id = $this->_customer->customer_id;
        $msg->item_type = \App\Entity\Message::TYPE_CUST;
        if (strlen($msg->message) == 0) {
            return;
        }
        $msg->save();

        $this->contentview->addmsgform->addmsg->setText('');
        $this->updateMessages();
        $this->goAnkor('contentviewlink');
        $this->customertable->customerlist->Reload(false);
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type = 2 and item_id=' . $this->_customer->customer_id, 'message_id');
        $this->contentview->dw_msglist->Reload();
    }

    //вывод строки  коментария
    public function msgListOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label("msgdata", nl2br($item->message)));
        $row->add(new Label("msgdate", \App\Helper::fdt($item->created)));
        $row->add(new Label("msguser", $item->username));

        $row->add(new ClickLink('delmsg'))->onClick($this, 'deleteMsgOnClick');
    }

    //удаление коментария
    public function deleteMsgOnClick($sender) {
        $msg = $sender->owner->getDataItem();
        \App\Entity\Message::delete($msg->message_id);
        $this->updateMessages();
        $this->customertable->customerlist->Reload(false);
    }

    public function OnEventSubmit($sender) {
        $event = new \App\Entity\Event();
        $event->title = $this->contentview->addeventform->addeventtitle->getText();
        $event->description = $this->contentview->addeventform->addeventdesc->getText();
        $event->eventdate = $this->contentview->addeventform->addeventdate->getDate();
        $event->user_id = System::getUser()->user_id;
        $event->customer_id = $this->_customer->customer_id;

        if (strlen($event->title) == 0) {
            return;
        }
        $event->save();

        $nt = $this->contentview->addeventform->addeventnotify->getValue();
        if ($nt > 0) {

            $n = new \App\Entity\Notify();
            $n->user_id = System::getUser()->user_id;
            $n->dateshow = $event->eventdate - ($nt * 3600);
            $n->message = "<b>" . $event->title . "</b>" . "<br>" . $event->description;
            $n->message .= "<br><br><b> Контрагент: </b> {$this->_customer->customer_name} &nbsp;&nbsp; {$this->_customer->phone} ";

            $n->save();
        }
        $this->contentview->addeventform->clean();
        $this->updateEvents();
        $this->goAnkor('contentviewlink');
        $this->customertable->customerlist->Reload(false);
    }

    //список   событий
    private function updateEvents() {
        $this->_eventlist = \App\Entity\Event::find('  customer_id=' . $this->_customer->customer_id);
        $this->contentview->dw_eventlist->Reload();
    }

    private function updateContrs() {
        $this->_contrlist = \App\Entity\Contract::find(' disabled<> 1 and  customer_id=' . $this->_customer->customer_id);
        $this->contentview->dw_contr->Reload();
    }

    //вывод строки  коментария
    public function eventListOnRow($row) {
        $event = $row->getDataItem();


        $row->add(new Label('eventtitle', $event->title));

        $row->add(new Label("eventdesc"))->setText($event->description);
        $row->add(new Label("eventdate", \App\Helper::fdt($event->eventdate)));

        $row->add(new ClickLink('delevent'))->onClick($this, 'deleteEventOnClick');
    }


    //удаление коментария
    public function deleteEventOnClick($sender) {
        $event = $sender->owner->getDataItem();
        \App\Entity\Event::delete($event->event_id);
        $this->updateEvents();
        $this->customertable->customerlist->Reload(false);
    }


    public function contrListOnRow($row) {
        $contr = $row->getDataItem();


        $row->add(new Label('contract_desc', $contr->shortdesc));


        $row->add(new ClickLink('contract'))->onClick($this, 'contractOnClick');
        $row->contract->setValue($contr->contract_number);
    }

    public function contractOnClick($sender) {
        $contr = $sender->owner->getDataItem();

        \App\Application::Redirect("\\App\\Pages\\Reference\\ContractList", $contr->contract_id);
    }

    public function OnSelStatus($sender){
       $this->_customer->leadstatus = $sender->getValue();
       $this->_customer->save();    
    }
    public function onConvert($sender){
        $this->leadf->chleads->setChecked(false);
        $this->_tvars['leadmode'] = false;
        
        $this->filter->clean();
        $this->customertable->customerlist->Reload();
        $this->_customer->status = 0; 
        $this->show() ;
              
    }
    
    //редактирование  источников
    public function onLeadSourcesList($sender){
       $options = System::getOptions('common' ) ;
       $this->_leadsourceslist = $options['leadsources'];
       if(is_array($this->_leadsourceslist)==false)$this->_leadsourceslist = array();
       
       $this->customertable->setVisible(false); 
       $this->leadsourcesform->setVisible(true); 
       $this->leadsourcesform->leadsourceslist->Reload(); 
    }
    public function OnSaveLeadSource($sender){

       $this->customertable->setVisible(true); 
       $this->leadsourcesform->setVisible(false); 
       
       $options = System::getOptions('common' ) ;
       $options['leadsources'] = $this->_leadsourceslist;
       System::setOptions('common',$options ) ; 
       
       $this->filter->searchleadsource->setOptionList(Customer::getLeadSources());
       $this->filter->searchleadstatus->setOptionList(Customer::getLeadStatuses());
       $this->customerdetail->editleadsource->setOptionList(Customer::getLeadSources());
       $this->customerdetail->editleadstatus->setOptionList(Customer::getLeadStatuses());
           
    }
    public function OnAddLeadSource($sender){
         $ls = new \App\DataItem() ;
         $ls->name='';
         $ls->id=time();
         $this->_leadsourceslist[$ls->id]= $ls;
         $this->leadsourcesform->leadsourceslist->Reload(); 
         
         
    }
    
  public function OnCancelLeadSource($sender){
       $this->customertable->setVisible(true); 
       $this->leadsourcesform->setVisible(false); 
 
    }
    
    
    public function leadsourceListOnRow($row){
         $item = $row->getDataItem();
         $row->add(new TextInput('leadsourcename',new  Bind($item,'name')))  ;
         $row->add(new  ClickLink('delsource',$this,'onDelLeadSource')); 
    }
    
    public function onDelLeadSource($sender) {
        $item = $sender->getOwner()->getDataItem() ;
       
        $this->_leadsourceslist = array_diff_key($this->_leadsourceslist, array($item->id => $this->_leadsourceslist[$item->id]));
          
        $this->leadsourcesform->leadsourceslist->Reload(); 
         
    }
    
    
}

class CustomerDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
      

         $conn = \ZDB\DB::getConnect();
        
         
        $type = $this->page->filter->searchtype->getValue();
        $holding = $this->page->filter->searchholding->getValue();
        $search = trim($this->page->filter->searchkey->getText());
        
        $isleads = $this->page->leadf->chleads->isChecked() ;
        if($isleads==false)  {
            $where = "status < 2 " ;

            if (strlen($search) > 0) {
                $search = Customer::qstr('%' . $search . '%');
                $where .= " and (customer_name like  {$search} or phone like {$search} or email like {$search}    )";
            }
            if ($type == 1) {
                $where .= " and detail like '%<type>1</type>%'    ";
            }
            if ($type == 2) {
                $where .= " and detail like '%<type>2</type>%'    ";
            }
            if ($type == 5) {
                $where .= " and detail like '%<isholding>1</isholding>%'    ";
            }
            if ($holding > 0) {
                $where .= " and detail like '%<holding>{$holding}</holding>%'    ";
            }
        } else {
            $searchleadsource = $this->page->filter->searchleadsource->getValue();
            $searchleadstatus = $this->page->filter->searchleadstatus->getValue();
         
            
            $where = "status = 2 " ;
            if (strlen($search) > 0) {
                $search = Customer::qstr('%' . $search . '%');
                $where .= " and (customer_name like  {$search} or phone like {$search} or email like {$search}    )";
            }
            if (strlen($searchleadsource) > 1) {
                $searchleadsource = Customer::qstr( $searchleadsource );
                $where .= " and (leadsource =  {$searchleadsource}    )";
            }
            if (strlen($searchleadstatus) > 1) {
                $searchleadstatus = Customer::qstr( $searchleadstatus );
                $where .= " and (leadstatus =  {$searchleadstatus}    )";
            }
        

        }
      
        return $where;
    }

    public function getItemCount() {
        return Customer::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
       
           return Customer::find($this->getWhere(), $sortfield . " " . $asc, $count, $start,"*, coalesce(  (select  count(*) from  documents where  documents.customer_id= customers_view.customer_id and documents.state>3 ),0)  as docs");
        
     
    }

    public function getItem($id) {

    }

}
