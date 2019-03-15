<?php

namespace App\Pages\Reference;

use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Entity\Category;

class CategoryList extends \App\Pages\Base
{

    private $_category;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CategoryList'))
            return;

        $this->add(new Panel('categorytable'))->setVisible(true);
        $this->categorytable->add(new DataView('categorylist', new \ZCL\DB\EntityDataSource('\App\Entity\Category'), $this, 'categorylistOnRow'))->Reload();
        $this->categorytable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('categorydetail'))->setVisible(false);
        $this->categorydetail->add(new TextInput('editcat_name'));
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
        if (false == \App\ACL::checkEditRef('CategoryList'))
            return;


        $cat_id = $sender->owner->getDataItem()->cat_id;
        $cnt = \App\Entity\Item::findCnt("  disabled <> 1  and cat_id=" . $cat_id);
        if ($cnt > 0) {
            $this->setError('Нельзя удалить категорию с товарами');
            return;
        }
        Category::delete($cat_id);
        $this->categorytable->categorylist->Reload();
    }

    public function editOnClick($sender) {
        $this->_category = $sender->owner->getDataItem();
        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        $this->categorydetail->editcat_name->setText($this->_category->cat_name);
    }

    public function addOnClick($sender) {
        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        // Очищаем  форму
        $this->categorydetail->clean();

        $this->_category = new Category();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CategoryList'))
            return;

        $this->_category->cat_name = $this->categorydetail->editcat_name->getText();
        if ($this->_category->cat_name == '') {
            $this->setError("Введите наименование");
            return;
        }

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
