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
use Zippy\Html\Form\Date;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;
use Zippy\Html\Link\SortLink;
use Zippy\Html\DataList\DataRow;

/**
 * Страница контрагентов
 */
class CustomerList extends \App\Pages\Base
{
    private $_customer        = null;
    public $_fileslist       = array();
    public $_msglist         = array();
    public $_eventlist       = array();
    public $_contrtlist      = array();
    public $_doclist         = array();
    public $_leadstatuseslist = array();
    public $_leadsourceslist = array();
    public $_bonuses = array(); // бонусы  по  контраоентам

    public $_tag = '' ; 

    public function __construct($id = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CustomerList')) {
            return;
        }
        $this->_bonuses = Customer::getBonusAll()  ;
        $shop = System::getOptions("shop");
        $this->_customer = new Customer();


        $this->add(new Panel('customertable'))->setVisible(true);
        $this->customertable->add(new Form('leadf'));
        $this->customertable->leadf->add(new CheckBox('chleads'))->onChange($this, 'OnLeadMode');

        $this->customertable->add(new Form('filter'))->onSubmit($this, 'OnSearch');
        $this->customertable->filter->add(new TextInput('searchkey'));
        $this->customertable->filter->add(new DropDownChoice('searchtype', array(), 0));
        $this->customertable->filter->add(new DropDownChoice('searchholding', Customer::getHoldList(), 0));

        $this->customertable->filter->add(new DropDownChoice('searchleadsource', Customer::getLeadSources(), "0"));
        $this->customertable->filter->add(new DropDownChoice('searchleadstatus', Customer::getLeadStatuses(), "0"));
     
        $this->customertable->filter->add(new ClickLink('erase', $this, "onErase"));     

        $this->customertable->add(new \Zippy\Html\Link\LinkList("taglist"))->onClick($this, 'OnTagList');        
     
     
        $this->customertable->add(new Form('listform'));
        $this->customertable->listform->add(new DataView('customerlist', new CustomerDataSource($this), $this, 'customerlistOnRow'));
        $this->customertable->listform->customerlist->setPageSize(Helper::getPG());
        $this->customertable->listform->add(new \Zippy\Html\DataList\Pager('pag', $this->customertable->listform->customerlist));

        $this->Reload();
        $this->customertable->listform->add(new SortLink("sortdoc", "docs", $this, "onSort"));
        $this->customertable->listform->add(new SortLink("sortname", "customer_name", $this, "onSort"));
        $this->customertable->listform->add(new SortLink("sortleadstatus", "leadstatus", $this, "onSort"));
        $this->customertable->listform->add(new SortLink("sortleaddate", "createdon", $this, "onSort"));

        $this->customertable->listform->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->customertable->listform->add(new ClickLink('showstat'))->onClick($this, 'showStat');
        $this->customertable->listform->add(new SubmitLink('deleteall'))->onClick($this, 'OnDelAll');

        $this->add(new Panel('statpan'))->setVisible(false);
        $this->statpan->add(new ClickLink('closestat'))->onClick($this, 'closeStat');

        $this->add(new Form('customerdetail'))->setVisible(false);
        $this->customerdetail->add(new TextInput('editaddress'));
        $this->customerdetail->add(new TextInput('editaddressdel'));
        $this->customerdetail->add(new TextInput('editcity'));
        $this->customerdetail->add(new TextInput('editcountry'));
        $this->customerdetail->add(new TextInput('editcustomername'));
        $this->customerdetail->add(new TextInput('editphone'));
        $this->customerdetail->add(new TextInput('editviber'));
        $this->customerdetail->add(new TextInput('edittelega'));
        $this->customerdetail->add(new TextInput('editpassword'));
        $this->customerdetail->add(new TextInput('editconfirm'));
        $this->customerdetail->add(new TextInput('editemail'));
        $this->customerdetail->add(new TextInput('editedrpou'));
        $this->customerdetail->add(new CheckBox('editjurid'));
        $this->customerdetail->add(new CheckBox('editisholding'));
        $this->customerdetail->add(new DropDownChoice('editholding', Customer::getHoldList(), 0));
        $this->customerdetail->add(new DropDownChoice('edittype', array(1 => "Покупець", 2 => "Постачальник"), 0));
        $this->customerdetail->add(new DropDownChoice('editpricetype', \App\Entity\Item::getPriceTypeList(), Helper::getDefPriceType()));

