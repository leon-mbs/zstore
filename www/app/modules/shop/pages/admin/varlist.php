<?php

namespace App\Modules\Shop\Pages\Admin;

use App\Application as App;
use App\Modules\Shop\Entity\Product;
use App\Modules\Shop\Entity\ProductAttribute;
use App\Modules\Shop\Entity\Variation;
use App\Modules\Shop\Entity\VarItem;

use App\Modules\Shop\Helper;
use App\System;
use App\Entity\Category;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;

class VarList extends \App\Pages\Base
{

    private $group      = null;
    public  $_grouplist = array();
    public  $_varlist   = array();
    public  $_itemlist   = array();
     
    private $_var;
 

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg('noaccesstopage');
            App::RedirectError();
            return;
        }

        $clist = Category::find(" cat_id in(select cat_id from items where disabled <>1) and detail not  like '%<noshop>1</noshop>%' ");

        $this->_grouplist = Category::findFullData($clist);

        usort($this->_grouplist, function($a, $b) {
            return $a->full_name > $b->full_name;
        });

        $this->add(new DataView('grouplist', new ArrayDataSource($this, '_grouplist'), $this, 'OnGroupRow'));

        $this->grouplist->Reload();

        $varpanel = $this->add(new Panel('varpanel'));
        $varpanel->add(new Panel('varlistpanel'));
        $varpanel->varlistpanel->add(new \Zippy\Html\DataList\DataView('varlist', new \Zippy\Html\DataList\ArrayDataSource(new Bind($this, '_varlist')), $this, 'OnVarRow'));

        //$this->UpdateAttrList();

        $varpanel->varlistpanel->add(new ClickLink('addvar'))->onClick($this, 'OnAddVar');
        
        $form = $varpanel->add(new Form('vareditform'));
        $form->setVisible(false);
        $form->onSubmit($this, 'OnSaveVar');
        $form->add(new TextInput('editvarname'));
       
        $form->add(new  DropDownChoice('editattrtype')) ;
        $form->add(new  Button('cancelvar'))->onClick($this,'OnCancel') ;
        
        $varpanel->add(new Panel('itemspanel'))->setVisible(false);
        $varpanel->itemspanel->add(new Form('itemform'))->onSubmit($this,'onAddItem');
        $varpanel->itemspanel->itemform->add(new  DropDownChoice('itemsel'))  ;
        $varpanel->itemspanel->add(new  Label('vartitle'))  ;
        $varpanel->itemspanel->add(new  ClickLink('backvar',$this,'onBackVar'))  ;
        $varpanel->itemspanel->add(new \Zippy\Html\DataList\DataView('varitemslist', new \Zippy\Html\DataList\ArrayDataSource(new Bind($this, '_itemlist')), $this, 'OnItemRow'));
      
        
    }

    public function OnGroupRow($row) {
        $group = $row->getDataItem();
        $row->add(new ClickLink('groupname', $this, 'onGroup'))->setValue($group->full_name);
        if ($group->cat_id == $this->group->cat_id) {
            $row->setAttribute('class', 'table-success');
        }
    }

    public function onGroup($sender) {
        $this->group = $sender->getOwner()->getDataItem();

        $this->grouplist->Reload(false);
        $this->UpdateVarList();
         $this->varpanel->varlistpanel->setVisible(true);
     
        $this->varpanel->itemspanel->setVisible(false); 
     
        $this->varpanel->vareditform->setVisible(false);
    }

    //обновить вариации
    protected function UpdateVarList() {
        $this->_varlist =  Variation::find('cat_id='.$this->group->cat_id,'varname')  ;

        $this->varpanel->vareditform->editattrtype->setOptionList(Helper::getAttrVar($this->group->cat_id));
        $this->varpanel->varlistpanel->varlist->Reload();
    }

    public function OnVarRow(\Zippy\Html\DataList\DataRow $datarow) {
        $var = $datarow->getDataItem();
        
        $datarow->add(new Label("varname", $var->varname));
        
        $datarow->add(new Label("attrname", $var->attributename ));
        $datarow->add(new Label("cnt", $var->cnt ));
        
        $datarow->add(new ClickLink("vardel", $this, 'OnDeleteVar'));
        $datarow->add(new ClickLink("varedit", $this, 'OnEditVar'));
        $datarow->add(new ClickLink("varitems", $this, 'OnItems'));
        
        
    }

    public function OnAddVar($sender) {
        $form = $this->varpanel->vareditform;
        $form->setVisible(true);
        
        $this->varpanel->varlistpanel->setVisible(false);
   
        $form->editvarname->setText("");
        $form->editattrtype->setValue("0");
        
        $this->_var = new  Variation();
    }

  

    public function OnEditVar($sender) {
        $this->_var = $sender->getOwner()->getDataItem();

        $form = $this->varpanel->vareditform;
        $form->setVisible(true);
        $this->varpanel->varlistpanel->setVisible(false);
        $form->editattrtype->setValue($this->_var->attr_id);
        $form->editvarname->setText($this->_var->varname);
   
    }

    public function OnSaveVar($sender) {
        $form = $this->varpanel->vareditform;
        $attrid = $form->editattrtype->getValue();
        if($attrid==0)  return;
        
        if($this->_var->attr_id!=$attrid){
            Variation::delItems($this->_var->var_id);
        }
        $this->_var->attr_id=$attrid;
        $this->_var->varname=$form->editvarname->getText();
        
        $this->_var->save();
        
        $this->UpdateVarList();
        $this->varpanel->varlistpanel->setVisible(true);
 
        $form->setVisible(false);
    }

  
    public function OnCancel($sender) {
       $this->varpanel->varlistpanel->setVisible(true);
       $this->varpanel->vareditform->setVisible(false);
         
    }
    public function OnDeleteVar($sender) {
        $id = $sender->getOwner()->getDataItem()->var_id;
        Variation::delItems($id);
        Variation::delete($id);
        $this->UpdateVarList();
         
    }
    public function OnItems($sender) {
        $this->_var = $sender->getOwner()->getDataItem();
        $this->varpanel->varlistpanel->setVisible(false); 
        $this->varpanel->itemspanel->setVisible(true); 
        $this->varpanel->itemspanel->vartitle->setText($this->_var->varname); 
        
         
        $this->UpdateItemList();
        
         
    }
    public function onAddItem($sender) {
        $item_id = $sender->itemsel->getValue();
        
        if($item_id>0) {
            $vi = new VarItem();
            $vi->item_id=$item_id;
            $vi->var_id=$this->_var->var_id;
            $vi->save();
            
        }
         
        $this->UpdateItemList();       
    }
   
    public function OnItemRow($row) {
      $item = $row->getDataItem();
         
      $row->add(new Label("varitemname", $item->itemname));
      $row->add(new Label("varitemcode", $item->item_code));
      $row->add(new Label("varattrval", $item->attributevalue));
      $row->add(new ClickLink("delitem", $this, 'OnDeleteItem'));
        
         
    }
   
    public function OnDeleteItem($sender) {
        $id = $sender->getOwner()->getDataItem()->varitem_id;
    
        VarItem::delete($id);
        $this->UpdateItemList();
         
    }
  
    public function onBackVar($sender) {
        
        $this->varpanel->varlistpanel->setVisible(true);
        $this->varpanel->itemspanel->setVisible(false); 
        $this->UpdateVarList() ;   
         
    }
    
    //обновить товары
    protected function UpdateItemList() {
        $this->_itemlist =  VarItem::find('var_id='.$this->_var->var_id,'itemname')  ;
        
        $this->varpanel->itemspanel->varitemslist->Reload();
        
        $items = VarItem::getFreeItems($this->_var->attr_id) ;
        $this->varpanel->itemspanel->itemform->itemsel->setOptionList($items); 
        
    }    
    
    public function beforeRender() {
        parent::beforeRender();

        $this->varpanel->setVisible(false);
        if ($this->group instanceof \App\Entity\Category) {

            $this->varpanel->setVisible(true);
        }
    }

}
