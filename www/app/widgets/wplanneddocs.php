<?php

namespace App\Widgets;

use \Zippy\Binding\PropertyBinding as Bind;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Label;
use \App\Helper;
use \App\System;
use \App\Entity\Doc\Document;
use \Carbon\Carbon;

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
        $where = "state not in( " . Document::STATE_CANCELED . "," . Document::STATE_EDITED . "," . Document::STATE_NEW . ") ";
        $where = $where . " and  document_date >= " . $conn->DBDate(strtotime('-5 days'));
        $where = $where . " and  meta_name in ('ServiceAct','GoodsIssue','GoodsReceipt') ";
        $where = $where . " and  content like '%<planned>1</planned>%'";
        if ($visible) {
            $data = Document::find($where, "document_date desc");
        }

        $doclist = $this->add(new DataView('pdoclist', new ArrayDataSource($data), $this, 'doclistOnRow'));
        $doclist->Reload();

        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function doclistOnRow($row) {
        $item = $row->getDataItem();
        $item = $item->cast();
        $dt = date('d-m-Y', $item->document_date);
        $row->add(new Label('number', $item->document_number));
        $row->add(new Label('date', $dt));
        $row->add(new Label('type', $item->meta_desc));

        $date = new Carbon();
        $date->setTimestamp($item->document_date);
        $start = $date->startOfDay()->timestamp;
        $end = $date->endOfDay()->timestamp;

        if (time() < $start) {
            $row->number->setAttribute('class', 'badge  ');
        } else
        if (time() > $end) {
            $row->number->setAttribute('class', 'badge badge-danger');
        } else {
            $row->number->setAttribute('class', 'badge badge-warning');
        }
    }

}
