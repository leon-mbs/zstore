<?php

namespace App\Pages\Service;

use App\Entity\Supplier;
use App\Entity\Item;
use App\Entity\SupItem;
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
use Zippy\Html\Link\SubmitLink;

class SupItems extends \App\Pages\Base
{

    private $_item;
 

    public function __construct($add = false) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('SupItems')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
 
        $this->filter->add(new TextInput('searchitem'));
       
        $this->filter->add(new DropDownChoice('searchsup', Supplier::findArray("sup_name","disabled<>1","sup_name"), 0));
      
        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->itemtable->add(new Form('listform'));

        $this->itemtable->listform->add(new DataView('itemlist', new SupItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->listform->itemlist->setPageSize(H::getPG());
        $this->itemtable->listform->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->listform->itemlist));
        $this->itemtable->listform->add(new SubmitLink('deleteall'))->onClick($this, 'OnDelAll');
     
 
        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
        $this->itemdetail->add(new TextInput('editshortname'));
        $this->itemdetail->add(new TextInput('editprice1'));
 
        $this->itemdetail->add(new TextInput('editmanufacturer'));
        $this->itemdetail->add(new TextInput('editurl'));
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
        $this->itemdetail->add(new TextInput('editzarp'));
        $this->itemdetail->add(new TextInput('editweight'));
        $this->itemdetail->add(new TextInput('editmaxsize'));
        $this->itemdetail->add(new TextInput('editvolume'));
        $this->itemdetail->add(new TextInput('editcustomsize'));
        $this->itemdetail->add(new TextInput('editwarranty'));
        $this->itemdetail->add(new TextInput('editlost'));

        $this->itemdetail->add(new TextInput('editcell'));
        $this->itemdetail->add(new TextInput('editmsr'));

