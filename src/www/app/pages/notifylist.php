<?php

namespace App\Pages;

use Zippy\Html\DataList\DataView;
use App\Entity\User;
use App\System;
use Zippy\WebApplication as App;
use \ZCL\DB\EntityDataSource;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class NotifyList extends \App\Pages\Base
{

    public $user = null;

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }




        $this->add(new DataView("nlist", new EntityDataSource("\\App\\Entity\\Notify", "dateshow <= now() and user_id=" . $user->user_id, " dateshow desc"), $this, 'OnRow'));
        $this->nlist->setPageSize(25);
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));
        $this->nlist->Reload();

        \App\Entity\Notify::markRead($user->user_id);
    }

    public function OnRow($row) {
        $notify = $row->getDataItem();

        $row->add(new Label("msg"))->setText($notify->message, true);
        $row->add(new Label("ndate", date("Y-m-d H:i", $notify->dateshow)));
        $row->add(new Label("newn"))->setVisible($notify->checked == 0);
    }

}
