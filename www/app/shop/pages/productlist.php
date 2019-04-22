<?php

namespace App\Shop\Pages;

use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Image;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\File;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Link\SubmitLink;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\BookmarkableLink;
use \ZCL\BT\Tree;
use \App\Shop\Entity\ProductGroup;
use \App\Shop\Entity\Product;
use \App\Entity\Item;
use \App\Entity\Stock;
use \App\Shop\Entity\ProductAttribute;
use \App\Shop\Entity\ProductAttributeValue;
use \App\Shop\Helper;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \ZCL\DB\EntityDataSource;
use \Zippy\Binding\PropertyBinding as PB;
use \App\System;
use \Zippy\Html\Form\AutocompleteTextInput;

class ProductList extends \App\Pages\Base {

    private $rootgroup, $product;
    private $store = "";
    private $op;
    public $group = null, $attrlist = array(), $imglist = array();

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowCat('ProductList'))
            return;

        $this->op = System::getOptions("shop");
        if (strlen($this->op['defcust']) == 0 || strlen($this->op['defstore']) == 0 || strlen($this->op['defpricetype']) == 0) {
            $this->setWarn('Не заданы все настройки магазина. Перейдите на страницу  настроек.');
        }


        $tree = $this->add(new Tree("tree"));
        $tree->onSelectNode($this, "onTree");

        $this->ReloadTree();

        $this->add(new Panel('listpanel'));
        $this->listpanel->add(new Form('searchform'))->onSubmit($this, 'searchformOnSubmit');
        $this->listpanel->searchform->add(new TextInput('skeyword'));
        $this->listpanel->searchform->add(new CheckBox('sstatus'));
        $this->listpanel->searchform->add(new DropDownChoice('smanuf', \App\Shop\Entity\Manufacturer::findArray('manufacturername', '', 'manufacturername')));
        $this->listpanel->searchform->add(new ClickLink('sclear'))->onClick($this, 'onSClear');
        $this->listpanel->add(new Form('sortform'));
        $this->listpanel->sortform->add(new DropDownChoice('sorting'))->onChange($this, 'sortingOnChange');
        $this->listpanel->add(new ClickLink('addnew'))->onClick($this, 'addnewOnClick');
        $this->listpanel->add(new DataView('plist', new ProductDataSource($this), $this, 'plistOnRow'));
        $this->listpanel->add(new \Zippy\Html\DataList\Paginator('pag', $this->listpanel->plist));
        $this->listpanel->plist->setPageSize(15);

        $this->add(new Panel('editpanel'))->setVisible(false);



        $editform = $this->editpanel->add(new Form('editform'));
        $editform->add(new AutocompleteTextInput('eitem'))->onText($this, 'OnAutoItem');
        $editform->eitem->onChange($this, 'onChangeItem');
        $editform->add(new TextInput('ename'));
        $editform->add(new TextInput('ecode'));
        $editform->add(new TextInput('eprice', 0));
        
        $editform->add(new TextArea('edescshort'));
        $editform->add(new TextArea('edescdet'));

        $editform->add(new DropDownChoice('emanuf', \App\Shop\Entity\Manufacturer::findArray('manufacturername', '', 'manufacturername')));
        $editform->add(new DropDownChoice('egroup', \App\Shop\Entity\ProductGroup::findArray('groupname', 'group_id not in (select parent_id from shop_productgroups)', 'groupname')));



        $editform->add(new DataView('attrlist', new ArrayDataSource(new PB($this, 'attrlist')), $this, 'attrlistOnRow'));
        $editform->add(new CheckBox('edisabled'));
        $editform->add(new ClickLink('bcancel'))->onClick($this, 'bcancelOnClick');
        $editform->add(new ClickLink('bdelete'))->onClick($this, 'bdeleteOnClick');

        $editform->onSubmit($this, 'onSubmitForm');

        $this->listpanel->addnew->setVisible(false);


        $this->add(new Panel('editimagepanel'))->setVisible(false);
        
        $this->editimagepanel->add(new ClickLink('backtoproduct'))->onClick($this, 'backtoproductOnClick');
        $this->editimagepanel->add(new Form('addimageform'))->onSubmit($this, "onImageSubmit");
        $this->editimagepanel->addimageform->add(new \Zippy\Html\Form\File("photo"));
        $this->editimagepanel->add(new DataView('imagelist', new ArrayDataSource(new PB($this, 'imglist')), $this, 'imglistOnRow'));
        
        
    }
           
    //загрузить дерево
    public function ReloadTree() {

        $this->tree->removeNodes();

        $this->rootgroup = new ProductGroup();
        $this->rootgroup->group_id = PHP_INT_MAX;
        $this->rootgroup->groupname = "//";

        $root = new \ZCL\BT\TreeNode("//", PHP_INT_MAX);
        $this->tree->addNode($root);

        $itemlist = ProductGroup::find("", "mpath,groupname");
        $nodelist = array();

        foreach ($itemlist as $item) {
            $node = new \ZCL\BT\TreeNode($item->groupname, $item->group_id);
            $parentnode = @$nodelist[$item->parent_id];
            if ($item->parent_id == 0)
                $parentnode = $root;

            $this->tree->addNode($node, $parentnode);

            $nodelist[$item->group_id] = $node;
        }
    }

    //клик по  узлу
    public function onTree($sender, $id) {
        $this->listpanel->addnew->setVisible(false);
        $this->editpanel->setVisible(false);

        $nodeid = $this->tree->selectedNodeId();
        if ($nodeid == -1) {
            $this->group = null;
            return;
        }
        if ($nodeid == -2) {
            $this->group = $this->rootgroup;
            return;
        }
        $this->group = ProductGroup::load($nodeid);
        if ($this->group instanceof ProductGroup) {
            $ch = $this->group->getChildren();
            //добавляем  только для  конечных групп
            $this->listpanel->addnew->setVisible(count($ch) == 0); // Добавляем  товар если  нет  дочерних груп у текущей]   
        }
        $this->listpanel->plist->Reload();
        $this->attrlist = array();

        $this->listpanel->setVisible(true);
    }

    public function searchformOnSubmit($sender) {

        $this->listpanel->plist->Reload();
    }

    public function sortingOnChange($sender) {
        $this->listpanel->plist->Reload();
    }

    public function onSClear($sender) {
        $this->listpanel->searchform->clean();
        $this->listpanel->plist->Reload();
    }

