<?php

namespace App\Widgets;

use App\Entity\Category;
use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;

use Zippy\Html\DataList\Column;
use Zippy\Html\DataList\DataTable;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Image;
/**
 * Виджет для подбора  товаров
 */
class ItemSel extends \Zippy\Html\PageFragment
{

     private $_page;
    private $_event;
    private $_pricetype;
    private $_store = 0;
    public  $_list  = array();
    public  $_catlist  = array();
    public  $_prodlist = array();


    /**
     *
     *
     * @param mixed $id
     * @param mixed $page
     * @param mixed $event
     * @param mixed $pricetype
     */
    public function __construct($id, $page, $event) {
        parent::__construct($id);
        $this->_page = $page;
        $this->_event = $event;

        $this->add(new Panel('witempan'))->setVisible(false) ;
        
        $this->witempan->add(new Form('wisfilter'))->onSubmit($this, 'ReloadData');

        $this->witempan->wisfilter->add(new TextInput('wissearchkey'));
        $this->witempan->wisfilter->add(new DropDownChoice('wissearchcat', Category::getList(false,false), 0));
        $this->witempan->wisfilter->add(new TextInput('wissearchmanufacturer'));
        $this->witempan->wisfilter->wissearchmanufacturer->setDataList(Item::getManufacturers());

        $ds = new ArrayDataSource($this, '_list');

        $table = $this->witempan->add(new DataTable('witemselt', $ds, true, true));
        $table->setPageSize(H::getPG());
        $table->AddColumn(new Column('itemname', "Назва", true, true, true));
        $table->AddColumn(new Column('item_code', "Артикул", true, true, false));
        $table->AddColumn(new Column('bar_code', "Штрих-код", true, true, false));
        $table->AddColumn(new Column('manufacturer', "Бренд", true, true, false));

        $table->setCellClickEvent($this, 'OnSelect');
        
        
        
        $this->add(new Panel('wcatpan'))->setVisible(false);
        $this->wcatpan->add(new DataView('wcatlist', new ArrayDataSource($this, '_catlist'), $this, 'onCatRow'));

        $this->add(new Panel('wprodpan'))->setVisible(false);
        $this->wprodpan->add(new DataView('wprodlist', new ArrayDataSource($this, '_prodlist'), $this, 'onProdRow'));
         
    }

    /**
     * тип  цены для  столбца  Цена
     *
     * @param mixed $pricetype
     * @param mixed $store
     */
    public function setPriceType($pricetype, $store = 0) {
        $this->_pricetype = $pricetype;
        $this->_store = $store;
        if (strlen($this->_pricetype) > 0) {
            $this->witempan->witemselt->AddColumn(new Column('price', 'Цiна', true, true, false, "text-right", "text-right"));
        }
    }

    /**
     * Обновление данных
     *
     */
    public function Reload($cat = false) {
        
        if($cat==true){
            $this->witempan->setvisible(false);            
            $this->wcatpan->setvisible(true);
            $this->wprodpan->setvisible(true);
            
            $this->_catlist = Category::find(" coalesce(parent_id,0)=0  ");
            $this->wcatpan->wcatlist->Reload();
           
        }   else {
            $this->wcatpan->setvisible(false);
            $this->wprodpan->setvisible(false);
          $this->witempan->setvisible(true);
          $this->witempan->wisfilter->clean();
          $this->ReloadData($this->witempan->wisfilter);
        }
    }

    public function OnSelect($sender, $data) {
        $item = $data['dataitem'];
        $this->_page->{$this->_event}($item->item_id, $item->itemname);
    }

    public function ReloadData($sender) {

        $where = "disabled <> 1";
        $text = trim($this->witempan->wisfilter->wissearchkey->getText());
        $man = trim($this->witempan->wisfilter->wissearchmanufacturer->getText());
        $cat = $this->witempan->wisfilter->wissearchcat->getValue();

        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }

        if (strlen($text) > 0) {

            $text = Item::qstr('%' . $text . '%');
            $where = $where . " and (itemname like {$text} or item_code like {$text} or bar_code like {$text}   or description like {$text} )  ";
        }
        if (strlen($man) > 0) {

            $man = Item::qstr($man);
            $where = $where . " and  manufacturer like {$man}      ";
        }


        $list = Item::find($where);

        $this->_list = array();
        foreach ($list as $item) {

            if (strlen($this->_pricetype) > 0) {
                $item->price = $item->getPrice($this->_pricetype, $this->_store);
            }

            $this->_list[] = $item;
        }

        $this->witempan->witemselt->Reload();
    }
      //категории
    public function onCatRow($row) {
        $cat = $row->getDataItem();
        $row->add(new Panel('catbtn'))->onClick($this, 'onCatBtnClick');
        $row->catbtn->add(new Label('catname', $cat->cat_name));
        $row->catbtn->add(new Image('catimage', "/loadimage.php?id=" . $cat->image_id));
    }

    //товары
    public function onProdRow($row) {
        //  $store_id = $this->setupform->store->getValue();

        $prod = $row->getDataItem();
        $prod->price = $prod->getPrice($this->_pricetype);
        $row->add(new Panel('prodbtn'))->onClick($this, 'onProdBtnClick');
        $row->prodbtn->add(new Label('prodname', $prod->itemname));
        $row->prodbtn->add(new Label('prodprice', H::fa($prod->price)));
        $row->prodbtn->add(new Image('prodimage', "/loadimage.php?id=" . $prod->image_id));
    }

    //выбрана  группа
    public function onCatBtnClick($sender) {
        $cat = $sender->getOwner()->getDataItem();
        $catlist = Category::find("  detail  not  like '%<nofastfood>1</nofastfood>%' and   coalesce(parent_id,0)= " . $cat->cat_id);
        if (count($catlist) > 0) {
            $this->_catlist = $catlist;
            $this->wcatpan->wcatlist->Reload();
        } else {
            $this->_prodlist = Item::find('disabled<>1  and  item_type in (1,4 )  and cat_id=' . $cat->cat_id);
            $this->wcatpan->setVisible(false);
            $this->wprodpan->setVisible(true);
            $this->wprodpan->wprodlist->Reload();
        }

    }

    // выбран  товар
    public function onProdBtnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
      //  $this->docpanel->editdetail->edittovar->setKey($item->item_id);
      //  $this->docpanel->editdetail->edittovar->setText($item->itemname);
     //   $this->OnChangeItem($this->docpanel->editdetail->edittovar);

        $this->_page->{$this->_event}($item->item_id, $item->itemname);
 
        $this->_catlist = Category::find(" coalesce(parent_id,0)=0  ");
        $this->wcatpan->wcatlist->Reload();


        $this->wcatpan->setVisible(true);
        $this->wprodpan->setVisible(false);

    }


}
