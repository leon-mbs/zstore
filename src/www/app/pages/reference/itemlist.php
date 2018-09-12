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
use \App\Entity\Item;
use \App\Entity\Category;

class ItemList extends \App\Pages\Base
{

    private $_item;

    public function __construct($add = false) {
        parent::__construct();
         if(false ==\App\ACL::checkShowRef('ItemList'))return;       

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchcat', Category::findArray("cat_name", "", "cat_name"), 0));

        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new DataView('itemlist', new ItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->itemtable->itemlist->setPageSize(25);
        $this->itemtable->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->itemlist));


        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
        $this->itemdetail->add(new TextInput('editprice'));

        $this->itemdetail->add(new TextInput('editbarcode'));
        $this->itemdetail->add(new DropDownChoice('editcat', Category::findArray("cat_name", "", "cat_name"), 0));
        $this->itemdetail->add(new TextInput('editcode'));
        $this->itemdetail->add(new TextArea('editdescription'));


        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        if ($add == false) {
            $this->itemtable->itemlist->Reload();
        } else {
            $this->addOnClick(null);
        }
    }

    public function itemlistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('cat_name', $item->cat_name));
        $row->add(new Label('price', $item->price));
        $row->add(new Label('qty', $item->qty));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
       if(false ==\App\ACL::checkEditRef('ItemList'))return;       
     
        $item = $sender->owner->getDataItem();
        //проверка на партии
        if ($item->checkDelete()) {
            Item::delete($item->item_id);
        } else {
            $this->setError("Нельзя удалить  товар");
            return;
        }



        $this->itemtable->itemlist->Reload();
    }

    public function editOnClick($sender) {
        $this->_item = $sender->owner->getDataItem();
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editname->setText($this->_item->itemname);
        $this->itemdetail->editprice->setText($this->_item->price);
        $this->itemdetail->editcat->setValue($this->_item->cat_id);

        $this->itemdetail->editdescription->setText($this->_item->description);
        $this->itemdetail->editcode->setText($this->_item->item_code);
        $this->itemdetail->editbarcode->setText($this->_item->bar_code);
    }

    public function addOnClick($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        $this->_item = new Item();
    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->itemtable->itemlist->Reload();
    }

    public function OnSubmit($sender) {
       if(false ==\App\ACL::checkEditRef('ItemList'))return;       
     
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);

        $this->_item->itemname = $this->itemdetail->editname->getText();
        $this->_item->cat_id = $this->itemdetail->editcat->getValue();
        $this->_item->price = $this->itemdetail->editprice->getText();

        $this->_item->item_code = $this->itemdetail->editcode->getText();

        $this->_item->bar_code = $this->itemdetail->editbarcode->getText();
        $this->_item->description = $this->itemdetail->editdescription->getText();

        $this->_item->Save();

        $this->itemtable->itemlist->Reload();
    }

}

class ItemDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $cat = $form->searchcat->getValue();

        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }
        if (strlen($text) > 0) {
            $text = Item::qstr('%' . $text . '%');
            $where = $where . " and (itemname like {$text} or item_code like {$text} )  ";
        }
        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Item::find($this->getWhere(), "itemname asc", $count, $start);
    }

    public function getItem($id) {
        return Item::load($id);
    }

}