//новый
    public function addnewOnClick($sender) {

        $this->product = new Product();
        $this->product->createdon = time();
        $this->product->group_id = $this->group->group_id;
        $this->editpanel->setVisible(true);
        $this->editpanel->editform->eitem->setKey(0);
        $this->editpanel->editform->eitem->setText('');
        $this->editpanel->editform->eitem->setAttribute('readonly', null);
        $this->listpanel->setVisible(false);
        $this->attrlist = $this->product->getAttrList();
        $this->editpanel->editform->attrlist->Reload();
        $this->editpanel->editform->clean();
        $this->editpanel->editform->bdelete->setVisible(false);
        $this->editpanel->editform->egroup->setValue($this->group->group_id);
    }

    //выбран товар 
    public function onChangeItem($sender) {

        $item = Item::load($sender->getKey());
        $this->product->productname = $item->itemname;
        $this->product->item_code = $item->item_code;
        $this->editpanel->editform->ename->setText($this->product->productname);
        $this->editpanel->editform->ecode->setText($this->product->item_code);
        $this->editpanel->editform->eprice->setText($item->getPrice($this->op['defpricetype'], $this->op['defstore']));
    }

    public function OnAutoItem($sender) {

        $text = Item::qstr('%' . trim($sender->getText()) . '%');
        $list = Item::findArray("itemname", "  disabled <> 1  and (itemname like {$text} or item_code like {$text}) ");

        return $list;
    }