        $this->customerdetail->add(new CheckBox('editallowedshop'))->setVisible($shop["uselogin"] == 1);
        $this->customerdetail->add(new CheckBox('editnosubs'));
        $this->customerdetail->add(new CheckBox('editdisabled'));


        $this->customerdetail->add(new TextArea('editcomment'));
        $this->customerdetail->add(new DropDownChoice('editleadsource', Customer::getLeadSources(), "0"));
        $this->customerdetail->add(new DropDownChoice('editleadstatus', Customer::getLeadStatuses(), "0"));
        $this->customerdetail->add(new \ZCL\BT\Tags("edittags"));

        $this->customerdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->customerdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel('contentview'))->setVisible(false);
        $this->contentview->add(new ClickLink('back'))->onClick($this, 'cancelOnClick');

        $this->contentview->add(new Label('concname'));
        $this->contentview->add(new Label('concreated'));
        $this->contentview->add(new Label('conlastdoc'));
        $this->contentview->add(new Label('conphone'));
        $this->contentview->add(new Label('conemail'));
        $this->contentview->add(new ClickLink('convert', $this, 'onConvert'));
        $this->contentview->add(new Form('conlstform'));
        $this->contentview->conlstform->add(new DropDownChoice('conleadstatus', Customer::getLeadStatuses()))->onChange($this, 'OnSelStatus');

        $this->contentview->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $this->contentview->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $this->contentview->addfileform->add(new TextInput('adddescfile'));
        $this->contentview->add(new DataView('dw_files', new ArrayDataSource(new Bind($this, '_fileslist')), $this, 'fileListOnRow'));

        $this->contentview->add(new Form('addmsgform'))->onSubmit($this, 'OnMsgSubmit');
        $this->contentview->addmsgform->add(new TextArea('addmsg'));
        $this->contentview->add(new DataView('dw_msglist', new ArrayDataSource(new Bind($this, '_msglist')), $this, 'msgListOnRow'));

        $this->contentview->add(new Form('addeventform'))->onSubmit($this, 'OnEventSubmit');
        $this->contentview->addeventform->add(new Date('addeventdate', time()));
        $this->contentview->addeventform->add(new \Zippy\Html\Form\Time('addeventtime', time()));
        $this->contentview->addeventform->add(new TextInput('addeventtitle'));
        $this->contentview->addeventform->add(new TextArea('addeventdesc'));

        $this->contentview->addeventform->add(new DropDownChoice('addeventnotify', array(), 0));
        $this->contentview->add(new DataView('dw_eventlist', new ArrayDataSource(new Bind($this, '_eventlist')), $this, 'eventListOnRow'));

         
        $this->contentview->dw_eventlist->setPageSize(10);
        $this->contentview->add(new \Zippy\Html\DataList\Paginator('eventpag', $this->contentview->dw_eventlist));

        $this->customertable->add(new ClickLink('leadsourcesedit', $this, 'onLeadSourcesList'));
        $this->customertable->add(new ClickLink('leadstatusesedit', $this, 'onLeadStatusesList'));

        $this->add(new Form('leadsourcesform'))->setVisible(false);
        $this->leadsourcesform->add(new SubmitButton('leadsourcesave'))->onClick($this, 'OnSaveLeadSource');
        $this->leadsourcesform->add(new SubmitLink('addnewsource'))->onClick($this, 'OnAddLeadSource');
        $this->leadsourcesform->add(new ClickLink('leadsourcecancel'))->onClick($this, 'OnCancelLeadSource');

        $this->leadsourcesform->add(new DataView('leadsourceslist', new ArrayDataSource(new Bind($this, '_leadsourceslist')), $this, 'leadsourceListOnRow'));

        $this->add(new Form('leadstatusesform'))->setVisible(false);
        $this->leadstatusesform->add(new SubmitButton('leadstatussave'))->onClick($this, 'OnSaveLeadStatus');
        $this->leadstatusesform->add(new SubmitLink('addnewstatus'))->onClick($this, 'OnAddLeadStatus');
        $this->leadstatusesform->add(new ClickLink('leadstatuscancel'))->onClick($this, 'OnCancelLeadStatus');

        $this->leadstatusesform->add(new DataView('leadstatuseslist', new ArrayDataSource(new Bind($this, '_leadstatuseslist')), $this, 'leadstatusListOnRow'));

        $this->contentview->add(new DataView('dw_contr', new ArrayDataSource(new Bind($this, '_contrlist')), $this, 'contrListOnRow'));

        $this->contentview->add(new DataView('dw_doc', new ArrayDataSource(new Bind($this, '_doclist')), $this, 'docListOnRow'));


        if ($id > 0) {
            $this->_customer = Customer::load($id);
            if ($this->_customer instanceof Customer) {
                $this->viewContent();
            }
        }


        $this->_tvars['leadmode'] = false;
    }

    public function Reload() {
          $this->customertable->listform->customerlist->Reload();
          
       
          $this->customertable->taglist->Clear();
          $tags = \App\Entity\Tag::getTags(\App\Entity\Tag::TYPE_CUSTOMER ) ;
          foreach ($tags as $tag) {
             $this->customertable->taglist->addClickLink($tag, '#'.$tag);
          }           
          
          
    }
    
    public function onErase() {
        $this->customertable->filter->clean();
        $this->_tag ='';
        $this->Reload() ;
    }
    
    public function OnLeadMode($sender) {

        $this->_tvars['leadmode'] = $sender->isChecked();
        $this->customertable->filter->clean();
        $this->Reload();

    }

    public function OnSearch($sender) {

        $this->Reload();

        $this->contentview->setVisible(false);
    }

    public function customerlistOnRow($row) {
        $item = $row->getDataItem();


        $row->add(new ClickLink('customername', $this, 'editOnClick'))->setValue($item->customer_name);


        $row->add(new Label('customerphone', $item->phone));
        $row->add(new Label('customeremail', $item->email));
        $row->add(new Label('customeredrpou', $item->edrpou));
        $row->add(new Label('leadstatus', $item->leadstatus));
        $row->add(new Label('createddate', Helper::fd($item->createdon)));
        $row->add(new Label('docs', $item->docs))->setVisible($item->docs > 0);

        $row->add(new Label('customercomment'))->setVisible(strlen($item->comment) > 0 && $item->comment == strip_tags($item->comment));
        $row->customercomment->setAttribute('title', $item->comment);

        $row->add(new Label('hasmsg'))->setVisible($item->mcnt > 0);
        $row->add(new Label('hasfiles'))->setVisible($item->fcnt > 0);
        $row->add(new Label('isplanned'))->setVisible($item->ecnt > 0);

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('contentlist'))->onClick($this, 'editContentOnClick');

        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($item, 'seldel')));

        $row->setAttribute('style', $item->status == 1 ? 'color: #aaa' : null);

        if ($item->customer_id == $this->_customer->customer_id) {
            $row->setAttribute('class', 'table-success');
        }


        $title="";
        if(intval($this->_bonuses[$item->customer_id] ??null) > 0) {
            $title= "Бонуси " . $this->_bonuses[$item->customer_id];
        }
        $d = $item->getDiscount();
        if(doubleval($d) > 0) {
            $title=  "Накопичувальна знижка " . Helper::fa($d) ."%";
        }
        $d = $item->discount;   //постоянная  скидка
        if(doubleval($d) > 0) {
            $title =  "Постійна знижка " . Helper::fa($d). "%";
        }
        $row->add(new Label('hasaction'))->setVisible(strlen($title)>0);

        $row->hasaction->setAttribute('title', $title)  ;

    }

    public function onSort($sender) {
        $sortfield = $sender->fileld;
        $sortdir = $sender->dir;

        $this->customertable->listform->sortdoc->Reset();

        $this->customertable->listform->customerlist->setSorting($sortfield, $sortdir);

        $sender->fileld = $sortfield;
        $sender->dir = $sortdir;
        $this->Reload();

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
        $this->customerdetail->edittelega->setText($this->_customer->telega);
        $this->customerdetail->editemail->setText($this->_customer->email);
        $this->customerdetail->editaddress->setText($this->_customer->address);
        $this->customerdetail->editaddressdel->setText($this->_customer->addressdel);
        $this->customerdetail->editcity->setText($this->_customer->city);
        $this->customerdetail->editedrpou->setText($this->_customer->edrpou);
        $this->customerdetail->editcountry->setText($this->_customer->country);
        $this->customerdetail->editcomment->setText($this->_customer->comment);
        $this->customerdetail->edittype->setValue($this->_customer->type);

        $this->customerdetail->editleadsource->setValue($this->_customer->leadsource);
        $this->customerdetail->editleadstatus->setValue($this->_customer->leadstatus);
        $this->customerdetail->editholding->setValue($this->_customer->holding);
        $this->customerdetail->editpricetype->setValue($this->_customer->pricetype);
        $this->customerdetail->editnosubs->setChecked($this->_customer->nosubs == 1);
        $this->customerdetail->editallowedshop->setChecked($this->_customer->allowedshop == 1);
        $this->customerdetail->editdisabled->setChecked($this->_customer->status == 1);
        $this->customerdetail->editjurid->setChecked($this->_customer->jurid);
        $this->customerdetail->editisholding->setChecked($this->_customer->isholding);
        
        $this->customerdetail->edittags->setTags(\App\Entity\Tag::getTags(\App\Entity\Tag::TYPE_CUSTOMER,(int)$this->_customer->customer_id));
        $this->customerdetail->edittags->setSuggestions(\App\Entity\Tag::getSuggestions(\App\Entity\Tag::TYPE_CUSTOMER));
        
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('CustomerList')) {
            return;
        }
        $id  =$sender->owner->getDataItem()->customer_id;

        $del = Customer::delete($id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }

        $this->Reload();

    }

    public function addOnClick($sender) {
        $this->customertable->setVisible(false);
        $this->customerdetail->setVisible(true);
        // Очищаем  форму
        $this->customerdetail->clean();
        $this->customerdetail->edittags->setTags(array());
        $this->customerdetail->edittags->setSuggestions(\App\Entity\Tag::getSuggestions( \App\Entity\Tag::TYPE_CUSTOMER) );
 
        $this->contentview->setVisible(false);

        $this->_customer = new Customer();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CustomerList')) {
            return;
        }

        $this->_customer->customer_name = trim($this->customerdetail->editcustomername->getText());

        if ($this->_customer->customer_name == '') {
            $this->setError("Не введено назву");
            return;
        }
        $this->_customer->phone = $this->customerdetail->editphone->getText();
        $this->_customer->viber = $this->customerdetail->editviber->getText();
        $this->_customer->telega = $this->customerdetail->edittelega->getText();
        $this->_customer->email = $this->customerdetail->editemail->getText();
        $this->_customer->address = $this->customerdetail->editaddress->getText();
        $this->_customer->addressdel = $this->customerdetail->editaddressdel->getText();
        $this->_customer->city = $this->customerdetail->editcity->getText();
        $this->_customer->edrpou = $this->customerdetail->editedrpou->getText();
        $this->_customer->country = $this->customerdetail->editcountry->getText();
        $this->_customer->comment = $this->customerdetail->editcomment->getText();
        $this->_customer->type = $this->customerdetail->edittype->getValue();
        $this->_customer->holding = $this->customerdetail->editholding->getValue();
        $this->_customer->pricetype = $this->customerdetail->editpricetype->getValue();
        $this->_customer->holding_name = $this->customerdetail->editholding->getValueName();

        if ($this->_tvars['leadmode'] == true) {
            $this->_customer->leadsource = $this->customerdetail->editleadsource->getValue();
            $this->_customer->leadstatus = $this->customerdetail->editleadstatus->getValue();
            $this->_customer->status = 2;
            if ($this->_customer->leadsource == "0") {
                $this->setError("Не вказано джерело ліда");
                return;
            }
            if ($this->_customer->leadstatus == "0") {
                $this->setError("Не вказано стан ліда");
                return;
            }
        } else {

            $this->_customer->status = $this->customerdetail->editdisabled->isChecked() ? 1 : 0;
        }
        $this->_customer->jurid = $this->customerdetail->editjurid->isChecked() ? 1 : 0;
        $this->_customer->nosubs = $this->customerdetail->editnosubs->isChecked() ? 1 : 0;
        $this->_customer->allowedshop = $this->customerdetail->editallowedshop->isChecked() ? 1 : 0;

        $this->_customer->isholding = $this->customerdetail->editisholding->isChecked() ? 1 : 0;

        if ($this->_customer->isholding == 1 && $this->_customer->holding > 0) {
            $this->setError('Холдинг не може бути дочірньою компанією');
            return;
        }


        $c = Customer::getByEmail($this->_customer->email);
        if ($c != null) {
            if ($c->customer_id != $this->_customer->customer_id) {
                $this->setError("Вже існує контрагент з таким e-mail");
                return;
            }
        }
        $this->_customer->phone = \App\Util::handlePhone($this->_customer->phone);

        if (strlen($this->_customer->phone) > 0 && strlen($this->_customer->phone) != Helper::PhoneL()) {
            $this->setError("Довжина номера телефона повинна бути ".\App\Helper::PhoneL()." цифр");
            return;
        }
        $c = Customer::getByPhone($this->_customer->phone);
        if ($c != null) {
            if ($c->customer_id != $this->_customer->customer_id) {
                $this->setError("Вже існує контрагент з таким телефоном");
                return;
            }
        }


        $pass = $this->customerdetail->editpassword->getText();
        $confirm = $this->customerdetail->editconfirm->getText();

        if (strlen($pass)>0) {
            if ($confirm == '') {
                $this->setError('Підтвердіть пароль');
                return;
            } else {
                if ($confirm != $pass) {

                    $this->setError('Невірне підтвердження');
                    return;
                }
            }

            $this->_customer->passw = $pass;

        }

  
        $this->_customer->save();
        
         
        $tags = $this->customerdetail->edittags->getTags() ;
        
        \App\Entity\Tag::updateTags($tags,\App\Entity\Tag::TYPE_CUSTOMER,(int)$this->_customer->customer_id) ;

        
        $this->customerdetail->setVisible(false);
        $this->customertable->setVisible(true);
        $this->Reload();

    }

    public function cancelOnClick($sender) {
        $this->customertable->setVisible(true);
        $this->customerdetail->setVisible(false);
        $this->contentview->setVisible(false);
    }


    //просмотр контента
    public function editContentOnClick($sender) {
        $this->_customer = $sender->getOwner()->getDataItem();

        $this->Reload();


        $this->viewContent();
    }

    public function viewContent() {

        $this->customerdetail->setVisible(false);
        $this->contentview->setVisible(true);
        $this->customertable->setVisible(false);
        $this->contentview->concname->setText($this->_customer->customer_name);
        $created = '';
        $lastdoc = '';
        if ($this->_customer->created > 0) {
            $user = \App\Entity\User::load($this->_customer->user_id);
            $created = "Доданий ".Helper::fd($this->_customer->createdon)." користувачем " .$user->username   ;
        }
        $doc = \App\Entity\Doc\Document::getFirst("customer_id=" . $this->_customer->customer_id, 'document_id desc');
        if ($doc instanceof \App\Entity\Doc\Document) {
            $lastdoc = "Останній документ {$doc->document_number} від ".Helper::fd($doc->document_date).". Всього " .$this->_customer->docs    ;
        }


        $this->contentview->concreated->setText($created);
        $this->contentview->conlastdoc->setText($lastdoc);
        $this->contentview->conphone->setText($this->_customer->phone);
        $this->contentview->conemail->setText($this->_customer->email);
        $this->contentview->conphone->setVisible(strlen($this->_customer->phone) > 0);
        $this->contentview->conemail->setVisible(strlen($this->_customer->email) > 0);
        $this->contentview->conlstform->conleadstatus->setValue($this->_customer->leadstatus);

        $this->updateFiles();
        $this->updateMessages();
        $this->updateEvents();
        $this->updateContrs();
        $this->updateDocs();

        
        $this->_tag='';
         
        // $this->goAnkor('contentviewlink');
    }
 
    public function OnTagList($sender) {
        $this->_tag  = $sender->getSelectedValue();

        $this->contentview->setVisible(false);
        $this->customertable->setVisible(true);
       
        $this->Reload() ;
         
    }
    //контент
    public function OnFileSubmit($sender) {

        $file = $this->contentview->addfileform->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("Файл більше 10 МБ!");
            return;
        }

        Helper::addFile($file, $this->_customer->customer_id, $this->contentview->addfileform->adddescfile->getText(), \App\Entity\Message::TYPE_CUST);
        $this->contentview->addfileform->adddescfile->setText('');
        $this->updateFiles();
        $this->goAnkor('contentviewlink');
        $this->customertable->listform->customerlist->Reload(false);
    }

    // обновление  списка  прикрепленных файлов
    private function updateFiles() {
        $this->_fileslist = Helper::getFileList($this->_customer->customer_id, \App\Entity\Message::TYPE_CUST);
        $this->contentview->dw_files->Reload();
    }

    //вывод строки  прикрепленного файла
    public function filelistOnRow(DataRow $row) {
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
        $this->customertable->listform->customerlist->Reload(false);
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
        $this->customertable->listform->customerlist->Reload(false);
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type = 2 and item_id=' . $this->_customer->customer_id, 'message_id');
        $this->contentview->dw_msglist->Reload();
    }

    //вывод строки  коментария
    public function msgListOnRow(DataRow $row) {
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
        $this->customertable->listform->customerlist->Reload(false);
    }

    public function OnEventSubmit($sender) {
        $event = new \App\Entity\Event();
        $event->title = $this->contentview->addeventform->addeventtitle->getText();
        $event->description = $this->contentview->addeventform->addeventdesc->getText();
        $event->eventdate = $this->contentview->addeventform->addeventdate->getDate();
        $event->eventdate = $this->contentview->addeventform->addeventtime->getDateTime($event->eventdate);
        $event->user_id = System::getUser()->user_id;
        $event->customer_id = $this->_customer->customer_id;

        if (strlen($event->title) == 0) {
            return;
        }
        $event->event_type=\App\Entity\Event::TYPE_CUSTOMER;

        $event->save();

        $nt = $this->contentview->addeventform->addeventnotify->getValue();

        if ($nt > 0) {

            $n = new \App\Entity\Notify();
            $n->user_id = $event->user_id;
            $n->dateshow = $event->eventdate - ($nt * 3600)  ;
            $n->message = "<b>" . $event->title . "</b>" . "<br>" . $event->description;
            $n->message .= "<br><br><b> Контрагент: </b> {$this->_customer->customer_name} &nbsp;&nbsp; {$this->_customer->phone} ";
            $n->sender_id = \App\Entity\Notify::EVENT;
            $n->save();

            if(\App\System::useCron()) {
                $task = new  \App\Entity\CronTask();
                $task->tasktype=\App\Entity\CronTask::TYPE_EVENTCUST;
                $task->starton=$n->dateshow;
                $task->starton= serialize(array(
                   'user_id'=>$event->user_id ,
               
                   'text'=>$n->message

                ));
               
                $task->save();
            }
        }

        $this->contentview->addeventform->clean();
        $this->updateEvents();
        $this->goAnkor('contentviewlink');
        $this->customertable->listform->customerlist->Reload(false);
    }

    //список   событий
    private function updateEvents() {
        $this->_eventlist = \App\Entity\Event::find('  event_type in(0,1) and  customer_id=' . $this->_customer->customer_id);
        $this->contentview->dw_eventlist->Reload();
    }

    private function updateContrs() {
        $this->_contrlist = \App\Entity\Contract::find(' disabled<> 1 and  customer_id=' . $this->_customer->customer_id,'contract_id desc');
        $this->contentview->dw_contr->Reload();
        $this->_tvars['iscontract'] = count($this->_contrlist) > 0; 
    }

    private function updateDocs() {
        $this->_doclist = \App\Entity\Doc\Document::find(' state <> 9 and  customer_id=' . $this->_customer->customer_id,'document_date desc',10);
        $this->contentview->dw_doc->Reload();

    }
 
    //вывод строки  коментария
    public function eventListOnRow(DataRow $row) {
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
        $this->customertable->listform->customerlist->Reload(false);
    }

    public function contrListOnRow(DataRow $row) {
        $contr = $row->getDataItem();

        $row->add(new Label('contract_desc', $contr->shortdesc));

        $row->add(new ClickLink('contract'))->onClick($this, 'contractOnClick');
        $row->contract->setValue($contr->contract_number);
    }

    public function docListOnRow(DataRow $row) {
        $doc = $row->getDataItem();

        $row->add(new Label('doc_amount',  Helper::fa($doc->amount) ));
        $row->add(new Label('doc_state',   \App\Entity\Doc\Document::getStateName($doc->state) ));

        $row->add(new ClickLink('doc'))->onClick($this, 'docOnClick');
        $row->doc->setValue($doc->document_number);
    }

    public function bonusListOnRow(DataRow $row) {
        $b = $row->getDataItem();
        $row->add(new Label('b_date', Helper::fd($b->paydate)));
        $row->add(new Label('b_doc', $b->document_number));
        $row->add(new Label('b_bonus', $b->bonus));

    }

    public function contractOnClick($sender) {
        $contr = $sender->owner->getDataItem();

        \App\Application::Redirect("\\App\\Pages\\Reference\\ContractList", $contr->contract_id);
    }

    public function docOnClick($sender) {
        $doc = $sender->owner->getDataItem();

        \App\Application::Redirect("\\App\\Pages\\Register\\DocList", $doc->document_id);
    }

    public function OnSelStatus($sender) {
        $this->_customer->leadstatus = $sender->getValue();
        $this->_customer->save();
        $this->Reload();

    }

    public function onConvert($sender) {
        $this->customertable->leadf->chleads->setChecked(false);
        $this->_tvars['leadmode'] = false;

        $this->customertable->filter->clean();
        $this->Reload();

        $this->_customer->status = 0;
        $this->_customer->fromlead = 1;

        $this->show();
    }

    //редактирование  источников
    public function onLeadSourcesList($sender) {
        $options = System::getOptions('common');
        $this->_leadsourceslist = $options['leadsources'];
        if (is_array($this->_leadsourceslist) == false) {
            $this->_leadsourceslist = array();
        }

        $this->customertable->setVisible(false);
        $this->leadsourcesform->setVisible(true);
        $this->leadsourcesform->leadsourceslist->Reload();
    }

    public function OnSaveLeadSource($sender) {

        $this->customertable->setVisible(true);
        $this->leadsourcesform->setVisible(false);

        $options = System::getOptions('common');
        $options['leadsources'] = $this->_leadsourceslist;
        System::setOptions('common', $options);

        $this->customertable->filter->searchleadsource->setOptionList(Customer::getLeadSources());
        $this->customerdetail->editleadsource->setOptionList(Customer::getLeadSources());

    }

    public function OnAddLeadSource($sender) {
        $ls = new \App\DataItem();
        $ls->name = '';
        $ls->id = time();
        $this->_leadsourceslist[$ls->id] = $ls;
        $this->leadsourcesform->leadsourceslist->Reload();
    }

    public function OnCancelLeadSource($sender) {
        $this->customertable->setVisible(true);
        $this->leadsourcesform->setVisible(false);
    }

    public function leadsourceListOnRow(DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new TextInput('leadsourcename', new Bind($item, 'name')));
        $row->add(new ClickLink('delsource', $this, 'onDelLeadSource'));
    }

    public function onDelLeadSource($sender) {
        $item = $sender->getOwner()->getDataItem();

        $this->_leadsourceslist = array_diff_key($this->_leadsourceslist, array($item->id => $this->_leadsourceslist[$item->id]));

        $this->leadsourcesform->leadsourceslist->Reload();
    }

    //редактирование  состояний
    public function onLeadStatusesList($sender) {
        $options = System::getOptions('common');
        $this->_leadstatuseslist = $options['leadstatuses'];
        if (is_array($this->_leadstatuseslist) == false) {
            $this->_leadstatuseslist = array();
        }

        $this->customertable->setVisible(false);
        $this->leadstatusesform->setVisible(true);
        $this->leadstatusesform->leadstatuseslist->Reload();
    }

    public function OnSaveLeadStatus($sender) {

        $this->customertable->setVisible(true);
        $this->leadstatusesform->setVisible(false);

        $options = System::getOptions('common');
        $options['leadstatuses'] = $this->_leadstatuseslist;
        System::setOptions('common', $options);

        $this->customertable->filter->searchleadstatus->setOptionList(Customer::getLeadStatuses());
        $this->contentview->conlstform->conleadstatus->setOptionList(Customer::getLeadStatuses());
        $this->customerdetail->editleadstatus->setOptionList(Customer::getLeadStatuses());


    }

    public function OnAddLeadStatus($sender) {
        $ls = new \App\DataItem();
        $ls->name = '';
        $ls->id = time();
        $this->_leadstatuseslist[$ls->id] = $ls;
        $this->leadstatusesform->leadstatuseslist->Reload();
    }

    public function OnCancelLeadStatus($sender) {
        $this->customertable->setVisible(true);
        $this->leadstatusesform->setVisible(false);
    }

    public function leadstatusListOnRow(DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new TextInput('leadstatusname', new Bind($item, 'name')));
        $row->add(new ClickLink('delstatus', $this, 'onDelLeadStatus'));
    }

    public function onDelLeadStatus($sender) {
        $item = $sender->getOwner()->getDataItem();

        $this->_leadstatuseslist = array_diff_key($this->_leadstatuseslist, array($item->id => $this->_leadstatuseslist[$item->id]));

        $this->leadstatusesform->leadstatuseslist->Reload();
    }

    public function showStat($sender) {
        $this->customertable->setVisible(false);
        $this->statpan->setVisible(true);

        $conn = $conn = \ZDB\DB::getConnect();
        $sql = " 
         SELECT   leadstatus,coalesce(count(*),0) as cnt   FROM customers 
             WHERE   
              status =2
              
             GROUP BY  leadstatus   
                         
        ";

        $rs = $conn->Execute($sql);
        $title = array();
        $data = array();
        $color = array();

        $gr1=[];
        $gr1[]=["name","amount"] ;

        foreach ($rs as $row) {

            $gr1[]=[$row['leadstatus'], intval($row['cnt'])];

        }


        $this->_tvars['gr1'] = json_encode($gr1);



        $sql = " 
         SELECT   leadsource,coalesce(count(*),0) as cnt   FROM customers 
             WHERE   
              status =2
              
             GROUP BY  leadsource   
                         
        ";
        $gr2=[];
        $gr2[]=["name","amount"] ;

        $rs = $conn->Execute($sql);


        foreach ($rs as $row) {

            $gr2[]=[$row['leadsource'], intval($row['cnt'])];


        }

        $this->_tvars['gr2'] = json_encode($gr2);

        $sql = " 
         SELECT   leadsource,coalesce(count(*),0) as cnt   FROM customers 
             WHERE   
              status =0  and  detail like '%<fromlead>1</fromlead>%'
              
             GROUP BY  leadsource   
                         
        ";

        $rs = $conn->Execute($sql);

        $gr3=[];
        $gr3[]=["name","amount"] ;

        foreach ($rs as $row) {
            $gr3[]=[$row['leadsource'], intval($row['cnt'])];

        }
        $this->_tvars['gr3'] = json_encode($gr3);

    }

    public function closeStat($sender) {
        $this->customertable->setVisible(true);
        $this->statpan->setVisible(false);
    }

    public function OnDelAll($sender) {
        if (false == \App\ACL::checkDelRef('CustomerList')) {
            return;
        }

        $ids = array();
        foreach ($this->customertable->listform->customerlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                $ids[] = $item->customer_id;
            }
        }
        if (count($ids) == 0) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $d = 0;
        $u = 0;
        foreach ($ids as $id) {
            $sql = "  select count(*)  from  documents where   customer_id = {$id}  ";
            $cnt = $conn->GetOne($sql);
            if ($cnt > 0) {
                $u++;
                $conn->Execute("update customers  set  status=1 where   customer_id={$id}");
            } else {
                $d++;
                $conn->Execute("delete from customers  where   customer_id={$id}");

            }
        }


        $this->setSuccess("Видалено {$d}, деактивовано {$u} ");

        $this->Reload();


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

        $type = $this->page->customertable->filter->searchtype->getValue();
        $holding = $this->page->customertable->filter->searchholding->getValue();
        $search = trim($this->page->customertable->filter->searchkey->getText());
       
        $isleads = $this->page->customertable->leadf->chleads->isChecked();
        if ($isleads == false) {
            $where = "1=1 ";

            if (strlen($search) > 0) {
                $edrpou = Customer::qstr('%<edrpou>' . $search . '</edrpou>%');
                $search = Customer::qstr('%' . $search . '%');
                $where .= " and (customer_name like  {$search} or phone like {$search} or email like {$search}  or detail like {$edrpou}    )";
            }
            if($type < 10) {
                $where .= " and status = 0 ";
                if ($type == 1) {
                    $where .= " and  detail like '%<type>1</type>%'    ";
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
            }
            if ($type == 10) {
                $where .= " and status = 1    ";
            }
          if(strlen($this->page->_tag)>0) {
                
               $tag   = Customer::qstr($this->page->_tag) ;
               $where = "status = 0 and customer_id in (select item_id from taglist where  tag_type=1 and tag_name={$tag} )"; 
            }
            
        } else {
            $searchleadsource = $this->page->customertable->filter->searchleadsource->getValue();
            $searchleadstatus = $this->page->customertable->filter->searchleadstatus->getValue();

            $where = "status = 2 ";
            if (strlen($search) > 0) {
                $search = Customer::qstr('%' . $search . '%');
                $where .= " and (customer_name like  {$search} or phone like {$search} or email like {$search}    )";
            }
            if (strlen($searchleadsource) > 1) {
                $searchleadsource = Customer::qstr($searchleadsource);
                $where .= " and (leadsource =  {$searchleadsource}    )";
            }
            if (strlen($searchleadstatus) > 1) {
                $searchleadstatus = Customer::qstr($searchleadstatus);
                $where .= " and (leadstatus =  {$searchleadstatus}    )";
            }
            
            if(strlen($this->page->_tag)>0) {
                
               $tag   = Customer::qstr($this->page->_tag) ;
               $where = "status = 2 and customer_id in (select item_id from taglist where  tag_type=1 and tag_name={$tag} )"; 
            }
            
            
        }
 
   
        
        return $where;
    }

    public function getItemCount() {
        return Customer::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        return Customer::find($this->getWhere(), $sortfield . " " . $asc, $count, $start, "*, coalesce(  (select  count(*) from  documents where  documents.customer_id= customers_view.customer_id and documents.state>3 ),0)  as docs");
    }

    public function getItem($id) {

    }

}
