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
use Zippy\Html\Link\ClickLink;
use App\Application as App;

class NotifyList extends \App\Pages\Base
{
    public $user = null;
    public $ds;
    private $users;

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }
        $this->users = \App\Entity\User::findArray("username", "disabled <>1");

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new TextInput('searchtext'));
       
        $this->add(new Form('msgform'))->onSubmit($this, 'msgOnSubmit');
        $this->msgform->add(new DropDownChoice('msguser',\App\Entity\User::findArray('username','disabled<>1 and user_id<>' . $user->user_id,'username'),0));
        $this->msgform->add(new TextArea('msgtext'));

        $this->ds = new EntityDataSource("\\App\\Entity\\Notify", "dateshow <= now() and user_id=" . $user->user_id, " dateshow desc");

        $this->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->nlist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));
        $this->nlist->Reload();

        \App\Entity\Notify::markRead($user->user_id);


    }

    public function OnRow($row) {
        $notify = $row->getDataItem();

        $row->add(new Label("sender"));
        $row->add(new Label("sendericon"));
        $sender_name = $this->users[$notify->sender_id]??'';
        if ($notify->sender_id > 0) {
            $row->sender->setText($sender_name);
            $row->sendericon->setAttribute('class', 'fa fa-user');

        }
        if ($notify->sender_id == Notify::SYSTEM) {
            $row->sender->setText("Системне повідомлення");
            $row->sendericon->setAttribute('class', 'fa fa-cog');
        }
        if ($notify->sender_id == Notify::EVENT) {
            $row->sender->setText("Нагадування про подію");
            $row->sendericon->setAttribute('class', 'fa fa-calendar');
        }
        if ($notify->sender_id == Notify::SUBSCRIBE) {
            $row->sender->setText("Розсилка");
            $row->sendericon->setAttribute('class', 'fa fa-envelope');
        }
        if ($notify->sender_id == Notify::CRONTAB) {
            $row->sender->setText("Планувальник");
            $row->sendericon->setAttribute('class', 'fa fa-clock');
        }


        $row->add(new Label("msg"))->setText($notify->message, true);
        $row->add(new Label("ndate", \App\Helper::fdt($notify->dateshow)));
        $row->add(new Label("newn"))->setVisible($notify->checked == 0);
        $row->add(new ClickLink("nanswer",$this,'onAnswer'))->setVisible($notify->sender_id > 0);
     

    }

    public function filterOnSubmit($sender) {
        $where = 'user_id=' . System::getUser()->user_id;

        $text = trim($sender->searchtext->getText());
        if (strlen($text) > 0) {
            $text = Notify::qstr('%' . $text . '%');
            $where = "(  message like {$text}) and user_id=" . System::getUser()->user_id;
        }

        $this->ds->setWhere($where);
        $this->nlist->Reload();
    }

    public function onAnswer($sender) {
        $msg= $sender->getOwner()->getDataItem();   
        
        $this->msgform->msguser->setValue($msg->sender_id);  
        $this->addJavaScript(" $(\"#msgtext\").focus() ",true)  ;
    }
    public function msgOnSubmit($sender) {
        
        $n = new \App\Entity\Notify();
        $n->user_id = $sender->msguser->getValue();;
        $n->message = trim($sender->msgtext->getText() ) ;
        $n->sender_id = System::getUser()->user_id;
        
        if($n->user_id==0) {
            $this->setError('Не вказано отримувача')  ;
            return;
        }
        if(strlen($n->message)==0) {
            $this->setError('Не введено текст')  ;
            return;
        }
        
        
        $n->save();  
        $this->setSUccess('Відправлено')  ;
        $sender->msgtext->setText('')       ;     
        $sender->msguser->setValue(0)       ;     
    }

}