//строка товара
    public function plistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new ClickLink("lname", $this, "lnameOnClick"))->setValue($item->productname);
        $row->add(new ClickLink("imedit", $this, "imeditOnClick"));
        // $row->add(new Label("lmanuf", $item->manufacturername));
        $row->add(new Label("ldescshort", $item->description));
        $row->add(new Label("lcode", $item->item_code));
        $row->add(new Label("lprice", $item->price));
        //$qty=\App\Entity\Item::getQuantity($item->item_id) ;
        $row->add(new Label("lcnt", $item->qty));
        $row->add(new \Zippy\Html\Image("lphoto"))->setUrl('/loadimage.php?id=' . $item->image_id . '&t=t');
    }

//редактирование

    public function lnameOnClick($sender) {



        $this->editpanel->setVisible(true);
        $this->listpanel->setVisible(false);
        $this->product = $sender->getOwner()->getDataItem();
        $this->editpanel->editform->eitem->setAttribute('readonly', 'readonly');

        $this->editpanel->editform->ename->setText($this->product->productname);

        $item = Item::load($this->product->item_id);
        $this->editpanel->editform->eitem->setText($item->itemname);
        $this->editpanel->editform->eitem->setKey($this->product->item_id);

        $this->editpanel->editform->ecode->setText($this->product->item_code);
        $this->editpanel->editform->edescshort->setText($this->product->description);
        $this->editpanel->editform->edescdet->setText($this->product->fulldescription);
        
        $this->editpanel->editform->eprice->setText($this->product->price);
        $this->editpanel->editform->emanuf->setValue($this->product->manufacturer_id);

        $this->editpanel->editform->bdelete->setVisible(true);

        $this->editpanel->editform->edisabled->setChecked($this->product->deleted > 0);

        $this->attrlist = $this->product->getAttrList();
        $this->editpanel->editform->attrlist->Reload();
        $this->editpanel->editform->egroup->setValue($this->group->group_id);
    }

    public function onSubmitForm($sender) {
        $this->product->manufacturer_id = $sender->emanuf->getValue();

        $this->product->productname = $sender->ename->getText();
        $this->product->item_id = $sender->eitem->getKey();
        $this->product->item_code = $sender->ecode->getText();
        $this->product->group_id = $sender->egroup->getValue();
        $this->product->description = $sender->edescshort->getText();
        $this->product->fulldescription = $sender->edescdet->getText();
        $this->product->price = $sender->eprice->getText();
        $this->product->chprice = "up";
        
        $this->product->deleted = $sender->edisabled->isChecked();
        if (strlen($this->product->productname) == 0) {
            $this->setError('Не указано имя');
            return;
        }


        $this->product->attributevalues = array();


        $rows = $sender->attrlist->getChildComponents();
        foreach ($rows as $r) {
            $a = $r->getDataItem();
            $this->product->attributevalues[$a->attribute_id] = "" . $a->attributevalue;
            if ($a->nodata == 1) {
                $this->product->attributevalues[$a->attribute_id] = '';
            }
        }

        $this->product->save();
        $this->listpanel->plist->Reload();
        $this->editpanel->setVisible(false);
        $this->listpanel->setVisible(true);
    }

