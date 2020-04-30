<?php

namespace App\Pages\Reference;

use App\Entity\Category;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * справочник категорийтоваров
 */
class CategoryList extends \App\Pages\Base
{

    private $_category;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CategoryList')) {
            return;
        }

        $this->add(new Panel('categorytable'))->setVisible(true);
        $this->categorytable->add(new DataView('categorylist', new \ZCL\DB\EntityDataSource('\App\Entity\Category', '', 'cat_name'), $this, 'categorylistOnRow'))->Reload();
        $this->categorytable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('categorydetail'))->setVisible(false);
        $this->categorydetail->add(new TextInput('editcat_name'));


        $this->categorydetail->add(new TextInput('editprice1'));
        $this->categorydetail->add(new TextInput('editprice2'));
        $this->categorydetail->add(new TextInput('editprice3'));
        $this->categorydetail->add(new TextInput('editprice4'));
        $this->categorydetail->add(new TextInput('editprice5'));
        $common = System::getOptions('common');
        if (strlen($common['price1']) > 0) {
            $this->categorydetail->editprice1->setVisible(true);
            $this->categorydetail->editprice1->setAttribute('placeholder', $common['price1']);
        } else {
            $this->categorydetail->editprice1->setVisible(false);
        }
        if (strlen($common['price2']) > 0) {
            $this->categorydetail->editprice2->setVisible(true);
            $this->categorydetail->editprice2->setAttribute('placeholder', $common['price2']);
        } else {
            $this->categorydetail->editprice2->setVisible(false);
        }
        if (strlen($common['price3']) > 0) {
            $this->categorydetail->editprice3->setVisible(true);
            $this->categorydetail->editprice3->setAttribute('placeholder', $common['price3']);
        } else {
            $this->categorydetail->editprice3->setVisible(false);
        }
        if (strlen($common['price4']) > 0) {
            $this->categorydetail->editprice4->setVisible(true);
            $this->categorydetail->editprice4->setAttribute('placeholder', $common['price4']);
        } else {
            $this->categorydetail->editprice4->setVisible(false);
        }
        if (strlen($common['price5']) > 0) {
            $this->categorydetail->editprice5->setVisible(true);
            $this->categorydetail->editprice5->setAttribute('placeholder', $common['price5']);
        } else {
            $this->categorydetail->editprice5->setVisible(false);
        }


        $this->categorydetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->categorydetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function categorylistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('cat_name', $item->cat_name));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CategoryList')) {
            return;
        }


        $cat_id = $sender->owner->getDataItem()->cat_id;

        $del = Category::delete($cat_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }

        $this->categorytable->categorylist->Reload();
    }

    public function editOnClick($sender) {
        $this->_category = $sender->owner->getDataItem();
        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        $this->categorydetail->editcat_name->setText($this->_category->cat_name);

        $this->categorydetail->editprice1->setText($this->_category->price1);
        $this->categorydetail->editprice2->setText($this->_category->price2);
        $this->categorydetail->editprice3->setText($this->_category->price3);
        $this->categorydetail->editprice4->setText($this->_category->price4);
        $this->categorydetail->editprice5->setText($this->_category->price5);
    }

    public function addOnClick($sender) {
        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        // Очищаем  форму
        $this->categorydetail->clean();

        $this->_category = new Category();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CategoryList')) {
            return;
        }

        $this->_category->cat_name = $this->categorydetail->editcat_name->getText();
        if ($this->_category->cat_name == '') {
            $this->setError("entername");
            return;
        }

        $this->_category->price1 = $this->categorydetail->editprice1->getText();
        $this->_category->price2 = $this->categorydetail->editprice2->getText();
        $this->_category->price3 = $this->categorydetail->editprice3->getText();
        $this->_category->price4 = $this->categorydetail->editprice4->getText();
        $this->_category->price5 = $this->categorydetail->editprice5->getText();

        $this->_category->Save();
        $this->categorydetail->setVisible(false);
        $this->categorytable->setVisible(true);
        $this->categorytable->categorylist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->categorytable->setVisible(true);
        $this->categorydetail->setVisible(false);
    }

}
