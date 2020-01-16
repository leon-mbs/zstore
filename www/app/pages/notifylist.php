<?php

namespace App\Pages;

use \Zippy\Html\DataList\DataView;
use \App\Entity\User;
use \App\Entity\Notify;
use \App\System;
use \App\Helper  as H;
use \Zippy\WebApplication as App;
use \ZCL\DB\EntityDataSource;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;

class NotifyList extends \App\Pages\Base {

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
    
        $this->ds =  new EntityDataSource("\\App\\Entity\\Notify", "dateshow <= now() and user_id=" . $user->user_id, " dateshow desc");

        $this->add(new DataView("nlist",$this->ds , $this, 'OnRow'));
        $this->nlist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));
        $this->nlist->Reload();

        \App\Entity\Notify::markRead($user->user_id);
    }

    public function OnRow($row) {
        $notify = $row->getDataItem();

        $row->add(new Label("sender"))->setText($notify->sender_name  );
        $row->add(new Label("msg"))->setText($notify->message, true);
        $row->add(new Label("ndate", date("Y-m-d H:i", $notify->dateshow)));
        $row->add(new Label("newn"))->setVisible($notify->checked == 0);
    }
    public function filterOnSubmit($sender) {
        $text = trim($sender->searchtext->getText());
        if(strlen($text)==0)   return  ;
        $text = Notify::qstr('%'.$text.'%')  ;
        $this->ds->setWhere("(sender_name like {$text} or message like {$text}) and user_id=" . System::getUser()->user_id);
        $this->nlist->Reload();
    }
    
}