//строка  атрибута
    public function attrlistOnRow($row) {
        $attr = $row->getDataItem();

        //$row->add(new CheckBox("nodata", new \Zippy\Binding\PropertyBinding($attr, "nodata")));
        $row->add(new AttributeComponent('attrdata', $attr));
    }

    public function bcancelOnClick($sender) {
        $this->editpanel->setVisible(false);
        $this->listpanel->setVisible(true);
    }

    public function bdeleteOnClick($sender) {
        if ($this->product->checkDelete() == false) {
            $this->setError('Продукт уже  используется');
            return;
        }
        Product::delete($this->product->product_id);
        $this->listpanel->plist->Reload();
        $this->editpanel->setVisible(false);
        $this->listpanel->setVisible(true);
    }

    public function imeditOnClick($sender) {
        $this->product = $sender->getOwner()->getDataItem();
        $this->listpanel->setVisible(false);
        $this->editimagepanel->setVisible(true);
        $this->updateImages();
    }

    public function backtoproductOnClick($sender) {

        $this->listpanel->setVisible(true);
        $this->editimagepanel->setVisible(false);
    }

    public function onImageSubmit($sender) {

        $file = $sender->photo->getFile();
        if (strlen($file["tmp_name"]) > 0) {
            $imagedata = getimagesize($file["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('Неверный формат');
                return;
            }

            if ($imagedata[0] * $imagedata[1] > 1000000) {
                $this->setError('Слишком большой размер изображения');
                return;
            }
            $r = ((double) $imagedata[0]) / $imagedata[1];
            if ($r > 1.1 || $r < 0.9) {
                $this->setError('Изображеие должно  быть примерно квадратным');
                return;
            }

            $image = new \App\Entity\Image();
            $image->content = file_get_contents($file['tmp_name']);
            $image->mime = $imagedata['mime'];
            $th = new \JBZoo\Image\Image($file['tmp_name']);
            $th = $th->resize(256, 256);
            //$th->save();
            $image->thumb = $th->getBinary();

            $image->save();
            $this->product->images[] = $image->image_id;
            $this->product->save();
            $sender->clean();

            $this->updateImages();
        }
    }

    public function imglistOnRow($row) {
        $image = $row->getDataItem();
        $row->add(new \Zippy\html\Image("imgitem"))->setUrl("/simage/".$image->image_id);
        $row->add(new ClickLink("icover", $this, "icoverOnClick"))->setVisible($image->image_id != $this->product->image_id);
        $row->add(new ClickLink("idel", $this, "idelOnClick"));

    }

    public function icoverOnClick($sender) { 
         $image = $sender->getOwner()->getDataItem();
         $this->product->image_id = $image->image_id;
         $this->product->save();
         $this->listpanel->plist->Reload();
         $this->updateImages();
    }
    
    public function idelOnClick($sender) {
         $image = $sender->getOwner()->getDataItem();
         $this->product->images = array_diff($this->product->images, array($image->image_id));
         $this->product->save();
         $this->updateImages();
         
    }
    
    
    public function updateImages() {
        $this->imglist = array();
        
        foreach ($this->product->images as $id) {
            $this->imglist[] = \App\Entity\Image::load($id);
        }
        $this->editimagepanel->imagelist->Reload();
    }
    
 
    

}

class ProductDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();

        $where = "1=1 ";
        if ($this->page->group instanceof ProductGroup) {
            $gr = sprintf('%08s', $this->page->group->group_id);

            $where .= " and  group_id in (select group_id from shop_productgroups where mpath like '%{$gr}%' ) ";
        }

        $st = $this->page->listpanel->searchform->skeyword->getText();
        $sm = $this->page->listpanel->searchform->smanuf->getValue();
        if ($sm > 0) {
            $where .= " and manufacturer_id  =  " . $sm;
        }
        if (strlen($st) > 0) {
            $where .= " and (productname like   " . $conn->qstr("%{$st}%") . " or item_code = " . $conn->qstr($st) . ") ";
        }
        if ($this->page->listpanel->searchform->sstatus->isChecked()) {
            $where .= " and deleted = 1  ";
        } else {
            $where .= " and deleted = 0  ";
        }


        return $where;
    }

    public function getItemCount() {
        return Product::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $order = "productname ";
        $o = $this->page->listpanel->sortform->sorting->getValue();
        if ($o == 1) {
            $order = "price asc";
        }
        if ($o == 2) {
            $order = "price desc";
        }
        if ($o == 3) {
            $order = "qty asc";
        }
        if ($o == 4) {
            $order = "qty desc";
        }

        return Product::find($this->getWhere(), $order, $count, $start);
    }

    public function getItem($id) {
        
    }

}

