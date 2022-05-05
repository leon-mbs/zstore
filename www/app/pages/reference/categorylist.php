<?php

namespace App\Pages\Reference;

use App\Entity\Category;
use App\System;
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

/**
 * справочник категорийтоваров
 */
class CategoryList extends \App\Pages\Base
{

    private $_category;
    public  $_catlist = array();

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CategoryList')) {
            return;
        }

        $this->add(new Panel('categorytable'))->setVisible(true);
        $this->categorytable->add(new DataView('categorylist', new ArrayDataSource($this, '_catlist'), $this, 'categorylistOnRow'));
        $this->categorytable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('categorydetail'))->setVisible(false);
        $this->categorydetail->add(new TextInput('editcat_name'));
        $this->categorydetail->add(new DropDownChoice('editparent', 0));

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
        $this->categorydetail->add(new \Zippy\Html\Image('editimage', '/loadimage.php?id=0'));
        $this->categorydetail->add(new \Zippy\Html\Form\File('editaddfile'));
        $this->categorydetail->add(new CheckBox('editdelimage'));
        $this->categorydetail->add(new CheckBox('editnoshop'));
        $this->categorydetail->add(new CheckBox('editnofastfood'));

        $this->categorydetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->categorydetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->Reload();
    }

    public function Reload() {
        $this->_catlist = Category::find('', 'cat_name', -1, -1, "item_cat.*,    coalesce((  select     count(*)   from     items i   where     (i.cat_id = item_cat.cat_id)),0) AS qty");
        foreach (Category::findFullData() as $c) {
            $this->_catlist[$c->cat_id]->full_name = $c->full_name;
            $this->_catlist[$c->cat_id]->parents = $c->parents;
        }


        $this->categorytable->categorylist->Reload();
    }

    public function updateParentList($id = 0) {
        $plist = array();
        foreach ($this->_catlist as $c) {
            if ($c->cat_id == $id) {
                continue;
            }
            if ($c->qty > 0) {
                continue;
            }
            if (in_array($id, $c->parents)) {
                continue;
            }
            $plist[$c->cat_id] = $c->full_name;
        }

        $this->categorydetail->editparent->setOptionList($plist);
    }

    public function categorylistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();

        $row->add(new Label('cat_name', $item->cat_name));
        $row->add(new Label('p_name', $this->_catlist[$item->parent_id]->full_name));
        $row->add(new Label('qty', $item->qty))->setVisible($item->qty > 0);
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('data-gallery', $item->image_id);
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('CategoryList')) {
            return;
        }

        $cat_id = $sender->owner->getDataItem()->cat_id;
        if ($this->_catlist[$cat_id]->qty > 0) {
            $this->setError('nodelcat');
            return;
        }
        if ($this->_catlist[$cat_id]->hasChild()) {
            $this->setError('nodelcatchild');
            return;
        }


        Category::delete($cat_id);

        $this->Reload();
    }

    public function editOnClick($sender) {
        $this->_category = $sender->owner->getDataItem();
        $this->updateParentList($this->_category->cat_id);

        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        $this->categorydetail->editcat_name->setText($this->_category->cat_name);
        $this->categorydetail->editparent->setValue($this->_category->parent_id);
        $this->categorydetail->editnoshop->setChecked($this->_category->noshop);
        $this->categorydetail->editnofastfood->setChecked($this->_category->nofastfood);

        $this->categorydetail->editprice1->setText($this->_category->price1);
        $this->categorydetail->editprice2->setText($this->_category->price2);
        $this->categorydetail->editprice3->setText($this->_category->price3);
        $this->categorydetail->editprice4->setText($this->_category->price4);
        $this->categorydetail->editprice5->setText($this->_category->price5);

        if ($this->_category->image_id > 0) {
            $this->categorydetail->editdelimage->setChecked(false);
            $this->categorydetail->editdelimage->setVisible(true);
            $this->categorydetail->editimage->setVisible(true);
            $this->categorydetail->editimage->setUrl('/loadimage.php?id=' . $this->_category->image_id);
        } else {
            $this->categorydetail->editdelimage->setVisible(false);
            $this->categorydetail->editimage->setVisible(false);
        }
    }

    public function addOnClick($sender) {
        $this->updateParentList($this->_category->cat_id);
        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        // Очищаем  форму
        $this->categorydetail->clean();
        $this->categorydetail->editimage->setVisible(false);
        $this->categorydetail->editdelimage->setVisible(false);
        $this->updateParentList();
        $this->_category = new Category();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CategoryList')) {
            return;
        }

        $this->_category->parent_id = $this->categorydetail->editparent->getValue();
        $this->_category->cat_name = $this->categorydetail->editcat_name->getText();
        $this->_category->noshop = $this->categorydetail->editnoshop->isChecked() ? 1 : 0;
        $this->_category->nofastfood = $this->categorydetail->editnofastfood->isChecked() ? 1 : 0;
        if ($this->_category->cat_name == '') {
            $this->setError("entername");
            return;
        }

        $this->_category->price1 = $this->categorydetail->editprice1->getText();
        $this->_category->price2 = $this->categorydetail->editprice2->getText();
        $this->_category->price3 = $this->categorydetail->editprice3->getText();
        $this->_category->price4 = $this->categorydetail->editprice4->getText();
        $this->_category->price5 = $this->categorydetail->editprice5->getText();

        //delete image
        if ($this->categorydetail->editdelimage->isChecked()) {
            if ($this->_category->image_id > 0) {
                Category::delete($this->_category->image_id);
            }
            $this->_category->image_id = 0;
        }

        $this->_category->save();

        $file = $this->categorydetail->editaddfile->getFile();
        if (strlen($file["tmp_name"]) > 0) {
            $imagedata = getimagesize($file["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('invalidformatimage');
                return;
            }

            if ($imagedata[0] * $imagedata[1] > 10000000) {

                $this->setError('toobigimage');
                return;
            }

            $image = new \App\Entity\Image();
            $image->content = file_get_contents($file['tmp_name']);
            $image->mime = $imagedata['mime'];

            if ($imagedata[0] != $imagedata[1]) {
                $thumb = new \App\Thumb($file['tmp_name']);
                if ($imagedata[0] > $imagedata[1]) {
                    $thumb->cropFromCenter($imagedata[1], $imagedata[1]);
                }
                if ($imagedata[0] < $imagedata[1]) {
                    $thumb->cropFromCenter($imagedata[0], $imagedata[0]);
                }
                $thumb->resize(256, 256);
                $image->content = $thumb->getImageAsString();
            }
            $conn =   \ZDB\DB::getConnect();
            if($conn->dataProvider=='postgres') {
              $image->thumb = pg_escape_bytea($image->thumb);
              $image->content = pg_escape_bytea($image->content);
                
            }

            $image->save();
            $this->_category->image_id = $image->image_id;
            $this->_category->save();
        }

        $this->categorydetail->setVisible(false);
        $this->categorytable->setVisible(true);
        $this->Reload();
    }

    public function cancelOnClick($sender) {
        $this->categorytable->setVisible(true);
        $this->categorydetail->setVisible(false);
    }

}
