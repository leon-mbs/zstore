<?php

namespace App\Pages;

use App\Entity\Notify;
use App\Helper as H;
use App\System;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\WebApplication as App;

class SystemLog extends \App\Pages\Base
{

    public $user = null;
    public $ds;


    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new TextInput('searchtext'));

        $this->ds = new EntityDataSource("\\App\\Entity\\Notify", "dateshow <= now() and user_id=" . Notify::SYSTEM, " dateshow desc");

        $this->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->nlist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));
        $this->nlist->Reload();

        \App\Entity\Notify::markRead($user->user_id);


    }

    public function OnRow($row) {
        $notify = $row->getDataItem();


        $row->add(new Label("msg"))->setText($notify->message, true);
        $row->add(new Label("ndate", \App\Helper::fdt($notify->dateshow)));
        $row->add(new Label("newn"))->setVisible($notify->checked == 0);
    }

    public function filterOnSubmit($sender) {
        $where = 'user_id=' . System::getUser()->user_id;

        $text = trim($sender->searchtext->getText());
        if (strlen($text) > 0) {
            $text = Notify::qstr('%' . $text . '%');
            $where = "   message like {$text}  and user_id=" . Notify::SYSTEM;
        }

        $this->ds->setWhere($where);
        $this->nlist->Reload();
    }


}
