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
use Zippy\Html\Form\File;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\System;

class FirmList extends \App\Pages\Base
{

    private $_firm;

    public function __construct() {
        parent::__construct();

        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg(H::l('onlyadminpage'));
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
      
        
        $this->firmdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->firmdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        
        $this->add(new Form('keyform'))->setVisible(false);        
        $this->keyform->add(new SubmitButton('send'))->onClick($this,'onSend',true)  ;
        $this->keyform->add(new Button('cancelppo'))->onClick($this, 'cancelOnClick');
        $this->keyform->add(new Button('delppo'))->onClick($this, 'delOnClick');
        $this->keyform->add(new TextInput('password'));
        $this->keyform->add(new File('keyfile'));
        $this->keyform->add(new File('certfile'));
     
    }

    public function firmlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('firm_name', $item->firm_name));
        $row->add(new Label('ppoowner', $item->ppoowner));

        $row->add(new ClickLink('ppo'))->onClick($this, 'ppoOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
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

        $this->_firm->logo = $this->firmdetail->editlogo->getText();
        $this->_firm->stamp = $this->firmdetail->editstamp->getText();
        $this->_firm->sign = $this->firmdetail->editsign->getText();

        if ($this->_firm->firm_name == '') {
            $this->setError("entername");
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
        $this->keyform->setVisible(false);
        $this->firmtable->firmlist->Reload();
    }
    public function ppoOnClick($sender) {

       $this->_firm = $sender->owner->getDataItem();
      $this->keyform->setVisible(true);
      $this->firmtable->setVisible(false);
      $this->keyform->password->setText('') ;
      $this->keyform->delppo->setVisible(strlen($this->_firm->ppoowner)>0) ;
      
    } 
    public function delOnClick($sender) {
         
         $this->_firm->ppoowner =  ''  ;
         $this->_firm->ppocert = ''  ;
         $this->_firm->ppokey =  ''  ;
         $this->_firm->save();  
        $this->firmtable->setVisible(true);
        $this->firmdetail->setVisible(false);
        $this->keyform->setVisible(false);
        $this->firmtable->firmlist->Reload();         
               
    }
    public function onSend($sender) {
          $die;
       $password = $this->keyform->password->getText() ;
       $keyfile = $this->keyform->keyfile->getFile() ;
       $certfile = $this->keyform->certfile->getFile() ;

       $keydata =  @file_get_contents($keyfile['tmp_name']);
       $certdata =  @file_get_contents($certfile['tmp_name']);
       
       if(strlen($password)==0  || strlen($keydata)==0  || strlen($certdata)==0 )  {
         $this->updateAjax(array(),"   $('#progress').text('". H::l("pponotloaddata") ."');   $('#send').attr('disabled',null);            ") ;
         return;
       }
       
       try{
         $cert =    \PPOLib\Cert::load($certdata) ;
   
         $key =   \PPOLib\KeyStore::load($keydata,$password,$cert ) ;
         

         $this->_firm->ppoowner =  $cert->getOwner()   ;
         $this->_firm->ppocert = base64_encode(serialize($cert) )  ;
         $this->_firm->ppokey =  base64_encode(serialize($key) )  ;
         $this->_firm->save();
         
       } catch(\Exception $ee){
         $msg = $ee->getMessage() ; 
         $this->updateAjax(array(),"   $('#progress').text('{$msg}');   $('#send').attr('disabled',null);            ") ;
         return;
           
       }      
       $kl = \App\Helper::l("ppokeyloaded");
       $this->updateAjax(array(),"   $('#progress').text('{$kl}')") ;
    }
}