        $this->itemdetail->add(new DropDownChoice('editcat', Category::findArray("cat_name", "cat_id not in (select coalesce(parent_id,0) from item_cat  )", "cat_name"), 0));
        $this->itemdetail->add(new TextInput('editcode'));
        $this->itemdetail->add(new TextArea('editdescription'));
        $this->itemdetail->add(new CheckBox('editdisabled'));
        $this->itemdetail->add(new CheckBox('edituseserial'));
        $this->itemdetail->add(new CheckBox('editnoprice'));
        $this->itemdetail->add(new CheckBox('editnoshop'));
        $this->itemdetail->add(new CheckBox('editautooutcome'));
        $this->itemdetail->add(new CheckBox('editautoincome'));
        $this->itemdetail->add(new \Zippy\Html\Image('editimage', '/loadimage.php?id=0'));
        $this->itemdetail->add(new \Zippy\Html\Form\File('editaddfile'));
        $this->itemdetail->add(new CheckBox('editdelimage'));
        $this->itemdetail->add(new DropDownChoice('edittype', Item::getTypes()));

        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

   
    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('cat_name', $item->cat_name));
        $row->add(new Label('manufacturer', $item->manufacturer));

        $row->add(new Label('price1', $item->price1));
        $row->add(new Label('price2', $item->price2));
        $row->add(new Label('price3', $item->price3));
        $row->add(new Label('price4', $item->price4));
        $row->add(new Label('price5', $item->price5));

        $row->add(new Label('hasnotes'))->setVisible(strlen($item->description) > 0);
        $row->hasnotes->setAttribute('title', htmlspecialchars_decode($item->description));
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);

        $row->add(new Label('cell', $item->cell));
        $row->add(new Label('inseria'))->setVisible($item->useserial);
        $row->add(new Label('hasaction'))->setVisible($item->hasAction());

        $row->add(new ClickLink('copy'))->onClick($this, 'copyOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

        $row->add(new ClickLink('set'))->onClick($this, 'setOnClick');
        $row->set->setVisible($item->item_type == Item::TYPE_PROD || $item->item_type == Item::TYPE_HALFPROD);

        $row->add(new ClickLink('printqr'))->onClick($this, 'printQrOnClick', true);
        $row->printqr->setVisible(strlen($item->url) > 0);

        
        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?t=t&id={$item->image_id}");
        if(strlen($item->thumb)>0) {
           $row->imagelistitem->setValue($item->thumb);    
        }
        
        $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('data-gallery', $item->image_id);
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }

        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($item, 'seldel')));

    }

 
    public function editOnClick($sender) {
        $this->_copy = false;
        $item = $sender->owner->getDataItem();
        $this->_item = Item::load($item->item_id);

        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editname->setText($this->_item->itemname);
        $this->itemdetail->editshortname->setText($this->_item->shortname);
        $this->itemdetail->editprice1->setText($this->_item->price1);
        $this->itemdetail->editprice2->setText($this->_item->price2);
        $this->itemdetail->editprice3->setText($this->_item->price3);
        $this->itemdetail->editprice4->setText($this->_item->price4);
        $this->itemdetail->editprice5->setText($this->_item->price5);
        $this->itemdetail->editcat->setValue($this->_item->cat_id);

        $this->itemdetail->editmanufacturer->setText($this->_item->manufacturer);
        $this->itemdetail->editmanufacturer->setDataList(Item::getManufacturers());


        $this->itemdetail->editdescription->setText($this->_item->description);
        $this->itemdetail->editcode->setText($this->_item->item_code);
        $this->itemdetail->editbarcode->setText($this->_item->bar_code);
        $this->itemdetail->editmsr->setText($this->_item->msr);
        $this->itemdetail->editmaxsize->setText($this->_item->maxsize);
        $this->itemdetail->editvolume->setText($this->_item->volume);
        $this->itemdetail->editlost->setText($this->_item->lost);
        $this->itemdetail->editcustomsize->setText($this->_item->customsize);
        $this->itemdetail->editwarranty->setText($this->_item->warranty);
        $this->itemdetail->edittype->setValue($this->_item->item_type);

        $this->itemdetail->editurl->setText($this->_item->url);
        $this->itemdetail->editweight->setText($this->_item->weight);
        $this->itemdetail->editcell->setText($this->_item->cell);
        $this->itemdetail->editminqty->setText(\App\Helper::fqty($this->_item->minqty));
        $this->itemdetail->editzarp->setText(\App\Helper::fqty($this->_item->zarp));
        $this->itemdetail->editdisabled->setChecked($this->_item->disabled);
        $this->itemdetail->edituseserial->setChecked($this->_item->useserial);
        $this->itemdetail->editnoshop->setChecked($this->_item->noshop);
        $this->itemdetail->editnoprice->setChecked($this->_item->noprice);
        $this->itemdetail->editautooutcome->setChecked($this->_item->autooutcome);
        $this->itemdetail->editautoincome->setChecked($this->_item->autoincome);
        if ($this->_item->image_id > 0) {
            $this->itemdetail->editdelimage->setChecked(false);
            $this->itemdetail->editdelimage->setVisible(true);
            $this->itemdetail->editimage->setVisible(true);
            $this->itemdetail->editimage->setUrl('/loadimage.php?id=' . $this->_item->image_id);
        } else {
            $this->itemdetail->editdelimage->setVisible(false);
            $this->itemdetail->editimage->setVisible(false);
        }

        $this->itemtable->listform->itemlist->setSelectedRow($sender->getOwner());
        $this->itemtable->listform->itemlist->Reload(false);

        $this->filter->searchbrand->setDataList(Item::getManufacturers());

    }

    public function addOnClick($sender) {
        $this->_copy = false;
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        $this->itemdetail->editmsr->setText('шт');
        $this->itemdetail->editimage->setVisible(false);
        $this->itemdetail->editdelimage->setVisible(false);
        $this->itemdetail->editnoprice->setChecked(false);
        $this->itemdetail->editnoshop->setChecked(false);
        $this->itemdetail->editautooutcome->setChecked(false);
        $this->itemdetail->editautoincome->setChecked(false);
        $this->_item = new Item();

        if (System::getOption("common", "autoarticle") == 1) {
            $this->itemdetail->editcode->setText(Item::getNextArticle());
        }
        $this->itemdetail->editmanufacturer->setDataList(Item::getManufacturers());

    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->itemtable->listform->itemlist->Reload();
    }

    public function OnSubmit($sender) {
        if (false == \App\ACL::checkEditRef('SupItems')) {
            return;
        }

        $this->_item->itemname = $this->itemdetail->editname->getText();
        $this->_item->itemname = trim($this->_item->itemname);
        
        if (strlen($this->_item->itemname) == 0) {
            $this->setError('entername');
            return;
        }
        
        $this->_item->shortname = $this->itemdetail->editshortname->getText();
        $this->_item->cat_id = $this->itemdetail->editcat->getValue();
        $this->_item->price1 = $this->itemdetail->editprice1->getText();
        $this->_item->price2 = $this->itemdetail->editprice2->getText();
        $this->_item->price3 = $this->itemdetail->editprice3->getText();
        $this->_item->price4 = $this->itemdetail->editprice4->getText();
        $this->_item->price5 = $this->itemdetail->editprice5->getText();

        $this->_item->item_code = trim($this->itemdetail->editcode->getText());
        $this->_item->manufacturer = trim($this->itemdetail->editmanufacturer->getText());

        $this->_item->bar_code = trim($this->itemdetail->editbarcode->getText());
        $this->_item->url = trim($this->itemdetail->editurl->getText());
        $this->_item->msr = $this->itemdetail->editmsr->getText();
        $this->_item->weight = $this->itemdetail->editweight->getText();
        $this->_item->maxsize = $this->itemdetail->editmaxsize->getText();
        $this->_item->volume = $this->itemdetail->editvolume->getText();
        $this->_item->lost = $this->itemdetail->editlost->getText();
        $this->_item->customsize = $this->itemdetail->editcustomsize->getText();
        $this->_item->warranty = $this->itemdetail->editwarranty->getText();
        $this->_item->item_type = $this->itemdetail->edittype->getValue();

        $this->_item->cell = $this->itemdetail->editcell->getText();
        $this->_item->minqty = $this->itemdetail->editminqty->getText();
        $this->_item->zarp = $this->itemdetail->editzarp->getText();
        $this->_item->description = $this->itemdetail->editdescription->getText();
        $this->_item->disabled = $this->itemdetail->editdisabled->isChecked() ? 1 : 0;
        $this->_item->useserial = $this->itemdetail->edituseserial->isChecked() ? 1 : 0;

        $this->_item->noprice = $this->itemdetail->editnoprice->isChecked() ? 1 : 0;
        $this->_item->noshop = $this->itemdetail->editnoshop->isChecked() ? 1 : 0;
        $this->_item->autooutcome = $this->itemdetail->editautooutcome->isChecked() ? 1 : 0;
        $this->_item->autoincome = $this->itemdetail->editautoincome->isChecked() ? 1 : 0;

        //проверка  уникальности артикула
        if (strlen($this->_item->item_code) > 0 && System::getOption("common", "nocheckarticle") != 1) {
            $code = Item::qstr($this->_item->item_code);
            $cnt = Item::findCnt("item_id <> {$this->_item->item_id} and item_code={$code} ");
            if ($cnt > 0) {
 
                    $this->setError('itemcode_exists');
                    return;
                
            }
        }
    
    
        if (strlen($this->_item->item_code) == 0 && System::getOption("common", "autoarticle") == 1) {
                    $this->_item->item_code = Item::getNextArticle();
                    $this->itemdetail->editcode->setText($this->_item->item_code);

                     
         }    
    
        //проверка  уникальности штрих кода
        if (strlen($this->_item->bar_code) > 0) {
            $code = Item::qstr($this->_item->bar_code);
            $cnt = Item::findCnt("item_id <> {$this->_item->item_id} and bar_code={$code} ");
            if ($cnt > 0) {
                $this->setWarn('barcode_exists');
            }
        }
        $printer = System::getOptions('printer');

        if (intval($printer['pmaxname']) > 0 && strlen($this->_item->shortname) > intval($printer['pmaxname'])) {

            $this->setWarn('tolongshortname', $printer['pmaxname']);

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
            $this->_item->thumb = "";
        }

        if ($this->_item->image_id > 0 && $this->_copy == true) {
            $image = \App\Entity\Image::load($this->_item->image_id);
            $image->image_id = 0;
            $image->save();
            $this->_item->image_id = $image->image_id;
            $this->_item->thumb="";
        }

        $this->_item->save();

        $file = $this->itemdetail->editaddfile->getFile();
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


                $image->content = $thumb->getImageAsString();
                $thumb->resize(256, 256);
                $image->thumb = $thumb->getImageAsString();
                $thumb->resize(64, 64);
                
                $this->_item->thumb = "data:{$image->mime};base64," . base64_encode($thumb->getImageAsString());
            }



            $image->save();
            $this->_item->image_id = $image->image_id;
            $this->_item->save();

            $this->filter->searchbrand->setDataList(Item::getManufacturers());
        
        }

        $this->itemtable->listform->itemlist->Reload(false);

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

 
 
    public function OnDelAll($sender) {
        if (false == \App\ACL::checkDelRef('ItemList')) {
            return;
        }

        $ids = array();
        foreach ($this->itemtable->listform->itemlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                $ids[] = $item->item_id;
            }
        }
        if (count($ids) == 0) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $d = 0;
        $u = 0;
        foreach ($ids as $id) {
            $sql = "  select count(*)  from  store_stock where   item_id = {$id}  ";
            $cnt = $conn->GetOne($sql);
            if ($cnt > 0) {
                $u++;
                $conn->Execute("update items  set  disabled=1 where   item_id={$id}");
            } else {
                $d++;
                $conn->Execute("delete from items  where   item_id={$id}");

            }
        }


        $this->setSuccess("delitems", $d, $u);

        $this->itemtable->listform->itemlist->Reload();

    }


}

class SupItemDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filter;
        $where = "1=1";
        $cat = $form->searchcat->getValue();
       
        if ($cat != 0) {
            if ($cat == -1) {
                $where = $where . " and cat_id=0";
            } else {
                $where = $where . " and cat_id=" . $cat;
            }
        }

     

    
 
        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $sortfield = "itemname asc";
     
        $l = Item::find($this->getWhere(), $sortfield, $count, $start);
   
        return $l;
    }

    public function getItem($id) {
        return Item::load($id);
    }

}
