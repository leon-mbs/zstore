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
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
 
use App\System;

class Subscribes extends \App\Pages\Base
{

    private $_sub;
    public $_subslist=array();

    public function __construct() {
        parent::__construct();
     
        if (System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('onlyadminsaccess'));
            \App\Application::RedirectError();
            return false;
        }
        $this->add(new  Panel('plist'));
        $this->plist->add(new  ClickLink('addnew',$this,'onAdd'));
        
        $this->plist->add(new DataView('subslist', new ArrayDataSource($this,'_subslist'), $this, 'sublistOnRow'));
         
        $this->add(new  Form('editform'))->setVisible(false);
        $this->editform->add(new CheckBox('editdisabled'));

        $this->editform->add(new DropDownChoice('editeventtype', Subscribe::getEventList(),1));
        $this->editform->add(new DropDownChoice('editdoctype', H::getDocTypes(),0));
        $this->editform->add(new DropDownChoice('editstate', \App\Entity\Doc\Document::getStateList(),0));
        $this->editform->add(new DropDownChoice('editrecievertype', Subscribe::getRecieverList(),1));
           
          
        $this->editform->add(new SubmitButton('save'))->onClick($this, 'OnSave');
        $this->editform->add(new ClickLink('cancel'))->onClick($this, 'OnCancel');
        $this->editform->add(new ClickLink('delete'))->onClick($this, 'OnDelete');

        $this->Reload() ;
    }
    
    
    public function sublistOnRow($row) {
        
        
        
        $row->add(new ClickLink('edit',$this,'OnEdit'));
    }
    
    
    
    
    public function onAdd($sender) {
        $this->plist->setVisible(false);
        $this->editform->setVisible(true);
        $this->editform->clean();
        $this->_sub = new Subscribe();
        
    }
     public function OnEdit($sender) {
        $this->_sub = $sender->getOwner()->getDataItem();   
        
        
    }
    public function OnSave($sender) {
          
        
        $this->_sub->save();
        $this->Reload()  ;
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
        
        Subscribe::delete($this->_sub->sub_id) ;
        $this->Reload()  ;
    }
    
    
    public function Reload() {
       
       $this->_subslist = Subscribe::find('') ;
         
       $this->plist->subslist->Reload() ;
    }
    
}
