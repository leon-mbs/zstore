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

class NotifyList extends \App\Pages\Base
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

        $this->ds = new EntityDataSource("\\App\\Entity\\Notify", "dateshow <= now() and user_id=" . $user->user_id, " dateshow desc");

        $this->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->nlist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));
        $this->nlist->Reload();

        \App\Entity\Notify::markRead($user->user_id);
        
        
        $this->add(new Form('msgform'))->onSubmit($this, 'OnSend');
        $this->msgform->add(new TextArea('msgtext'));
        $this->msgform->add(new DropDownChoice('users', \App\Entity\User::findArray('username', 'disabled <> 1 and user_id <>' . $user->user_id, 'username'), 0));
        $this->msgform->add(new CheckBox('sendall'))->setVisible($this->user->rolename == 'admins');
        
        
    }

    public function OnRow($row) {
        $notify = $row->getDataItem();

        $row->add(new Label("sender"))->setText($notify->sender_name);

        $row->add(new Label("msg"))->setText($notify->message, true);
        $row->add(new Label("ndate", \App\Helper::fdt($notify->dateshow)));
        $row->add(new Label("newn"))->setVisible($notify->checked == 0);
    }

    public function filterOnSubmit($sender) {
        $where='user_id=' . System::getUser()->user_id;
  
        $text = trim($sender->searchtext->getText());
        if (strlen($text)> 0) {
           $text = Notify::qstr('%' . $text . '%');
           $where=  "(sender_name like {$text} or message like {$text}) and user_id=" . System::getUser()->user_id ;
        }
        
        $this->ds->setWhere($where);
        $this->nlist->Reload();
    }
    
   public function OnSend($sender) {
        $msg = trim($sender->msgtext->getText());

        if (strlen($msg) == 0) {
            return;
        }


        $all = $sender->sendall->isChecked();

        $list = array();
        if ($all) {
            foreach ($sender->users->getOptionList() as $id => $n) {
                $list[] = $id;
            }
        } else {
            $id = $sender->users->getValue();
            if ($id == 0) {

                $this->setError('noselreciever');
                return;
            }
            $list[] = $id;
        }


        foreach ($list as $id) {
            $n = new \App\Entity\Notify();
            $n->user_id = $id;
            $n->message = $msg;
            $n->sender_name = $this->user->username;
            $n->save();
        }
        $this->setSuccess('sent');
        $sender->clean();
    }
    
    

}
