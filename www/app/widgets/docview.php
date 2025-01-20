<?php

namespace App\Widgets;

use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;

/**
 * Виджет для  просмотра  документов
 */
class DocView extends \Zippy\Html\PageFragment
{
    private $_doc;
    private $_p         = null;
    public $_reldocs   = array();
    public $_statelist = array();
    public $_fileslist = array();
    public $_msglist   = array();
    public $_paylist   = array();
    public $_itemlist  = array();

    public function __construct($id) {
        parent::__construct($id);

        $this->add(new Label('_docid_')) ;
        $this->add(new Label('_path_')) ;


    }

    // Устанавливаем  документ  для  просмотра
    public function setDoc(\App\Entity\Doc\Document $doc) {
        $this->_docid_->setAttribute('docid', $doc->document_id);

        $this->setvisible(true);
        $path = $this->id;
        $owner =  $this->getOwner() ;
        while(($owner    instanceof \Zippy\Html\WebPage)==false) {
            $path = $owner->id.'::'.$path ;
            $owner =  $owner->getOwner() ;
        }
        $this->_path_->setAttribute('path', $path);


    }

    public function loaddata($arg, $post) {
        $docid =  $arg[0] ;

        $user = \App\System::getUser() ;
        $common = \App\System::getOptions('common') ;

        $doc = Document::load($docid);
        if($doc== null){
           return json_encode([], JSON_UNESCAPED_UNICODE);
        
        }
        $doc = $doc->cast();

        $exportlist = $doc->supportedExport();

        $html = $doc->generateReport();

        $htmlpos ='' ;

        if(in_array(Document::EX_POS, $exportlist))  {
            $htmlpos = $doc->generatePosReport();
        }
        
        $ret = array();
        $ret['usemobileprinter']=   $user->usemobileprinter ==1  ;
        $ret['showpartion']=   $user->noshowpartion  ==0  ;
        //   $ret['user_id']=   $user->user_id   ;
        //  $ret['userlogin']=  $user->userlogin   ;
        //  $ret['userrole']=   $user->rolename   ;
        //  $ret['isadmin']=   $user->rolename  =='admins' ;

        $ret['exports']=  array() ;

 
        $ret['exports']['word']  =  in_array(Document::EX_WORD, $exportlist) ;
        $ret['exports']['excel'] =  in_array(Document::EX_EXCEL, $exportlist) ;
        $ret['exports']['pdf']   =  in_array(Document::EX_PDF, $exportlist) ;
        $ret['exports']['pos']   =  in_array(Document::EX_POS, $exportlist) ;
        $ret['exports']['email'] =  in_array(Document::EX_MAIL, $exportlist) ;
        $ret['qr']['img'] = '';
        $ret['qr']['url'] = '';


        $qr = $doc->getQRPay() ;
        $ret['exports']['qrpay'] =  is_array($qr);

        if(is_array($qr)) {
            $ret['qr']['img'] = $qr['qr'] ;

            $ret['qr']['url'] = $qr['url'] ;
        }

        $ret['html'] = $html   ;
        $ret['htmlpos'] = $htmlpos   ;

        $ret['isscan'] = false   ;

        if (is_numeric($doc->headerdata['scan'] ?? null)) {
            $ret['scanimage'] = '/loadfile.php?im=1&id=' . $doc->headerdata['scan']   ;
            $ret['isscan'] = true  ;
        }

        //статусы
        $ret['loglist'] = array();
        foreach($doc->getLogList() as $st) {
            $ret['loglist'][]= array('statedate'=> \App\Helper::fdt($st->createdon),'stateuser'=>$st->username,'statename'=>Document::getStateName($st->docstate)) ;
        }

        //оплаты
        $ret['paylist'] = array();
        foreach(\App\Entity\Pay::getPayments($doc->document_id) as $p) {
            $ret['paylist'][]= array('paydate'=> \App\Helper::fd($p->paydate),
                                    'paymf'=>$p->mf_name,
                                    'payamountp'=>H::fa($p->amount > 0 ? $p->amount : "") ,
                                    'payamountm'=>H::fa($p->amount < 0 ? 0 - $p->amount : "")
                                    ) ;
        }

        //склад
        $ret['entrylist'] = array();
        $sql = " select e.entry_id, s.stock_id, s.partion,i.itemname,i.item_code,e.quantity,e.outprice  
        from entrylist e 
        join store_stock  s on e.stock_id = s.stock_id
        join items i on s.item_id = i.item_id 
        where  coalesce(e.quantity,0) <> 0  and document_id=" . $docid . " order  by e.entry_id";

        foreach(\App\Entity\Entry::findBySql($sql) as $entry) {
            $ret['entrylist'][]= array(
                'itname'=> $entry->itemname,
                'itcode'=>$entry->item_code,
                'itqty'=>H::fqty($entry->quantity),
                'itpartion'=>H::fa($entry->partion),
                'itprice'=>H::fa($entry->outprice),
                'itamount'=>H::fa($entry->outprice * $entry->quantity)
              ) ;
        }


        $ret['pdoc_id'] = 0;
        $ret['pdoc_name'] = '';
        if($doc->parent_id >0){
            $p = Document::load($doc->parent_id);
            if($p instanceof Document) {
                if ( \App\ACL::checkShowDoc($p)) {
                   $ret['pdoc_id'] = $doc->parent_id;
                   $ret['pdoc_name'] = $p->meta_desc . ' ' . $p->document_number. ' ' . $p->getStateName($p->state);
                }
            }
        }
        $ret['reldocs'] = array();

        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }

