<?php

namespace App\Widgets;

use App\Helper;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для  просмотра недавно использованных документов
 */
class WRDoc extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $user = System::getUser();

        $visible = (strpos($user->widgets, 'wrdoc') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();
        $cstr = \App\Acl::getBranchListConstraint();
        if (strlen($cstr) > 0) {
            $cstr = " d.branch_id in({$cstr}) and ";
        }


        if ($visible) {
            $sql = "select  distinct d.document_id,d.meta_desc,d.document_number,d.document_date,d.amount from docstatelog_view l join documents_view d  on l.document_id= d.document_id where  {$cstr} l.user_id={$user->user_id} and l.createdon > " . $conn->DBDate(strtotime("-1 month", time()));


            $rc = $conn->Execute($sql);

            foreach ($rc as $row) {
                $data[] = new \App\DataItem($row);
            }
        }

        $doclist = $this->add(new DataView('rdoclist', new ArrayDataSource($data), $this, 'doclistOnRow'));


        $doclist->Reload();

        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function doclistOnRow($row) {
        $item = $row->getDataItem();


        $row->add(new Label('date', date('Y.m.d', strtotime($item->document_date))));
        $row->add(new Label('type', $item->meta_desc));
        $row->add(new Label('amount', Helper::fa($item->amount)));
        $row->add(new \Zippy\Html\Link\RedirectLink("number", "\\App\\Pages\\Register\\DocList", $item->document_id))->setValue($item->document_number);
    }

}
