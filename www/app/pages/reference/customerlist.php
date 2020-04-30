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
use Zippy\Html\Panel;

/**
 * Страница контрагентов
 */
class CustomerList extends \App\Pages\Base
{

    private $_customer = null;
    public $_fileslist = array();
    public $_msglist = array();
    public $_eventlist = array();

    public function __construct($id = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CustomerList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSearch');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchtype', array(Customer::TYPE_BAYER => 'Покупатели', Customer::TYPE_SELLER => 'Поставщики'), 0));
        $this->filter->add(new DropDownChoice('searchstatus', array(Customer::STATUS_ACTUAL => 'Актуальный', Customer::STATUS_DISABLED => 'Не используется', Customer::STATUS_WAIT => 'Потенциальный'), Customer::STATUS_ACTUAL));


        $this->add(new Panel('customertable'))->setVisible(true);
        $this->customertable->add(new DataView('customerlist', new \ZCL\DB\EntityDataSource('\App\Entity\Customer'), $this, 'customerlistOnRow'));
        $this->customertable->customerlist->setPageSize(Helper::getPG());
        $this->customertable->add(new \Zippy\Html\DataList\Paginator('pag', $this->customertable->customerlist));
        $this->customertable->customerlist->setSelectedClass('table-success');
        $this->customertable->customerlist->Reload();

        $this->customertable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('customerdetail'))->setVisible(false);
        $this->customerdetail->add(new TextInput('editaddress'));
        $this->customerdetail->add(new TextInput('editcity'));
        $this->customerdetail->add(new TextInput('editcustomername'));
        $this->customerdetail->add(new TextInput('editphone'));
        $this->customerdetail->add(new TextInput('editemail'));
        $this->customerdetail->add(new CheckBox('editjurid'));
        $this->customerdetail->add(new DropDownChoice('edittype', array(1 => 'Покупатель', 2 => 'Поставщик'), 0));
        $this->customerdetail->add(new DropDownChoice('editstatus', array(Customer::STATUS_ACTUAL => 'Актуальный', Customer::STATUS_DISABLED => 'Не используется', Customer::STATUS_WAIT => 'Потенциальный'), Customer::STATUS_ACTUAL));
        $this->customerdetail->add(new TextInput('discount'));
        $this->customerdetail->add(new TextInput('bonus'));
        $this->customerdetail->add(new TextArea('editcomment'));

        $this->customerdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->customerdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel('contentview'))->setVisible(false);
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

        if ($id > 0) {
            $this->_customer = Customer::load($id);
            if ($this->_customer instanceof Customer) {
                $this->show();
            }
        }
    }

    public function OnSearch($sender) {
        $status = $this->filter->searchstatus->getValue();
        $type = $this->filter->searchtype->getValue();
        $search = trim($this->filter->searchkey->getText());
        $where = "status=" . $status;

        if (strlen($search) > 0) {
            $search = Customer::qstr('%' . $search . '%');
            $where .= " and (customer_name like  {$search} or phone like {$search}    )";
        }
        if ($type > 0) {

            $where .= " and detail like '%<type>{$type}</type>%'    ";
        }


        $this->customertable->customerlist->getDataSource()->setWhere($where);

        $this->customertable->customerlist->Reload();
        $this->contentview->setVisible(false);
    }

    public function customerlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('customername', $item->customer_name));
        $row->add(new Label('customerphone', $item->phone));
        $row->add(new Label('customeremail', $item->email));
        $row->add(new Label('customercomment', $item->comment));
        $row->add(new Label('hasmsg'))->setVisible($item->mcnt > 0);
        $row->add(new Label('hasfiles'))->setVisible($item->fcnt > 0);
        $row->add(new Label('isplanned'))->setVisible($item->ecnt > 0);

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('contentlist'))->onClick($this, 'editContentOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $this->_customer = $sender->owner->getDataItem();
        $this->show();
    }

    public function show() {

        $this->customertable->setVisible(false);
        $this->customerdetail->setVisible(true);
        $this->contentview->setVisible(false);


        $this->customerdetail->editcustomername->setText($this->_customer->customer_name);
        $this->customerdetail->editphone->setText($this->_customer->phone);
        $this->customerdetail->editemail->setText($this->_customer->email);
        $this->customerdetail->editaddress->setText($this->_customer->address);
        $this->customerdetail->editcity->setText($this->_customer->city);
        $this->customerdetail->discount->setText($this->_customer->discount);
        $this->customerdetail->bonus->setText($this->_customer->bonus);
        $this->customerdetail->editcomment->setText($this->_customer->comment);
        $this->customerdetail->edittype->setValue($this->_customer->type);
        $this->customerdetail->editstatus->setValue($this->_customer->status);
        $this->customerdetail->editjurid->setChecked($this->_customer->jurid);
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CustomerList')) {
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
        $this->_customer = new Customer();

        $this->contentview->setVisible(false);
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
        $this->_customer->email = $this->customerdetail->editemail->getText();
        $this->_customer->address = $this->customerdetail->editaddress->getText();
        $this->_customer->city = $this->customerdetail->editcity->getText();
        $this->_customer->discount = $this->customerdetail->discount->getText();
        $this->_customer->bonus = $this->customerdetail->bonus->getText();
        $this->_customer->comment = $this->customerdetail->editcomment->getText();
        $this->_customer->type = $this->customerdetail->edittype->getValue();
        $this->_customer->status = $this->customerdetail->editstatus->getValue();
        $this->_customer->jurid = $this->customerdetail->editjurid->isChecked() ? 1 : 0;


        $c = Customer::getByEmail($this->_customer->email);
        if ($c != null) {
            if ($c->customer_id != $this->_customer->customer_id) {
                $this->setError("existcustemail");
                return;
            }
        }
        if (strlen($this->_customer->phone) > 0 && strlen($this->_customer->phone) != 10) {
            $this->setError("tel10");
            return;
        }
        $c = Customer::getByPhone($this->_customer->phone);
        if ($c != null) {
            if ($c->customer_id != $this->_customer->customer_id) {
                $this->setError("existcustphone");
                return;
            }
        }


        $this->_customer->Save();
        $this->customerdetail->setVisible(false);
        $this->customertable->setVisible(true);
        $this->customertable->customerlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->customertable->setVisible(true);
        $this->customerdetail->setVisible(false);
    }

    //просмотр контента
    public function editContentOnClick($sender) {
        $this->_customer = $sender->owner->getDataItem();
        $this->customerdetail->setVisible(false);
        $this->contentview->setVisible(true);
        $this->updateFiles();
        $this->updateMessages();
        $this->updateEvents();
        $this->customertable->customerlist->setSelectedRow($sender->getOwner());
        $this->customertable->customerlist->Reload();
    }

    //контент
    public function OnFileSubmit($sender) {

        $file = $this->contentview->addfileform->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->getOwnerPage()->setError("filemore10M");
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
        $this->_msglist = \App\Entity\Message::find('item_type = 2 and item_id=' . $this->_customer->customer_id);
        $this->contentview->dw_msglist->Reload();
    }

    //вывод строки  коментария
    public function msgListOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label("msgdata", $item->message));
        $row->add(new Label("msgdate", date("Y-m-d H:i", $item->created)));
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

    //вывод строки  коментария
    public function eventListOnRow($row) {
        $event = $row->getDataItem();


        $row->add(new Label('eventtitle', $event->title));

        $row->add(new Label("eventdesc"))->setText($event->description);
        $row->add(new Label("eventdate", date("Y-m-d H:i", $event->eventdate)));

        $row->add(new ClickLink('delevent'))->onClick($this, 'deleteEventOnClick');
    }

    //удаление коментария
    public function deleteEventOnClick($sender) {
        $event = $sender->owner->getDataItem();
        \App\Entity\Event::delete($event->event_id);
        $this->updateEvents();
        $this->customertable->customerlist->Reload(false);
    }

}
