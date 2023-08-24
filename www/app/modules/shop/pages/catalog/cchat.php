<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Entity\Category;
use App\Entity\Item;

class CChat extends \Zippy\Html\WebPage
{
    private $_doc;
    private $_uri;

    public function __construct($doc_id=0, $hash="") {
        parent::__construct();

        if($hash=="" || $doc_id==0) {
            http_response_code(404) ;
            die;
        }
        $this->_uri = $_SERVER['REQUEST_URI'] ;

        $this->_doc = \App\Entity\Doc\Document::load($doc_id) ;
        if($this->_doc==null) {
            http_response_code(404) ;
            die;
        }
        if($this->_doc->headerdata['hash'] != $hash) {
            http_response_code(404) ;
            die;
        }

        $this->add(new \Zippy\Html\Form\Form('msgform'))->onSubmit($this, 'OnSend');
        $this->msgform->add(new \Zippy\Html\Form\TextArea('msgtext'));

        $this->updatelist() ;


    }

    public function OnSend($sender) {
        $message=trim($this->msgform->msgtext->getText());

        $msg = new \App\Entity\Message() ;
        $msg->message=$message;
        $msg->user_id=0;
        $msg->item_id=$this->_doc->document_id;
        $msg->item_type=\App\Entity\Message::TYPE_CUSTCHAT;
        $msg->save() ;

        $this->updatelist() ;
        $this->msgform->msgtext->setText('') ;
        \App\Application::$app->RedirectURI($this->_uri) ;
    }

    private function updatelist() {

        $this->_tvars['document_number'] = $this->_doc->document_number;
        $this->_tvars['document_date'] = \App\Helper::fd($this->_doc->document_date);
        $this->_tvars['msglist'] =[];

        $list = \App\Entity\Message::find("item_id={$this->_doc->document_id} and item_type=" .\App\Entity\Message::TYPE_CUSTCHAT, "message_id asc");

        foreach($list as $msg) {
            $m=[];
            $m['isseller']  = $msg->user_id >0;
            $m['message']  = $msg->message;
            $m['checked']  = $msg->checked==1;
            $m['msgdate'] = date('Y-m-d H:i', $msg->created);

            $this->_tvars['msglist'][] = $m;

            if($m['isseller']) {
                $msg->checked = 1;
                $msg->save();
            }

        }
    }
}
