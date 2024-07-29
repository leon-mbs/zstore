<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Modules\Shop\Entity\Product;
use App\Modules\Shop\Helper;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Panel;
use Zippy\Html\DataList\ArrayDataSource;
use App\Entity\Category;

class Main extends Base
{
    private $cat_id = 0;
    public $_newlist = array();
    public $_catlist = array();

    public function __construct($id = 0) {
        parent::__construct();
        $id = intval($id);

        $this->cat_id = $id;

        $this->add(new Label("breadcrumb", Helper::getBreadScrumbs($id), true));

        $this->add(new Panel("subcatlistp"));



        $this->_catlist  = Category::find(" detail  not  like '%<noshop>1</noshop>%' and  coalesce(parent_id,0)=" . $id);

        usort($this->_catlist, function ($a, $b) {
            return $a->order > $b->order;
        });


        $this->subcatlistp->add(new DataView("subcatlist", new ArrayDataSource($this, '_catlist'), $this, 'OnCatRow'));

        $this->subcatlistp->subcatlist->Reload();

        $this->add(new Panel("newlistp"));
        $cat = '';
        if ($id > 0) {
            $c = \App\Entity\Category::load($id);
            $ch = $c->getChildren();
            $cat = " cat_id in (" . implode(',', $ch) . ") and ";
        }



        $ar = @unserialize(\App\Helper::getKeyVal('shop_newlist')) ;
        if(is_array($ar) && COUNT($ar) >0) {



            $ids = array() ;
            foreach($ar as $a) {
                $ids[] = $a->item_id;
            }

            $sql=   " cat_id >0 and item_id in (" . implode(',', $ids) . ") and  {$cat} disabled <> 1 and detail  not  like '%<noshop>1</noshop>%' " ;

            $newlist = Product::find($sql, '', 6);

            foreach($ar as $a) {  //выстраиваем  в порядке  добавления
                if($newlist[$a->item_id] instanceof Product) {
                    $this->_newlist[]=$newlist[$a->item_id] ;
                }

            }
            unset($newlist);
        }
        $this->newlistp->add(new DataView('newlist', new ArrayDataSource($this, "_newlist"), $this, 'OnNewRow'))->Reload();
        $this->newlistp->setVisible(count($this->_newlist)>0) ;
        //        $this->newlistp->add(new DataView("newlist", new EntityDataSource("\\App\\Modules\\Shop\\Entity\\Product", "  {$cat} disabled <> 1 and detail  not  like '%<noshop>1</noshop>%' ", "item_id desc", 6), $this, 'OnNewRow'))->Reload();
    }

    public function OnCatRow($datarow) {
        $g = $datarow->getDataItem();
        $link = $g->hasChild() > 0 ? "/scat/" . $g->cat_id : "/pcat/" . $g->cat_id;
        $datarow->add(new BookmarkableLink("scatimg", $link))->setValue("/loadshopimage.php?id=" . $g->image_id);
        $datarow->add(new BookmarkableLink("scatname", $link))->setValue($g->cat_name);
    }

    public function OnNewRow($row) {
        $item = $row->getDataItem();
        $row->add(new BookmarkableLink("nimage", $item->getSEF()))->setValue('/loadshopimage.php?id=' . $item->image_id . "&t=t");
        $row->add(new BookmarkableLink("nname", $item->getSEF()))->setValue($item->itemname);
    }

}
