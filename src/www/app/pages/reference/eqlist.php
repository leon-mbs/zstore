<?php

namespace App\Pages\Reference;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \App\Entity\Equipment;
use \App\Entity\Employee;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Bind;
use \App\Helper;
use \App\System;
use \Zippy\Html\Link\BookmarkableLink;

//справочник  оборудования
class EqList extends \App\Pages\Base
{

    private $_item;
     public $_fileslist = array();
    public $_msglist = array();
    public $_eventlist = array();
    
    public function __construct() {
        parent::__construct();
        if(false ==\App\ACL::checkShowRef('EqList'))return;       

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchemp', Employee::findArray("emp_name", "", "emp_name"), 0));

        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new DataView('eqlist', new DS($this), $this, 'eqlistOnRow'));
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->itemtable->eqlist->setPageSize(25);
        $this->itemtable->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->eqlist));
        $this->itemtable->eqlist->setSelectedClass('table-success');
        $this->itemtable->eqlist->Reload();

        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
     

        $this->itemdetail->add(new TextInput('editserial'));
        $this->itemdetail->add(new DropDownChoice('editemp', Employee::findArray("emp_name", "", "emp_name"), 0));
        $this->itemdetail->add(new TextInput('editcode'));
        $this->itemdetail->add(new TextArea('editdescription'));


        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

 
        
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
  
    }

    public function eqlistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('eq_name', $item->eq_name));
        $row->add(new Label('code', $item->code));
        $row->add(new Label('serial', $item->serial));
 
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('contentlist'))->onClick($this, 'editContentOnClick');
        
    }

    public function deleteOnClick($sender) {
       if(false ==\App\ACL::checkEditRef('EqList'))return;       
      
        $item = $sender->owner->getDataItem();
        //проверка на партии
        if ($item->checkDelete()) {
            Equipment::delete($item->eq_id);
        } else {
            $this->setError("Нельзя удалить   ");
            return;
        }



        $this->itemtable->eqlist->Reload();
    }

    public function editOnClick($sender) {
        $this->_item = $sender->owner->getDataItem();
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editname->setText($this->_item->eq_name);
        
        $this->itemdetail->editemp->setValue($this->_item->emp_id);

        $this->itemdetail->editdescription->setText($this->_item->description);
        $this->itemdetail->editcode->setText($this->_item->code);
        $this->itemdetail->editserial->setText($this->_item->serial);
        
        $this->contentview->setVisible(false);        
    }

    public function addOnClick($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        $this->_item = new Equipment();
          $this->contentview->setVisible(false);
    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->itemtable->eqlist->Reload();
    }

    public function OnSubmit($sender) {
       if(false ==\App\ACL::checkEditRef('EqList'))return;       
        
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);

        $this->_item->eq_name = $this->itemdetail->editname->getText();
        $this->_item->emp_id = $this->itemdetail->editemp->getValue();
        $this->_item->emp_name = $this->itemdetail->editemp->getValueName();
      
        $this->_item->code = $this->itemdetail->editcode->getText();

        $this->_item->serial = $this->itemdetail->editserial->getText();
        $this->_item->description = $this->itemdetail->editdescription->getText();

        $this->_item->Save();

        $this->itemtable->eqlist->Reload();
    }
   //просмотр контента
    public function editContentOnClick($sender) {
        $this->_item = $sender->owner->getDataItem();
        $this->itemdetail->setVisible(false);
         $this->contentview->setVisible(true);
        $this->itemtable->eqlist->setSelectedRow($sender->getOwner());
        $this->itemtable->eqlist->Reload();
       $this->updateFiles();
        $this->updateMessages();
        $this->updateEvents();
       
    }    

    //контент
    public function OnFileSubmit($sender) {

        $file = $this->contentview->addfileform->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->getOwnerPage()->setError("Файл больше 10М !");
            return;
        }

        Helper::addFile($file, $this->_item->eq_id, $this->contentview->addfileform->adddescfile->getText(), \App\Entity\Message::TYPE_EQ);
        $this->contentview->addfileform->adddescfile->setText('');
        $this->updateFiles();
        $this->goAnkor('contentviewlink');
    }

    // обновление  списка  прикрепленных файлов
    private function updateFiles() {
        $this->_fileslist = Helper::getFileList($this->_item->eq_id, \App\Entity\Message::TYPE_EQ);
        $this->contentview->dw_files->Reload();
    }

    //вывод строки  прикрепленного файла
    public function filelistOnRow($row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . '?p=App/Pages/LoadFile&arg=' . $item->file_id));
        $file->setValue($item->filename);
        $file->setAttribute('title', $item->description);

        $row->add(new ClickLink('delfile'))->onClick($this, 'deleteFileOnClick');
    }

    //удаление прикрепленного файла
    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        Helper::deleteFile($file->file_id);
        $this->updateFiles();
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
        $msg->item_id =  $this->_item->eq_id;
        $msg->item_type = \App\Entity\Message::TYPE_EQ;
        if (strlen($msg->message) == 0)
            return;
        $msg->save();

        $this->contentview->addmsgform->addmsg->setText('');
        $this->updateMessages();
        $this->goAnkor('contentviewlink');
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type = 3 and item_id=' . $this->_item->eq_id);
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
    }

    public function OnEventSubmit($sender) {
        $event = new \App\Entity\Event();
        $event->title = $this->contentview->addeventform->addeventtitle->getText();
        $event->description = $this->contentview->addeventform->addeventdesc->getText();
        $event->eventdate = $this->contentview->addeventform->addeventdate->getDate();
        $event->user_id = System::getUser()->user_id;
        $event->eq_id = $this->_item->eq_id;

        if (strlen($event->title) == 0)
            return;
        $event->save();

        $nt = $this->contentview->addeventform->addeventnotify->getValue();
        if ($nt > 0) {

            $n = new \App\Entity\Notify();
            $n->user_id = System::getUser()->user_id;
            $n->dateshow = $event->eventdate - ($nt * 3600);
            $n->message = "<b>" . $event->title . "</b>" . "<br>" . $event->description;
            $n->message .= "<br><br><b> Оборудование: </b> {$this->_item->eq_name}   ";
            $n->save();
        }
        $this->contentview->addeventform->clean();
        $this->contentview->addeventform->addeventdate->setDate(time()) ;
        $this->updateEvents();
        $this->goAnkor('contentviewlink');
    }

    //список   событий
    private function updateEvents() {
        $this->_eventlist = \App\Entity\Event::find('  eq_id=' . $this->_item->eq_id);
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
    }
    
    
    
    
}

class DS implements \Zippy\Interfaces\DataSource
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

        if ($emp > 0) {
            $where = $where . " and detail like '%<emp_id>{$emp}</emp_id>%' ";
        }
        if (strlen($text) > 0) {
            $text = Equipment::qstr('%' . $text . '%');
            $where = $where . " and (eq_name like {$text} or detail like {$text} )  ";
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
