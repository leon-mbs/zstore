<?php

namespace App\Pages\Reference;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\ItemSet;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class ItemList extends \App\Pages\Base
{

    private $_item;
    private $_pitem_id = 0;
    public $_itemset = array();

    public function __construct($add = false) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ItemList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new CheckBox('showdis'));
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchcat', Category::findArray("cat_name", "", "cat_name"), 0));

        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new DataView('itemlist', new ItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->itemtable->itemlist->setPageSize(H::getPG());
        $this->itemtable->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->itemlist));

        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
        $this->itemdetail->add(new TextInput('editprice1'));
        $this->itemdetail->add(new TextInput('editprice2'));
        $this->itemdetail->add(new TextInput('editprice3'));
        $this->itemdetail->add(new TextInput('editprice4'));
        $this->itemdetail->add(new TextInput('editprice5'));
        $this->itemdetail->add(new TextInput('editmanufacturer'));
        $common = System::getOptions('common');
        if (strlen($common['price1']) > 0) {
            $this->itemdetail->editprice1->setVisible(true);
            $this->itemdetail->editprice1->setAttribute('placeholder', $common['price1']);
        } else {
            $this->itemdetail->editprice1->setVisible(false);
        }
        if (strlen($common['price2']) > 0) {
            $this->itemdetail->editprice2->setVisible(true);
            $this->itemdetail->editprice2->setAttribute('placeholder', $common['price2']);
        } else {
            $this->itemdetail->editprice2->setVisible(false);
        }
        if (strlen($common['price3']) > 0) {
            $this->itemdetail->editprice3->setVisible(true);
            $this->itemdetail->editprice3->setAttribute('placeholder', $common['price3']);
        } else {
            $this->itemdetail->editprice3->setVisible(false);
        }
        if (strlen($common['price4']) > 0) {
            $this->itemdetail->editprice4->setVisible(true);
            $this->itemdetail->editprice4->setAttribute('placeholder', $common['price4']);
        } else {
            $this->itemdetail->editprice4->setVisible(false);
        }
        if (strlen($common['price5']) > 0) {
            $this->itemdetail->editprice5->setVisible(true);
            $this->itemdetail->editprice5->setAttribute('placeholder', $common['price5']);
        } else {
            $this->itemdetail->editprice5->setVisible(false);
        }
        $this->itemdetail->add(new TextInput('editbarcode'));
        $this->itemdetail->add(new TextInput('editminqty'));
        $this->itemdetail->add(new TextInput('editweight'));

        $this->itemdetail->add(new TextInput('editcell'));
        $this->itemdetail->add(new TextInput('editmsr'));
        $this->itemdetail->add(new DropDownChoice('editcat', Category::findArray("cat_name", "", "cat_name"), 0));
        $this->itemdetail->add(new TextInput('editcode'));
        $this->itemdetail->add(new TextArea('editdescription'));
        $this->itemdetail->add(new CheckBox('editdisabled'));
        $this->itemdetail->add(new CheckBox('edituseserial'));
        $this->itemdetail->add(new CheckBox('editpricelist', true));
        $this->itemdetail->add(new \Zippy\Html\Image('editimage', '/LoadImage.php?id=0'));
        $this->itemdetail->add(new \Zippy\Html\Form\File('editaddfile'));
        $this->itemdetail->add(new CheckBox('editdelimage'));

        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');


        $this->add(new Panel('setpanel'))->setVisible(false);
        $this->setpanel->add(new DataView('setlist', new ArrayDataSource($this, '_itemset'), $this, 'itemsetlistOnRow'));
        $this->setpanel->add(new Form('setform'))->onSubmit($this, 'OnAddSet');
        $this->setpanel->setform->add(new AutocompleteTextInput('editsname'))->onText($this, 'OnAutoSet');
        $this->setpanel->setform->add(new TextInput('editsqty', 1));

        $this->setpanel->add(new Label('stitle'));
        $this->setpanel->add(new ClickLink('backtolist', $this, "onback"));


        if ($add == false) {
            $this->itemtable->itemlist->Reload();
        } else {
            $this->addOnClick(null);
        }


    }

    public function itemlistOnRow($row) {
        $item = $row->getDataItem();
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('cat_name', $item->cat_name));
        $plist = array();
        if ($item->price1 > 0) {
            $plist[] = H::fa($item->price1);
        }
        if ($item->price2 > 0) {
            $plist[] = H::fa($item->price2);
        }
        if ($item->price3 > 0) {
            $plist[] = H::fa($item->price3);
        }
        if ($item->price4 > 0) {
            $plist[] = H::fa($item->price4);
        }
        if ($item->price5 > 0) {
            $plist[] = H::fa($item->price5);
        }
        $row->add(new Label('price', implode(', ', $plist)));
        $row->add(new Label('desc', htmlspecialchars_decode($item->description), true));
        $row->add(new Label('cell', $item->cell));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('set'))->onClick($this, 'setOnClick');
        $row->add(new ClickLink('print'))->onClick($this, 'printOnClick', true);

        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }


    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('ItemList')) {
            return;
        }

        $item = $sender->owner->getDataItem();

        $del = Item::delete($item->item_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }


        $this->itemtable->itemlist->Reload(false);
        $this->resetURL();
    }

    public function editOnClick($sender) {
        $this->_item = $sender->owner->getDataItem();
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editname->setText($this->_item->itemname);
        $this->itemdetail->editprice1->setText(H::fa($this->_item->price1));
        $this->itemdetail->editprice2->setText(H::fa($this->_item->price2));
        $this->itemdetail->editprice3->setText(H::fa($this->_item->price3));
        $this->itemdetail->editprice4->setText(H::fa($this->_item->price4));
        $this->itemdetail->editprice5->setText(H::fa($this->_item->price5));
        $this->itemdetail->editcat->setValue($this->_item->cat_id);

        $this->itemdetail->editmanufacturer->setText($this->_item->manufacturer);
        $this->itemdetail->editdescription->setText($this->_item->description);
        $this->itemdetail->editcode->setText($this->_item->item_code);
        $this->itemdetail->editbarcode->setText($this->_item->bar_code);
        $this->itemdetail->editmsr->setText($this->_item->msr);
        $this->itemdetail->editweight->setText($this->_item->weight);

        $this->itemdetail->editcell->setText($this->_item->cell);
        $this->itemdetail->editminqty->setText(\App\Helper::fqty($this->_item->minqty));
        $this->itemdetail->editdisabled->setChecked($this->_item->disabled);
        $this->itemdetail->edituseserial->setChecked($this->_item->useserial);
        $this->itemdetail->editpricelist->setChecked($this->_item->pricelist);
        if ($this->_item->image_id > 0) {
            $this->itemdetail->editdelimage->setChecked(false);
            $this->itemdetail->editdelimage->setVisible(true);
            $this->itemdetail->editimage->setVisible(true);
            $this->itemdetail->editimage->setUrl('/LoadImage.php?id=' . $this->_item->image_id);
        } else {
            $this->itemdetail->editdelimage->setVisible(false);
            $this->itemdetail->editimage->setVisible(false);
        }
    }

    public function addOnClick($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        $this->itemdetail->editmsr->setText('шт');
        $this->itemdetail->editimage->setVisible(false);
        $this->itemdetail->editdelimage->setVisible(false);
        $this->itemdetail->editpricelist->setChecked(true);
        $this->_item = new Item();

        if (System::getOption("common", "autoarticle") == 1) {
            $this->itemdetail->editcode->setText(Item::getNextArticle());
        }
    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->itemtable->itemlist->Reload();
    }

    public function OnSubmit($sender) {
        if (false == \App\ACL::checkEditRef('ItemList')) {
            return;
        }

        $this->_item->itemname = $this->itemdetail->editname->getText();
        $this->_item->cat_id = $this->itemdetail->editcat->getValue();
        $this->_item->price1 = $this->itemdetail->editprice1->getText();
        $this->_item->price2 = $this->itemdetail->editprice2->getText();
        $this->_item->price3 = $this->itemdetail->editprice3->getText();
        $this->_item->price4 = $this->itemdetail->editprice4->getText();
        $this->_item->price5 = $this->itemdetail->editprice5->getText();

        $this->_item->item_code = trim($this->itemdetail->editcode->getText());
        $this->_item->manufacturer = trim($this->itemdetail->editmanufacturer->getText());

        $this->_item->bar_code = trim($this->itemdetail->editbarcode->getText());
        $this->_item->msr = $this->itemdetail->editmsr->getText();
        $this->_item->weight = $this->itemdetail->editweight->getText();

        $this->_item->cell = $this->itemdetail->editcell->getText();
        $this->_item->minqty = $this->itemdetail->editminqty->getText();
        $this->_item->description = $this->itemdetail->editdescription->getText();
        $this->_item->disabled = $this->itemdetail->editdisabled->isChecked() ? 1 : 0;
        $this->_item->useserial = $this->itemdetail->edituseserial->isChecked() ? 1 : 0;

        $this->_item->pricelist = $this->itemdetail->editpricelist->isChecked() ? 1 : 0;


        //проверка  уникальности артикула
        if (strlen($this->_item->item_code) > 0) {
            $code = Item::qstr($this->_item->item_code);
            $cnt = Item::findCnt("item_id <> {$this->_item->item_id} and item_code={$code} ");
            if ($cnt > 0) {
                //пытаемся генерить еще раз 
                if ($this->_item->item_id == 0 && System::getOption("common", "autoarticle") == 1) {
                    $this->_item->item_code = Item::getNextArticle();
                    $this->itemdetail->editcode->setText($this->_item->item_code);

                    $cnt = Item::findCnt("item_id <> {$this->_item->item_id} and item_code={$code} ");
                    if ($cnt > 0) {

                        $this->setError('itemcode_exists');
                        return;
                    }
                } else {
                    $this->setError('itemcode_exists');
                    return;
                }
            }
        }

        $itemname = Item::qstr($this->_item->itemname);
        $code = Item::qstr($this->_item->item_code);
        $cnt = Item::findCnt("item_id <> {$this->_item->item_id} and itemname={$itemname} and item_code={$code} ");
        if ($cnt > 0) {
            $this->setError('itemnamecode_exists');
            return;
        }


        //delete image
        if ($this->itemdetail->editdelimage->isChecked()) {
            if ($this->_item->image_id > 0) {
                \App\Entity\Image::delete($this->_item->image_id);
            }
            $this->_item->image_id = 0;
        }


        $this->_item->Save();


        $file = $this->itemdetail->editaddfile->getFile();
        if (strlen($file["tmp_name"]) > 0) {
            $imagedata = getimagesize($file["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('invalidformatimage');
                return;
            }

            if ($imagedata[0] * $imagedata[1] > 1000000) {

                $this->setError('toobigimage');
                return;
            }

            $image = new \App\Entity\Image();
            $image->content = file_get_contents($file['tmp_name']);
            $image->mime = $imagedata['mime'];

            $image->save();
            $this->_item->image_id = $image->image_id;
            $this->_item->Save();
        }


        $this->itemtable->itemlist->Reload(false);

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

    //комплекты
    public function onback($sender) {
        $this->setpanel->setVisible(false);
        $this->itemtable->setVisible(true);
    }

    public function setOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->_pitem_id = $item->item_id;
        $this->_itemset = ItemSet::find("pitem_id=" . $item->item_id, "itemname");
        $this->setpanel->setVisible(true);
        $this->itemtable->setVisible(false);

        $this->setpanel->stitle->setText($item->itemname);

        $this->_itemset = ItemSet::find("pitem_id=" . $this->_pitem_id, "itemname");
        $this->setpanel->setlist->Reload();
    }

    public function itemsetlistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('sname', $item->itemname));
        $row->add(new Label('scode', $item->item_code));
        $row->add(new Label('sqty', H::fqty($item->qty)));
        $row->add(new ClickLink('sdel'))->onClick($this, 'ondelset');
    }

    public function OnAutoSet($sender) {
        $text = Item::qstr('%' . $sender->getText() . '%');
        $in = "(" . $this->_pitem_id;
        foreach ($this->_itemset as $is) {
            $in .= "," . $is->item_id;
        }

        $in .= ")";
        return Item::findArray('itemname', "item_id not in {$in} and (itemname like {$text} or item_code like {$text}) and disabled <> 1");
    }

    public function OnAddSet($sender) {
        $id = $sender->editsname->getKey();
        if ($id == 0) {
            $this->setError("noselitem");
            return;
        }

        $qty = $sender->editsqty->getText();

        $set = new ItemSet();
        $set->pitem_id = $this->_pitem_id;
        $set->item_id = $id;
        $set->qty = $qty;

        $set->save();

        $this->_itemset = ItemSet::find("pitem_id=" . $this->_pitem_id, "itemname");

        $this->setpanel->setlist->Reload();
        $sender->clean();
    }

    public function ondelset($sender) {
        $item = $sender->owner->getDataItem();

        ItemSet::delete($item->set_id);

        $this->_itemset = ItemSet::find("pitem_id=" . $this->_pitem_id, "itemname");

        $this->setpanel->setlist->Reload();
    }

    public function printOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $printer = \App\System::getOptions('printer');
        $wp = 'style="width:40mm"';
        if (strlen($printer['pwidth']) > 0) {
            $wp = 'style="width:' . $printer['pwidth'] . 'mm"';
        }
        $report = new \App\Report('item_tag.tpl');
        $header = array('printw' => $wp);
        if ($printer['pname'] == 1) {
            $header['name'] = $item->itemname;
        }
        if ($printer['pprice'] == 1) {
            $header['price'] = number_format($item->getPrice($printer['pricetype']), 2, '.', '');
        }
        if ($printer['pcode'] == 1) {
            $header['article'] = $item->item_code;
        }

        if ($printer['pbarcode'] == 1) {
            $barcode = $item->bar_code;
            if (strlen($barcode) == 0) {
                $barcode = $item->item_code;
            }
            if (($barcode > 0) == false) {
                $this->updateAjax(array(), "  alert('Не цифровой  код')");
                return;
            }
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $img = '<img src="data:image/png;base64,' . base64_encode($generator->getBarcode($barcode, $printer['barcodetype'])) . '">';
            $header['img'] = $img;
            $header['barcode'] = \App\Util::addSpaces($barcode);
        }


        $html = $report->generate($header);
        $this->updateAjax(array(), "  $('#tag').html('{$html}') ; $('#pform').modal()");
    }

}

class ItemDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $cat = $form->searchcat->getValue();
        $showdis = $form->showdis->isChecked();

        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }
        if ($showdis == true) {

        } else {
            $where = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            if ($p == false) {
                $text = Item::qstr('%' . $text . '%');
                $where = $where . " and (itemname like {$text} or item_code like {$text} )  ";
            } else {
                $text = Item::qstr($text);
                $where = $where . " and (itemname = {$text} or item_code = {$text} )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = Item::find($this->getWhere(true), "itemname asc", $count, $start);
        $f = Item::find($this->getWhere(), "itemname asc", $count, $start);
        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }
        return $l;
    }

    public function getItem($id) {
        return Item::load($id);
    }

}