    public function loadchilddocs($arg, $post) {
        $user = \App\System::getUser() ;

        $docid =  $arg[0] ;

        $doc = Document::load($docid);


        $docs = array();
        foreach($doc->getChildren() as $d) {
            
            if(\App\ACL::checkShowDoc($d) ==false) return;
            
            $docs[]=array('id'=>$d->document_id,
                          'name'=>$d->meta_desc . ' ' . $d->document_number,
                          'status'=>$d->getStateName($d->state),
                          'candel'=>($user->user_id == $d->user_id || $user->rolename  =='admins')
                          );
        }


        return json_encode($docs, JSON_UNESCAPED_UNICODE);

    }

    public function delchilddoc($arg, $post) {

        $chid =  $arg[0] ;

        $conn = \ZDB\DB::getConnect();
        $conn->Execute("update documents set parent_id=0 where  document_id=" . $chid);

    }

    public function addchilddoc($arg, $post) {


        $child = Document::load($arg[0]);
        if ($child instanceof Document) {
            $child->parent_id = $arg[1];
            $child->save();
        }

    }

    public function getDocs($args, $post) {

        $q = $args[0];
        $q= \App\Entity\Doc\Document::qstr('%'.$q.'%') ;
        $data = array();
        foreach(\App\Entity\Doc\Document::findArray('document_number', "parent_id <> {$args[1]} and document_number like ".$q) as $id=>$v) {
            $data[]=array('value'=>$v,'key'=>$id);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE);

    }

    public function loadmessages($arg, $post) {
        $user = \App\System::getUser() ;

        $docid =  $arg[0] ;


        $msglist = array();
        foreach(\App\Entity\Message::getMessages(1, $docid) as $msg) {
            $msglist[]=array(
            'id'=>$msg->message_id,
            'msgdata'=>nl2br($msg->message),
            'msgdate'=>\App\Helper::fdt($msg->created)  ,
            'msguser'=>$msg->username ,
            'candel'=>($user->user_id == $msg->user_id || $user->rolename  =='admins')
            ) ;
        }


        return json_encode($msglist, JSON_UNESCAPED_UNICODE);

    }

    public function delmsg($arg, $post) {


        \App\Entity\Message::delete($arg[0]);

    }

    public function addmsg($arg, $post) {
        if(strlen($post['msgtext'])==0) {
            return;
        }
        $user = System::getUser();
        $doc = Document::load($arg[0]);

        $msg = new \App\Entity\Message();
        $msg->message = $post['msgtext'];
        $msg->created = time();
        $msg->user_id = $user->user_id;
        $msg->item_id = $arg[0];
        $msg->item_type = \App\Entity\Message::TYPE_DOC;

        $msg->save();

        $conn = \ZDB\DB::getConnect();
        $ids = $conn->GetCol("select distinct  user_id from  docstatelog where document_id = {$arg[0]}   ") ;

        
        foreach(\App\Entity\Message::find("item_id = {$arg[0]} and  item_type = ".\App\Entity\Message::TYPE_DOC) as $msg){
           
           if(!in_array($msg->user_id,$ids)) {
              $ids[]= $msg->user_id;
           }
        }
        
        
        foreach ($ids as $id) {

            if($user->user_id==$id) continue;
            
            $n = new \App\Entity\Notify();
            $n->user_id = $id;
            $n->message = "<b>Новий коментар до документа:</b> {$doc->meta_desc} {$doc->document_number}  ";
            $n->message .= "<br> {$msg->message} ";
            $n->message .= "<br>  <a href=\"/index.php?p=App/Pages/Register/DocList&arg={$doc->document_id}\">Відповісти</a> ";
            $n->sender_id = $user->user_id;
            $n->save();
        }


    }

    public function loadfiles($arg, $post) {
        $user = \App\System::getUser() ;

        $docid =  $arg[0] ;
        $doc = Document::load($docid);


        $filelist = array();
        foreach(H::getFileList($docid, \App\Entity\Message::TYPE_DOC) as $f) {


            $url = _BASEURL . 'loadfile.php?id=' . $f->file_id;
            if (strlen($f->mime) > 0) {
                $url = $url . '&im=1';
            }



            $filelist[]=array(
            'id'=>$f->file_id,
            'url'=>$url,
            'filename'=>$f->filename,
            'title'=>$f->description,

            'candel'=>($user->user_id == $doc->user_id || $user->rolename  =='admins')
            ) ;
        }


        return json_encode($filelist, JSON_UNESCAPED_UNICODE);

    }

    public function delfile($arg, $post) {

        H::deleteFile($arg[0]);
        $doc= Document::load($arg[1]) ;
        if($doc->headerdata["scan"]== $arg[0] )  {
             $doc->headerdata["scan"] = 0;
             $doc->save();
        }

    }

    public function addfile($arg, $post) {


        $file =  $_FILES['addfile']  ;

        if(strlen($file['tmp_name'] ??'')==0) {
            return;
        }

        H::addFile($file, $arg[0], $post['adddescfile'], \App\Entity\Message::TYPE_DOC);

    }

    public function printEP($arg, $post) {



        try {

            $doc = Document::Load($arg[0])->cast();
            $xml = $doc->generatePosReport(true);
            $pr = new \App\Printer() ;

            $buf = $pr->xml2comm($xml) ;

            $retb = json_encode($buf) ;
            $ret = json_encode(array("error"=>"","buf"=>$buf))  ;


            return $ret;


        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $ret = json_encode(array("error"=>$message))  ;


        }

    }

    /*
    public function onMail($arg, $post) {
        try {
            $doc = Document::Load($arg[0])->cast();

            $doc->sendEmail();

            return json_encode(array("ok"=>true)) ;


        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $ret = json_encode(array("error"=>$message))  ;


        }

    }
    */

}
