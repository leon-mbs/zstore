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
 * Виджет для  просмотра недавно использованных документов
 */
class WRDoc extends \Zippy\Html\PageFragment {

    public function __construct($id) {
        parent::__construct($id);

        $user = System::getUser();

        $visible = (strpos($user->widgets, 'wrdoc') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();

        if ($visible) {
            $sql = "select distinct document_id,meta_desc,document_number from docstatelog_view where user_id={$user->user_id} and createdon > " . $conn->DBDate(strtotime("-1 month", time())) . "   order by log_id";


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




        $row->add(new Label('type', $item->meta_desc));
        $row->add(new \Zippy\Html\Link\RedirectLink("number", "\\App\\Pages\\Register\\DocList", $item->document_id))->setValue($item->document_number);
    }

}
