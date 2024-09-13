<?php

namespace App\Pages;

use App\Entity\Subscribe;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\System;

class Subscribes extends \App\Pages\Base
{
    private $_sub;
    public $_subslist = array();

    public function __construct() {
        parent::__construct();

        if (System::getUser()->rolename != 'admins') {
            System::setErrorMsg("До сторінки має доступ тільки користувачі з роллю admins  ");
            \App\Application::RedirectError();
            return false;
        }
        $this->add(new Panel('plist'));
        $this->plist->add(new ClickLink('addnew', $this, 'onAdd'));

        $this->plist->add(new DataView('subslist', new ArrayDataSource($this, '_subslist'), $this, 'sublistOnRow'));

        $this->add(new Form('editform'))->setVisible(false);
        $this->editform->add(new CheckBox('editdisabled'));
        $this->editform->add(new CheckBox('editattach'));
        $this->editform->add(new CheckBox('edithtml'));
        $this->editform->add(new TextArea('editmsgtext'));
        $this->editform->add(new TextInput('editmsgsubject'));
        $this->editform->add(new TextInput('editurl'));

        $this->editform->add(new DropDownChoice('editeventtype', Subscribe::getEventList(), Subscribe::EVENT_DOCSTATE))->onChange($this, 'update');
        $this->editform->add(new DropDownChoice('editdoctype', H::getDocTypes(), 0));
        $this->editform->add(new DropDownChoice('editstate', \App\Entity\Doc\Document::getStateList(), 0));
        $this->editform->add(new DropDownChoice('editrecievertype', Subscribe::getRecieverList(), Subscribe::RSV_CUSTOMER))->onChange($this, 'update');
        $this->editform->add(new DropDownChoice('editmsgtype', Subscribe::getMsgTypeList(), 0))->onChange($this, 'update');
        $this->editform->add(new DropDownChoice('edituser', \App\Entity\User::findArray('username', 'disabled<>1', 'username'), 0));

        $this->editform->add(new SubmitButton('save'))->onClick($this, 'OnSave');
        $this->editform->add(new ClickLink('cancel'))->onClick($this, 'OnCancel');
        $this->editform->add(new ClickLink('delete'))->onClick($this, 'OnDelete');
   
        $this->Reload();

    }

    public function update($sender) {

    //    $l=Subscribe::getRecieverList($et) ;
     //   $this->editform->editrecievertype->setOptionList($l);

        $et = $this->editform->editeventtype->getValue();
        if($sender->id=='editeventtype') {
   
            if($et == Subscribe::EVENT_DOCSTATE) {
                $this->editform->editdoctype->setVisible(true);
                $this->editform->editstate->setVisible(true);


            }
            if($et == Subscribe::EVENT_NEWCUST) {
                $this->editform->editdoctype->setVisible(false);
                $this->editform->editstate->setVisible(false);


            }
       
            $this->update($this->editform->editrecievertype) ;    
            return;       
        }
        $rt = $this->editform->editrecievertype->getValue();

        if($sender->id=='editrecievertype') {
            $l=Subscribe::getMsgTypeList($rt) ;
       //     $this->editform->editmsgtype->setOptionList($l);
         //   $this->editform->editmsgtype->setValue(array_shift(array_keys($l)));            
             
            $this->editform->edituser->setVisible($rt==Subscribe::RSV_USER);

            $this->editform->editurl->setVisible($rt == Subscribe::RSV_WH);
               
            $this->update($this->editform->editmsgtype) ;    
                   
            return;       
          
        }        
        $mt = $this->editform->editmsgtype->getValue();
        
        if($sender->id=='editmsgtype') {
            $this->editform->editmsgsubject->setVisible(false);
            $this->editform->editattach->setVisible( false);
            $this->editform->edithtml->setVisible(false);
            
             
            if($mt == Subscribe::MSG_EMAIL) {
                $this->editform->editmsgsubject->setVisible(true);
            }            
            return;       
          
        }        

        
        
    }

    public function sublistOnRow($row) {
        $sub = $row->getDataItem();

        $row->add(new Label('sub_typename', $sub->sub_typename));
        $row->add(new Label('msg_typename', $sub->msg_typename));
        $row->add(new Label('reciever_typename', $sub->reciever_typename));
        $desc = array();
        if ($sub->doctype > 0) {
            $desc[] = $sub->doctypename;
        }
        if ($sub->state > 0) {
            $desc[] = $sub->statename;
        }
        if ($sub->user_id > 0) {
            $desc[] = $sub->username;
        }
        $row->add(new Label('desc', implode(', ', $desc)));

        $row->add(new ClickLink('edit', $this, 'OnEdit'));
        $row->setAttribute('style', $sub->disabled == 1 ? 'color: #aaa' : null);
    }

