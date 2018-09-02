<?php

namespace App\Shop\Pages;

use Carbon\Carbon;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\BookmarkableLink;
use \App\Shop\Entity\ProductGroup;
use \App\Shop\Helper;
use \ZCL\DB\EntityDataSource;
use \Zippy\Html\Link\ClickLink;

class Main extends Base
{

    private $group_id = 0;

    public function __construct($id = 0) {
        parent::__construct();



        $this->group_id = $id;

        $toplist = ProductGroup::find("parent_id=0");

        $this->_tvars["leftmenu"] = array();
        foreach ($toplist as $g) {
            if ($g->gcnt > 0) {
                $this->_tvars["leftmenu"][] = array("link" => "/scat/{$g->group_id}", "name" => $g->groupname);
            } else {
                $this->_tvars["leftmenu"][] = array("link" => "/pcat/{$g->group_id}", "name" => $g->groupname);
            }
        }

        $this->add(new Label("breadcrumb", Helper::getBreadScrumbs($id), true));


        $this->add(new Panel("subcatlistp"));

        $this->subcatlistp->setVisible($id > 0);
        $this->subcatlistp->add(new DataView("subcatlist", new EntityDataSource("\\App\\Shop\\Entity\\ProductGroup", "parent_id=" . $id), $this, 'OnCatRow'));
        if ($id > 0)
            $this->subcatlistp->subcatlist->Reload();

        $this->add(new Panel("newlistp"));
        $this->newlistp->add(new DataView("newlist", new EntityDataSource("\\App\\Shop\\Entity\\Product", "", "product_id desc", 12), $this, 'OnNewRow'))->Reload();


        $this->add(new Panel('cartlistpanel'));
        $this->cartlistpanel->add(new DataView('cartlist', \App\Shop\Basket::getBasket(), $this, "onCartRow"));
        $this->add(new Panel('complistpanel'));
        $this->complistpanel->add(new DataView('complist', \App\Shop\CompareList::getCompareList(), $this, "onCompRow"));
        $this->cartlistpanel->cartlist->Reload();
        $this->complistpanel->complist->Reload();
    }

    public function OnCatRow($datarow) {
        $g = $datarow->getDataItem();
        $link = $g->gcnt > 0 ? "/scat/" . $g->group_id : "/pcat/" . $g->group_id;
        $datarow->add(new BookmarkableLink("scatimg", $link))->setValue("/loadimage.php?id=" . $g->image_id);
        $datarow->add(new BookmarkableLink("scatname", $link))->setValue($g->groupname);
    }

    public function OnNewRow($row) {
        $item = $row->getDataItem();
        $row->add(new BookmarkableLink("nimage", "/sp/" . $item->product_id))->setValue('/loadimage.php?id=' . $item->image_id . "&t=t");
        $row->add(new BookmarkableLink("nname", "/sp/" . $item->product_id))->setValue($item->productname);
    }

    public function onCartRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label("cartname", $item->productname));
        $row->add(new ClickLink("cartdel", $this, 'oncartdel'));
    }

    public function onCompRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label("compname", $item->productname));
        $row->add(new ClickLink("compdel", $this, 'oncompdel'));
    }

    public function oncartdel($sender) {
        $item = $sender->getOwner()->getDataItem();
        \App\Shop\Basket::getBasket()->deleteProduct($item->product_id);
    }

    public function oncompdel($sender) {
        $item = $sender->getOwner()->getDataItem();
        \App\Shop\CompareList::getCompareList()->deleteProduct($item->product_id);
    }

    protected function beforeRender() {
        parent::beforeRender();
        $this->cartlistpanel->cartlist->setDataSource(\App\Shop\Basket::getBasket());
        $this->cartlistpanel->cartlist->Reload();
        $this->cartlistpanel->setVisible(\App\Shop\Basket::getBasket()->isEmpty() == false);

        $this->complistpanel->complist->setDataSource(\App\Shop\CompareList::getCompareList());
        $this->complistpanel->complist->Reload();
        $this->complistpanel->setVisible(\App\Shop\CompareList::getCompareList()->isEmpty() == false);
    }

}