//компонент атрибута  товара
//выводит  элементы  формы  ввода   в  зависимости  от  типа  атрибута
class AttributeComponent extends \Zippy\Html\CustomComponent implements \Zippy\Interfaces\SubmitDataRequest {

    protected $productattribute = null;

    public function __construct($id, $productattribute) {
        parent::__construct($id);
        $this->productattribute = $productattribute;
    }

    public function getContent($attributes) {
        $ret = "<td>{$this->productattribute->attributename}</td><td>";

        //'Есть/Нет'
        if ($this->productattribute->attributetype == 1) {

            if ($this->productattribute->value == 1) {
                $checked = ' checked="on"';
            }
            $ret .= "  <input type=\"checkbox\"  name=\"{$this->id}\" {$checked}   /> ";
        }
        //'Число'
        if ($this->productattribute->attributetype == 2) {

            $ret .= " <input style='width:100px;' name=\"{$this->id}\" type=\"text\" value=\"{$this->productattribute->value}\"  class=\"form-control\"  /> ";
            $ret .= "";
        }
        //'Список'
        if ($this->productattribute->attributetype == 3) {

            $ret .= " <select style='width:250px;' name=\"{$this->id}\" class=\"form-control\" ><option value=\"-1\">Не выбран</option>";
            $list = explode(',', $this->productattribute->valueslist);
            foreach ($list as $key => $value) {
                $value = trim($value);
                $sel = $sel . "<option value=\"{$key}\" " . ($this->productattribute->value === $value ? ' selected="on"' : '') . ">{$value}</option>";
            }
            $ret .= $sel . '</select> ';
        }
        //'Набор'
        if ($this->productattribute->attributetype == 4) {

            $ret .= "<div class=\"checkbox\">";


            $list = explode(',', $this->productattribute->valueslist);
            $values = explode(',', $this->productattribute->value);
            $i = 1;
            foreach ($list as $key => $value) {

                $value = trim($value);
                if (in_array($value, $values))
                    $checked = ' checked="on"';
                else
                    $checked = "";

                $name = $this->id . '_' . $i++;
                $ret = $ret . "<input  name=\"{$name}\" type=\"checkbox\"  {$checked}> {$value}";
                $ret .= "<br>";
            }

            $ret .= "</div>";
        }
        //'Строка'
        if ($this->productattribute->attributetype == 5) {

            $ret .= "<textarea style='width:200px;height:60px;' name=\"{$this->id}\" type=\"text\"      class=\"form-control\" >{$this->productattribute->value}</textarea> ";
        }
        if ($this->productattribute->nodata == 1) {
            $checked = ' checked="on"';
        }
        $ret .= "</td><td> <input {$checked}    type=\"checkbox\" name=\"dis{$this->id}\">  Н/Д  
                                    
                     </td> ";

        return $ret;
    }

    //Вынимаем данные формы  после  сабмита
    public function getRequestData() {

        if ($this->productattribute->attributetype == 1) {
            $this->productattribute->attributevalue = isset($_POST[$this->id]) ? 1 : 0;
        };
        if ($this->productattribute->attributetype == 2 || $this->productattribute->attributetype == 5) {
            $this->productattribute->attributevalue = $_POST[$this->id];
        }
        if ($this->productattribute->attributetype == 3) {
            $list = explode(',', $this->productattribute->valueslist);

            $this->productattribute->attributevalue = $list[$_POST[$this->id]];
        }

        if ($this->productattribute->attributetype == 4) {
            $values = array();
            $list = explode(',', $this->productattribute->valueslist);
            $i = 1;
            foreach ($list as $key => $value) {
                $name = $this->id . '_' . $i++;
                if (isset($_POST[$name])) {
                    $values[] = trim($value);
                }
            }
            $this->productattribute->attributevalue = implode(',', $values);
        };
        $this->productattribute->nodata = 0;
        if (isset($_POST['dis' . $this->id])) {
            $this->productattribute->nodata = 1;
            $this->productattribute->attributevalue = '';
        }
    }

    public function clean() {
        $this->value = array();
    }

}
