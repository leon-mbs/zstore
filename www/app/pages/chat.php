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
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Label;
use App\Application as App;

class Chat extends \App\Pages\Base
{
    public $user = null;

    private $users;
    private $offset   = 0;
    private $pagesize = 0;


    public function __construct() {
        parent::__construct();
        $this->user = System::getUser();
        if ($this->user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }
        $this->users = \App\Entity\User::findArray("username", "disabled <>1");

        $this->add(new Form('msgform'))->onSubmit($this, 'OnSend');
        $this->msgform->add(new TextArea('msgtext'));
        $this->msgform->add(new DropDownChoice('msgpersonal', \App\Entity\User::findArray("username", "disabled<>1 and user_id<>" . $this->user->user_id, "username"), 0));
        $this->msgform->add(new \Zippy\Html\Form\File('msgfile'));
        $this->msgform->add(new TextInput('msglink'));
        $this->msgform->add(new \Zippy\Html\Form\AutocompleteTextInput('msgdoc'))->onText($this, 'OnAutoDoc');


        $this->add(new Form('searchform'))->onSubmit($this, 'OnSearch');
        $this->searchform->add(new TextInput('searchtext'));
        $this->searchform->add(new ClickLink('searchclear', $this, "onClear"));
        $this->add(new ClickLink('prevpart', $this, "onPrev"));
        $this->add(new ClickLink('nextpart', $this, "onNext"));


        $this->pagesize = round(H::getPG() / 2);

        $this->Reload();
        $this->goAnkor('endchat');
    }

    public function Reload() {
        $this->prevpart->setVisible(false);
        $this->nextpart->setVisible(false);

        $conn = \ZCL\DB\DB::getConnect();


        $st = $this->searchform->searchtext->getText();

        $this->_tvars['chat'] = array();

        $w = "user_id=" . Notify::CHAT;

        if (strlen($st) > 0) {
            $w .= " and  message like " . Notify::qstr("%{$st}%");
        }

        $stat = $conn->GetRow("select count(notify_id) as cnt,max(notify_id) as idmax,min(notify_id) as idmin from notifies where " . $w);


        $nlist = Notify::find($w, "notify_id desc", $this->pagesize, $this->offset);
        $nlist = array_reverse($nlist);
        $minid = PHP_INT_MAX;
        $maxid = 0;

        foreach ($nlist as $n) {
            $item = array();
            $item['isme'] = $n->sender_id == $this->user->user_id;
            $item['message'] = nl2br($n->message);
            $item['sender'] = $this->users[$n->sender_id];

            $item['msgdate'] = date('Y-m-d H:i', $n->dateshow);
            if (date('Y-m-d', $n->dateshow) == date('Y-m-d')) {
                $item['msgdate'] = date('H:i', $n->dateshow);
            }

            $this->_tvars['chat'][] = $item;
            if ($minid > $n->notify_id) {
                $minid = $n->notify_id;
            }
            if ($maxid < $n->notify_id) {
                $maxid = $n->notify_id;
            }
        }

        if ($minid > $stat['idmin']) {
            $this->prevpart->setVisible(true);
        }
        if ($maxid < $stat['idmax']) {
            $this->nextpart->setVisible(true);
        }

        $this->goAnkor('endchat');

        setcookie("last_chat_id", $stat['idmax']);
    }


    public function OnSearch($sender) {
        $this->offset = 0;
        $this->Reload();
    }

    public function OnPrev($sender) {
        $this->offset += $this->pagesize;
        $this->Reload();
    }

    public function OnNext($sender) {
        $this->offset -= $this->pagesize;
        if ($this->offset < 0) {
            $this->offset = 0;
        }
        $this->Reload();
    }

    public function onClear($sender) {
        $this->searchform->searchtext->setText('');
        $this->offset = 0;
        $this->Reload();
    }

    public function OnAutoDoc($sender) {
        $text = $sender->getText();
        $text = \App\Entity\Doc\Document::qstr('%' . $text . '%');

        return \App\Entity\Doc\Document::findArray('document_number', "document_number like " . $text);
    }

    public function OnSend($sender) {
        $this->searchform->searchtext->setText('');


        $n = new  Notify();
        $n->user_id = Notify::CHAT;
        $n->message = $sender->msgtext->getText();
        $n->sender_id = System::getUser()->user_id;

        $link = $sender->msglink->getText();
        if (strlen($link) > 0) {
            $n->message .= "<br><a href=\"{$link}\" > <i class=\"fa fa-link\" ></i>" . $link . "</a>";
        }
        $doc_id = $sender->msgdoc->getKey();

        if ($doc_id > 0) {
            $doc = \App\Entity\Doc\Document::load($doc_id);
            if ($doc != null) {
                $n->message .= "<br><a href=\"/index.php?p=App/Pages/Register/DocList&arg={$doc_id}\" ><i class=\"fa fa-file\" ></i> " . $doc->document_number . "</a>";
            }
        }
        $file = $sender->msgfile->getFile();
        if (strlen($file["tmp_name"]) > 0) {
            if ($file['size'] > 1024*1024) {
                $this->setError("Файл більше 1 МБ!");
                return;
            }
            $id = H::addFile($file, 0, '', \App\Entity\Message::TYPE_CHAT);

            $url = _BASEURL . 'loadfile.php?id=' . $id;

            $imagedata = getimagesize($file["tmp_name"]);

            if (is_array($imagedata) > 0) {
                $url = $url . '&im=1';
            }

            $n->message .= "<br><a target=\"_blank\" href=\"{$url}\" ><i class=\"fa fa-paperclip\" ></i> " . $file['name'] . "</a>";
        }
        $up = $sender->msgpersonal->getValue();
        if ($up > 0) {
            $n->user_id = $up;  //личное
            $this->setSuccess("Відправлено");
        }
        $n->save();
        $sender->clean();
        $this->offset = 0;

        $this->Reload();

        $this->goAnkor('endchat');
    }


}
