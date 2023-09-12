<?php

namespace App\Modules\Shop\Pages\Admin;

use App\Application as App;
use App\Modules\Shop\Entity\Product;
use App\Modules\Shop\Entity\ProductAttribute;
use App\Entity\Category;
use App\Entity\Item;


use App\Modules\Shop\Helper;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

use Zippy\Html\Panel;

class RecList extends \App\Pages\Base
{
    private $group      = null;
    public $_grouplist = array();
    public $_itemlist   = array();
    public $_reclist   = array();


    private $_item;


    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");
            App::RedirectError();
            return;
        }

        $clist = Category::find(" cat_id in(select cat_id from items where disabled <>1) and detail not  like '%<noshop>1</noshop>%' ");

        $this->_grouplist = Category::findFullData($clist);

        usort($this->_grouplist, function ($a, $b) {
            return $a->full_name > $b->full_name;
        });

        $this->add(new DataView('grouplist', new ArrayDataSource($this, '_grouplist'), $this, 'OnGroupRow'));

        $this->grouplist->Reload();

        $recpanel = $this->add(new Panel('recpanel'));
        $recpanel->add(new Panel('itemlistpanel'));
        $recpanel->itemlistpanel->add(new \Zippy\Html\DataList\DataView('itemlist', new \Zippy\Html\DataList\ArrayDataSource(new Bind($this, '_itemlist')), $this, 'OnItemRow'));

        $recpanel->itemlistpanel->add(new ClickLink('additem'))->onClick($this, 'OnAddItem');

        $form = $recpanel->add(new Form('itemeditform'));
        $form->setVisible(false);
        $form->add(new DropDownChoice("edititem", array(), 0));
        $form->add(new AutocompleteTextInput("editrec"))->onText($this, "OnAutoItem");

        $form->add(new SubmitLink('addrec'))->onClick($this, 'OnAddRec');
        $form->add(new SubmitButton('save'))->onClick($this, 'OnSaveItem');
        $form->add(new ClickLink('cancel'))->onClick($this, 'cancelOnClick');
        $form->add(new DataView('recitemlist', new ArrayDataSource($this, '_reclist'), $this, 'OnRecRow'));


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

        $this->recpanel->itemlistpanel->setVisible(true);


        $this->recpanel->itemeditform->setVisible(false);

        $this->UpdateItemList();
    }


    protected function UpdateItemList() {
        $this->_itemlist =  Item::find("detail like '%<reclist>%' and cat_id=".$this->group->cat_id, 'itemname')  ;

        $this->recpanel->itemlistpanel->itemlist->Reload();
    }

    public function OnItemRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label("itemname", $item->itemname));
        $names=[];
        foreach($item->reclist as $it) {
            $names[]= $it->itemname;
        }


        $recitems = implode("<br>", $names) ;
        $row->add(new Label("recitems", $recitems, true));

        $row->add(new ClickLink("itemedit", $this, 'OnItemEdit'));
        $row->add(new ClickLink("itemdel", $this, 'OnItemDel'));


    }

    public function OnAddItem($sender) {
        $form = $this->recpanel->itemeditform;
        $form->setVisible(true);

        $this->recpanel->itemlistpanel->setVisible(false);
        $where = "disabled <> 1 and detail  not  like '%<noshop>1</noshop>%' and cat_id=" . $this->group->cat_id ;

        $list = [];
        foreach(Item::findYield($where, "itemname") as $it) {
            $name = $it->itemname;
            if(strlen($it->item_code)>0) {
                $name = $name . " ,". $it->item_code;
            }
            $list[$it->item_id] = $name;
        }

        $form->edititem->setOptionList($list);

        $this->_reclist=[];
        $this->recpanel->itemeditform->editrec->setKey(0) ;
        $this->recpanel->itemeditform->editrec->setText("") ;
        $this->recpanel->itemeditform->recitemlist->Reload()  ;
    }

    public function OnItemEdit($sender) {
        $item = $sender->getOwner()->getDataItem();

        $form = $this->recpanel->itemeditform;
        $form->setVisible(true);

        $where = "disabled <> 1 and detail  not  like '%<noshop>1</noshop>%' and cat_id=" . $this->group->cat_id ;

        $list = [];
        foreach(Item::findYield($where, "itemname") as $it) {
            $name = $it->itemname;
            if(strlen($it->item_code) > 0) {
                $name = $name . " ,". $it->item_code;
            }
            $list[$it->item_id] = $name;
        }

        $form->edititem->setOptionList($list);

        $this->recpanel->itemlistpanel->setVisible(false);
        $form->edititem->setValue($item->item_id);
        $this->_reclist =  $item->reclist;
        $this->recpanel->itemeditform->recitemlist->Reload()  ;


    }

    public function OnSaveItem($sender) {
        $form = $this->recpanel->itemeditform;
        $item_id = $form->edititem->getValue();
        if($item_id==0) {
            $this->setError('Не введений  товар') ;
            return;
        }

        $item = Item::load($item_id);
        $item->reclist = $this->_reclist;
        $item->save();

        $this->UpdateItemList();

        $this->recpanel->itemeditform->setVisible(false);
        $this->recpanel->itemlistpanel->setVisible(true);
    }

    public function cancelOnClick($sender) {
        $this->recpanel->itemeditform->setVisible(false);
        $this->recpanel->itemlistpanel->setVisible(true);
    }

    public function OnItemDel($sender) {
        $id = $sender->getOwner()->getDataItem()->item_id;

        $item = Item::load($id) ;
        $item->reclist = [];
        $item->save() ;
        $this->UpdateItemList();
    }

    public function OnAddRec($sender) {
        if(count($this->_reclist)>3) {
            $this->setWarn('Не більше чотирьох ')  ;
            return;
        }
        $id = $this->recpanel->itemeditform->editrec->getKey();


        $name = $this->recpanel->itemeditform->editrec->getText();
        $this->recpanel->itemeditform->editrec->setKey(0) ;
        $this->recpanel->itemeditform->editrec->setText("") ;

        $it= new \App\DataItem() ;
        $it->item_id = $id;
        $it->itemname = $name;

        $this->_reclist[$id] = $it;
        $this->recpanel->itemeditform->recitemlist->Reload()  ;

    }

    public function OnRecRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label("recitem", $item->itemname)) ;
        $row->add(new ClickLink("recitemdel", $this, "onDelRecItem")) ;
    }

    public function onDelRecItem($row) {
        $item = $row->getOwner()->getDataItem();

        $rowid =  array_search($item, $this->_reclist, true);

        $this->_reclist = array_diff_key($this->_reclist, array($rowid => $this->_reclist[$rowid]));

        $this->recpanel->itemeditform->recitemlist->Reload()  ;

    }

    public function OnAutoItem($sender) {

        $text = trim($sender->getText());
        $criteria = "  disabled <> 1 and detail  not  like '%<noshop>1</noshop>%' ";


        if (strlen($text) > 0) {
            $like = Item::qstr('%' . $text . '%');
            $criteria .= "  and  (itemname like {$like} or item_code like {$like}   )";
        } else {
            return [];
        }
        

        $list = array();
        foreach (Item::findYield($criteria) as $key => $value) {

            $list[$key] = $value->itemname;
            if (strlen($value->item_code) > 0) {
                $list[$key] = $value->itemname . ', ' . $value->item_code;
            }

        }
        return  $list;
    }


    public function beforeRender() {
        parent::beforeRender();

        $this->recpanel->setVisible(false);
        if ($this->group instanceof \App\Entity\Category) {

            $this->recpanel->setVisible(true);
        }
    }

}
