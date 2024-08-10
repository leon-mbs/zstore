<?php

namespace App\Pages;

use App\Entity\Firm;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\File;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\System;

class FirmList extends \App\Pages\Base
{
    private $_firm;
    public $_fileslist       = array();

    public function __construct() {
        parent::__construct();

        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('До сторінки має доступ тільки адміністратор');
            \App\Application::RedirectError();
            return false;
        }


        $this->add(new Panel('firmtable'))->setVisible(true);
        $this->firmtable->add(new DataView('firmlist', new \ZCL\DB\EntityDataSource('\App\Entity\Firm', '', 'disabled,firm_name'), $this, 'firmlistOnRow'))->Reload();
        $this->firmtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');


        $this->add(new Form('firmdetail'))->setVisible(false);
        $this->firmdetail->add(new TextInput('editfirm_name'));
        $this->firmdetail->add(new TextInput('editinn'));
        $this->firmdetail->add(new TextInput('edittin'));
        $this->firmdetail->add(new TextInput('editaddress'));
        $this->firmdetail->add(new TextInput('editphone'));

        $this->firmdetail->add(new CheckBox('editdisabled'));
        $this->firmdetail->add(new TextInput('editlogo'));
        $this->firmdetail->add(new TextInput('editstamp'));
        $this->firmdetail->add(new TextInput('editsign'));
        $this->firmdetail->add(new TextInput('editiban'));
        $this->firmdetail->add(new TextInput('editpayname'));
        $this->firmdetail->add(new TextInput('editvdoc'));


        $this->firmdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->firmdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel('fpan'))->setVisible(false);
        $this->fpan->add(new ClickLink('cancel2'))->onClick($this, 'cancelOnClick');
        $this->fpan->add(new Label('ffname')) ;
        $this->fpan->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $this->fpan->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $this->fpan->addfileform->add(new TextInput('adddescfile'));
        $this->fpan->add(new DataView('dw_files', new ArrayDataSource(new Bind($this, '_fileslist')), $this, 'fileListOnRow'));
   
  

    }

    public function firmlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('firm_name', $item->firm_name));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('files'))->onClick($this, 'filesOnClick');
    }

    public function deleteOnClick($sender) {

        $firm_id = $sender->owner->getDataItem()->firm_id;

        $del = Firm::delete($firm_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->firmtable->firmlist->Reload();
    }


    public function editOnClick($sender) {
        $this->_firm = $sender->owner->getDataItem();
        $this->firmtable->setVisible(false);
        $this->firmdetail->setVisible(true);
        $this->firmdetail->editfirm_name->setText($this->_firm->firm_name);
        $this->firmdetail->editinn->setText($this->_firm->inn);
        $this->firmdetail->edittin->setText($this->_firm->tin);
        $this->firmdetail->editaddress->setText($this->_firm->address);
        $this->firmdetail->editphone->setText($this->_firm->phone);

        $this->firmdetail->editlogo->setText($this->_firm->logo);
        $this->firmdetail->editstamp->setText($this->_firm->stamp);
        $this->firmdetail->editsign->setText($this->_firm->sign);
        $this->firmdetail->editiban->setText($this->_firm->iban);
        $this->firmdetail->editpayname->setText($this->_firm->payname);
        $this->firmdetail->editvdoc->setText($this->_firm->vdoc);

        $this->firmdetail->editdisabled->setChecked($this->_firm->disabled);
    }

    public function addOnClick($sender) {
        $this->firmtable->setVisible(false);
        $this->firmdetail->setVisible(true);
        // Очищаем  форму
        $this->firmdetail->clean();

        $this->_firm = new Firm();
    }

    public function saveOnClick($sender) {

        $this->_firm->firm_name = $this->firmdetail->editfirm_name->getText();
        $this->_firm->inn = $this->firmdetail->editinn->getText();
        $this->_firm->tin = $this->firmdetail->edittin->getText();
        $this->_firm->address = $this->firmdetail->editaddress->getText();
        $this->_firm->phone = $this->firmdetail->editphone->getText();
        $this->_firm->iban = $this->firmdetail->editiban->getText();
        $this->_firm->payname = $this->firmdetail->editpayname->getText();
        $this->_firm->vdoc = $this->firmdetail->editvdoc->getText();

        $this->_firm->logo = $this->firmdetail->editlogo->getText();
        $this->_firm->stamp = $this->firmdetail->editstamp->getText();
        $this->_firm->sign = $this->firmdetail->editsign->getText();

        if ($this->_firm->firm_name == '') {
            $this->setError("Не введено назву");
            return;
        }

        $this->_firm->disabled = $this->firmdetail->editdisabled->isChecked() ? 1 : 0;

        $this->_firm->save();
        $this->firmdetail->setVisible(false);
        $this->firmtable->setVisible(true);
        $this->firmtable->firmlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->firmtable->setVisible(true);
        $this->firmdetail->setVisible(false);
        $this->fpan->setVisible(false);

        $this->firmtable->firmlist->Reload();
    }
    
   public function filesOnClick($sender) {
        $this->_firm = $sender->owner->getDataItem();
        $this->fpan->ffname->setText($this->_firm->firm_name);
        $this->fpan->setVisible(true);
        $this->firmtable->setVisible(false);
        $this->updateFiles() ;
        
    }
    private function updateFiles() {
        $this->_fileslist = H::getFileList($this->_firm->firm_id, \App\Entity\Message::TYPE_FIRM);
        $this->fpan->dw_files->Reload();
    }    
 
    public function filelistOnRow( $row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . 'loadfile.php?id=' . $item->file_id));
        $file->setValue($item->filename);
      
        $row->add(new Label('filedesc',$item->description))  ;
        $row->add(new ClickLink('delfile'))->onClick($this, 'deleteFileOnClick');
    }

    
    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        H::deleteFile($file->file_id);
        $this->updateFiles();

    }
    public function OnFileSubmit($sender) {

        $file = $this->fpan->addfileform->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("Файл більше 10 МБ!");
            return;
        }

        H::addFile($file, $this->_firm->firm_id, $this->fpan->addfileform->adddescfile->getText(), \App\Entity\Message::TYPE_FIRM);
        $this->fpan->addfileform->adddescfile->setText('');
        $this->updateFiles();
   
     
    }
    
}
