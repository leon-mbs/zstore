<?php

namespace App\Widgets;

use App\Entity\Doc\Document;
use App\Helper;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для  просмотра недавно  открытых  документов
 */
class WOpenDocs extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wopendoc') !== false || System::getUser()->rolename == 'admins');

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();

        // список  открытых документов
        $where = " state  < " . Document::STATE_EXECUTED;

        if ($visible) {
            $data = Document::find($where, "document_date desc");
        }

        $doclist = $this->add(new DataView('odoclist', new ArrayDataSource($data), $this, 'doclistOnRow'));
        $doclist->setPageSize(Helper::getPG());
        $this->add(new \Zippy\Html\DataList\Paginator("odpag", $doclist));
        $doclist->Reload();

        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function doclistOnRow($row) {
        $item = $row->getDataItem();
        $item = $item->cast();

        $row->add(new \Zippy\Html\Link\RedirectLink("wod_number", "\\App\\Pages\\Register\\DocList", $item->document_id))->setValue($item->document_number);

        $row->add(new Label('wod_date', \App\Helper::fdt($item->document_date)));
        $row->add(new Label('wod_type', $item->meta_desc));
        $row->add(new Label('wod_amount', Helper::fa($item->amount)));
    }

}
