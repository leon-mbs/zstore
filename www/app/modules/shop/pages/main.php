<?php

namespace App\Modules\Shop\Pages;

use App\Modules\Shop\Entity\ProductGroup;
use App\Modules\Shop\Helper;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Panel;

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
        $this->subcatlistp->add(new DataView("subcatlist", new EntityDataSource("\\App\\Modules\\Shop\\Entity\\ProductGroup", "parent_id=" . $id), $this, 'OnCatRow'));
        if ($id > 0) {
            $this->subcatlistp->subcatlist->Reload();
        }

        $this->add(new Panel("newlistp"));
        $this->newlistp->add(new DataView("newlist", new EntityDataSource("\\App\\Modules\\Shop\\Entity\\Product", "", "product_id desc", 12), $this, 'OnNewRow'))->Reload();
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

}
