<?php

namespace App\Widgets;

use App\Entity\Doc\Document;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для  просмотра запланированых документов
 */
class WPlannedDocs extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);


        $visible = (strpos(System::getUser()->widgets, 'wplanned') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();

        // список  запланированных документов
        $where = "state >= " . Document::STATE_EXECUTED;
        $where = $where . " and  date(document_date) > date(now()) ";
        //   $where = $where . " and  meta_name in ('ServiceAct','GoodsIssue','GoodsReceipt') ";

        if ($visible) {
            $data = Document::find($where, "document_date desc");
        }

        $doclist = $this->add(new DataView('pdoclist', new ArrayDataSource($data), $this, 'doclistOnRow'));
        $doclist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("plpag", $doclist));
        $doclist->Reload();

        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function doclistOnRow($row) {
        $item = $row->getDataItem();
        $item = $item->cast();
        $dt = date('d-m-Y', $item->document_date);
        $row->add(new \Zippy\Html\Link\RedirectLink("number", "\\App\\Pages\\Register\\DocList", $item->document_id))->setValue($item->document_number);

        $row->add(new Label('date', $dt));
        $row->add(new Label('type', $item->meta_desc));
    }

}
