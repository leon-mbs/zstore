<?php

namespace App\Modules\Shop\Pages;

use App\Application as App;
use App\Entity\Item;
use App\Modules\Shop\Entity\Product;
 
use \App\Entity\Category;

use App\System;
use ZCL\BT\Tree;
use Zippy\Binding\PropertyBinding as PB;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class ProductList extends \App\Pages\Base
{

    private   $_item;
    private             $store = "";
    private             $op;
    public                $attrlist = array(), $imglist = array();
    public $group = null  ;
    public   $_grouplist = array()  ;    

    public function __construct() {
        parent::__construct();
        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg('noaccesstopage');
            App::RedirectError();
            return;
        }

        $this->op = System::getOptions("shop");
        if (strlen($this->op['defcust']) == 0 || strlen($this->op['defstore']) == 0 || strlen($this->op['defpricetype']) == 0) {

            $this->setWarn('notsetoptionsmag');
        }


        $clist =  Category::find(" cat_id in(select cat_id from items where disabled <>1)  and  detail not  like '%<noshop>1</noshop>%' " ) ;
        
        $this->_grouplist =  Category::findFullData($clist) ;
       
        usort($this->_grouplist,function($a,$b){ return $a->full_name > $b->full_name; }) ;
        
        $fc = new Category();
        $fc->cat_id=0;
        $fc->cat_name=  \App\Helper::l("allcategory");
        $fc->full_name= \App\Helper::l("allcategory");
        
        $first=array($fc) ;
        
        
        $this->_grouplist = array_merge($first,$this->_grouplist);
        $this->add(new DataView('grouplist', new ArrayDataSource($this,'_grouplist'), $this, 'OnGroupRow'));
        $this->grouplist->setSelectedClass('table-success');
        $this->grouplist->Reload();


        $this->add(new Panel('listpanel'));
        $this->listpanel->add(new Form('searchform'))->onSubmit($this, 'searchformOnSubmit');
        $this->listpanel->searchform->add(new TextInput('skeyword'));
        $this->listpanel->searchform->add(new CheckBox('sstatus'));
        $this->listpanel->searchform->add(new DropDownChoice('smanuf', array()));
        $this->listpanel->searchform->add(new ClickLink('sclear'))->onClick($this, 'onSClear');
        $this->listpanel->add(new Form('sortform'));
        $this->listpanel->sortform->add(new DropDownChoice('sorting'))->onChange($this, 'sortingOnChange');
        $this->listpanel->add(new ClickLink('addnew'))->onClick($this, 'addnewOnClick');
        $this->listpanel->add(new DataView('plist', new ProductDataSource($this), $this, 'plistOnRow'));
        $this->listpanel->add(new \Zippy\Html\DataList\Paginator('pag', $this->listpanel->plist));
        $this->listpanel->plist->setPageSize(15);
        $this->listpanel->setVisible(false); 
        $this->add(new Panel('editpanel'))->setVisible(false);


        $editform = $this->editpanel->add(new Form('editform'));
        $editform->add(new TextInput('ename'));
 
        $editform->add(new TextArea('edescdet'));
   
        $editform->add(new DataView('attrlist', new ArrayDataSource(new PB($this, 'attrlist')), $this, 'attrlistOnRow'));
        
        $editform->add(new ClickLink('bcancel'))->onClick($this, 'bcancelOnClick');
        

        $editform->onSubmit($this, 'onSubmitForm');

        $this->listpanel->addnew->setVisible(false);


        $this->add(new Panel('editimagepanel'))->setVisible(false);

        $this->editimagepanel->add(new ClickLink('backtoproduct'))->onClick($this, 'backtoproductOnClick');
        $this->editimagepanel->add(new Form('addimageform'))->onSubmit($this, "onImageSubmit");
        $this->editimagepanel->addimageform->add(new \Zippy\Html\Form\File("photo"));
        $this->editimagepanel->add(new DataView('imagelist', new ArrayDataSource(new PB($this, 'imglist')), $this, 'imglistOnRow'));
    }

 

    public  function    OnGroupRow($row){
        $group = $row->getDataItem() ;
        $row->add(new  ClickLink('groupname',$this,'onGroup'))->setValue($group->full_name) ;
    }
    
    public function onGroup($sender ) {
        $this->group = $sender->getOwner()->getDataItem();
        $this->grouplist->setSelectedRow($sender->getOwner());
      
        $this->grouplist->Reload(false);        
        $this->editpanel->setVisible(false);     
        $this->listpanel->setVisible(true);     
        $this->listpanel->plist->Reload();
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

 

//строка товара
    public function plistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new ClickLink("lname", $this, "lnameOnClick"))->setValue($item->itemname);
        $row->add(new ClickLink("imedit", $this, "imeditOnClick"));
    
        $row->add(new Label("lcode", $item->item_code));
        $row->add(new Label("lprice", \App\Helper::fa($item->getPrice())));
        
        $row->add(new Label("lcnt", \App\Helper::fqty($item->qty)));
        $row->add(new \Zippy\Html\Image("lphoto"))->setUrl('/loadshopimage.php?id=' . $item->image_id . '&t=t');
    }