    public function onAdd($sender) {
        $this->plist->setVisible(false);
        $this->editform->setVisible(true);
        $this->editform->delete->setVisible(false);
        $this->editform->clean();
        $this->_sub = new Subscribe();
        $this->editform->editeventtype->setValue(Subscribe::EVENT_DOCSTATE);
        $this->editform->editrecievertype->setValue(Subscribe::EVENT_DOCSTATE);
        $this->update($this->editform->editeventtype);
        
        $this->update( $this->editform->editeventtype) ;        
    }

    public function OnEdit($sender) {
        $this->_sub = $sender->getOwner()->getDataItem();
        $this->editform->delete->setVisible(true);

        $this->editform->editeventtype->setValue($this->_sub->sub_type);
        $this->editform->editrecievertype->setValue($this->_sub->reciever_type);
        $this->editform->editmsgtype->setValue($this->_sub->msg_type);
        $this->editform->edituser->setValue($this->_sub->user_id);
        $this->editform->editdoctype->setValue($this->_sub->doctype);
        $this->editform->editstate->setValue($this->_sub->state);

        $this->editform->editmsgtext->setText($this->_sub->msgtext);
        $this->editform->editmsgsubject->setText($this->_sub->msgsubject);
        $this->editform->editurl->setText($this->_sub->url);
        $this->editform->editdisabled->setCheCked($this->_sub->disabled);
        $this->editform->editattach->setCheCked($this->_sub->attach);
        $this->editform->edithtml->setCheCked($this->_sub->html);

        $this->update($this->editform->editeventtype);
        $this->plist->setVisible(false);
        $this->editform->setVisible(true);
        
       
    }

    public function OnSave($sender) {


        $this->_sub->sub_type = $this->editform->editeventtype->getValue();
        $this->_sub->sub_typename = $this->editform->editeventtype->getValueName();
        $this->_sub->reciever_type = $this->editform->editrecievertype->getValue();
        $this->_sub->reciever_typename = $this->editform->editrecievertype->getValueName();
        $this->_sub->msg_type = $this->editform->editmsgtype->getValue();
        $this->_sub->msg_typename = $this->editform->editmsgtype->getValueName();
        $this->_sub->user_id = $this->editform->edituser->getValue();
        $this->_sub->username = $this->editform->edituser->getValueName();
        $this->_sub->doctype = $this->editform->editdoctype->getValue();
        $this->_sub->doctypename = $this->editform->editdoctype->getValueName();
        $this->_sub->state = $this->editform->editstate->getValue();
        $this->_sub->statename = $this->editform->editstate->getValueName();

        $this->_sub->msgtext = trim($this->editform->editmsgtext->getText());
        $this->_sub->msgsubject = trim($this->editform->editmsgsubject->getText());
        $this->_sub->url = trim($this->editform->editurl->getText());
        $this->_sub->disabled = $this->editform->editdisabled->isCheCked() ? 1 : 0;
        $this->_sub->html = $this->editform->edithtml->isCheCked() ? 1 : 0;

        if ($this->_sub->reciever_type == Subscribe::RSV_USER && $this->_sub->user_id == 0) {
            $this->setError("Не вказано користувача");
            return;
        }

        if ($this->_sub->reciever_type != Subscribe::RSV_WH ) {
            if (strlen($this->_sub->msgtext) == 0) {
                $this->setError("Не вказано текст повідомлення");
                return;
            }
            if ($this->_sub->msg_type == 0) {
                $this->setError("Не вказано тип повідомлення");
                return;
            }
        }  else {
            $this->_sub->msg_type =0;           
            $this->_sub->msg_typename ='';           
        }
        
        if ($this->_sub->reciever_type == Subscribe::RSV_WH && strlen($this->_sub->url) == 0) {
            $this->setError("Не вказано URL");
            return;
        }
        
        
        $this->_sub->save();
        $this->Reload();
        $this->plist->setVisible(true);
        $this->editform->setVisible(false);
    }

    public function OnCancel($sender) {
        $this->plist->setVisible(true);
        $this->editform->setVisible(false);

        //        $this->Reload()  ;
    }

    public function OnDelete($sender) {
        $this->plist->setVisible(true);
        $this->editform->setVisible(false);

        Subscribe::delete($this->_sub->sub_id);
        $this->Reload();
    }

    public function Reload() {

        $this->_subslist = Subscribe::find('');

        $this->plist->subslist->Reload();
    }

}