//редактирование

    public function lnameOnClick($sender) {


        $this->editpanel->setVisible(true);
        $this->listpanel->setVisible(false);
        $this->_item = $sender->getOwner()->getDataItem();
      
        $this->editpanel->editform->ename->setText($this->_item->getName());

        
      
        $this->editpanel->editform->edescdet->setText($this->_item->getDescription());

      
        
        $this->attrlist = $this->_item->getAttrList();
        $this->editpanel->editform->attrlist->Reload();
 
    }

    public function onSubmitForm($sender) {
    
        $this->_item->product->name = $sender->ename->getText();
   
        $this->_item->product->desc = $sender->edescdet->getText();
    
        $this->_item->product->attributevalues = array();


        $rows = $sender->attrlist->getChildComponents();
        foreach ($rows as $r) {
            $a = $r->getDataItem();
            
            $this->_item->product->attributevalues[$a->attribute_id] = "" . $a->attributevalue;
            if ($a->nodata == 1) {
                $this->_item->product->attributevalues[$a->attribute_id] = '';
            }
        }

        $this->_item->save();
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

 

    public function imeditOnClick($sender) {
        $this->_item = $sender->getOwner()->getDataItem();
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
                $this->setError('invalidformat');
                return;
            }

            if ($imagedata[0] * $imagedata[1] > 1000000) {
                $this->setError('toobigimage');
                return;
            }
          
  
            $image = new \App\Entity\Image();
            $image->content = file_get_contents($file['tmp_name']);
            $image->mime = $imagedata['mime'];

            if($imagedata[0] != $imagedata[1] ) {
              $thumb = new \App\Thumb($file['tmp_name']);
              if($imagedata[0] > $imagedata[1] ) {
                  $thumb->cropFromCenter($imagedata[1], $imagedata[1]);
              }
              if($imagedata[0] < $imagedata[1] ) {
                  $thumb->cropFromCenter($imagedata[0], $imagedata[0]);
              }
              $image->content = $thumb->getImageAsString();
 
            }
  
            $thumb->resize(256, 256);
            $image->thumb = $thumb->getImageAsString();

            $image->save();
            $this->_item->product->images[] = $image->image_id;
            $this->_item->save();
            $sender->clean();

            $this->updateImages();
        }
    }

    public function imglistOnRow($row) {
        $image = $row->getDataItem();
        $row->add(new \Zippy\html\Image("imgitem"))->setUrl('/loadshopimage.php?id=' . $image->image_id . "&t=t");
        $row->add(new ClickLink("icover", $this, "icoverOnClick"))->setVisible($image->image_id != $this->_item->image_id);
        $row->add(new ClickLink("idel", $this, "idelOnClick"));
    }

    public function icoverOnClick($sender) {
        $image = $sender->getOwner()->getDataItem();
        $this->_item->image_id = $image->image_id;
        $this->_item->save();
        $this->listpanel->plist->Reload();
        $this->updateImages();
    }

    public function idelOnClick($sender) {
        $image = $sender->getOwner()->getDataItem();
        $this->_item->product->images = array_diff($this->_item->product->images, array($image->image_id));
        $this->_item->save();
        $this->updateImages();
    }

    public function updateImages() {
        $this->imglist = array();

        foreach ($this->_item->getImages() as $id) {
            $this->imglist[] = \App\Entity\Image::load($id);
        }
        $this->editimagepanel->imagelist->Reload();
    }

}

class ProductDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();

        $where = "1=1 ";
     
        if ($this->page->group instanceof Category) {
           

            $where .= " and  cat_id =  " . $this->page->group->cat_id;
        }
           /*
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
        */

        return $where;
    }

    public function getItemCount() {
        return Product::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

       $order = "itemname ";
       /*  $o = $this->page->listpanel->sortform->sorting->getValue();
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
        */
        return Product::find($this->getWhere(), $order, $count, $start);
    }

    public function getItem($id) {

    }

}

//компонент атрибута  товара
//выводит  элементы  формы  ввода   в  зависимости  от  типа  атрибута
class AttributeComponent extends \Zippy\Html\CustomComponent implements \Zippy\Interfaces\SubmitDataRequest
{

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
            $sel = '';
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
                if (in_array($value, $values)) {
                    $checked = ' checked="on"';
                } else {
                    $checked = "";
                }

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
