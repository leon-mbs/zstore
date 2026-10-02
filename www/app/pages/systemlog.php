<?php

namespace App\Pages;

use App\Entity\Notify;
use App\Helper as H;
use App\System;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource ;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Application as App;
use App\Entity\Doc\Document;
 

class SystemLog extends \App\Pages\Base
{
    public $ds=[];
    public $sds=[];


    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }

        $this->add(new Label('fc'));
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new TextInput('searchtext'));

        $this->ds = new EntityDataSource("\\App\\Entity\\Notify", "  user_id=" .   Notify::SYSTEM, " dateshow desc");


        $this->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->nlist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));

        $flist=[];
        
        $files = scandir(_ROOT.'logs');
        foreach($files as $f){
           if(strpos($f,'.txt') > 0 )  {
               $di= new \App\DataItem()  ;
               $di->fname=$f;
               $flist[]=$di;
           }
        }
        $this->add(new DataView("flist", new ArrayDataSource($flist), $this, 'OnFRow'))->Reload();

        
        $this->add(new Form('sfilter'))->onSubmit($this, 'sfilterOnSubmit');
        $this->sfilter->add(new Date('from', time() - (15 * 24 * 3600)));
        $this->sfilter->add(new Date('to' ));
        $this->sfilter->add(new DropDownChoice('doctype', H::getDocTypes() ));
        $this->sfilter->add(new DropDownChoice('author', \App\Entity\User::findArray('username', 'disabled<>1', 'username') ));
        
        $this->sfilter->add(new DropDownChoice('status', Document::getStateList() ));
          
        $this->add(new DataView("slist", new  ArrayDataSource($this,'sds') , $this, 'OnSRow'));
        $this->slist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("spag", $this->slist));

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);
      

        $this->filterOnSubmit($this->filter)  ;
        \App\Entity\Notify::markRead(\App\Entity\Notify::SYSTEM);

    }



    
    public function filterOnSubmit($sender) {

        $st = trim($sender->searchtext->getText()) ;

        $where =   "  user_id=" . Notify::SYSTEM;

        if (strlen($st) > 0) {
            $text = Notify::qstr('%' . $st. '%');
            $where .= " and    message like {$text}   "  ;
        }



        $this->ds->setWhere($where);
        $this->nlist->Reload();
    }



    
    public function OnRow($row) {
        $notify = $row->getDataItem();

        $row->add(new Label("msg"))->setText($notify->message, true);

        $row->add(new Label("ndate", \App\Helper::fdt($notify->dateshow)));
        $row->add(new Label("newn"))->setVisible($notify->checked == 0);


    }
    public function OnFRow($row) {
       $f = $row->getDataItem();
       $row->add(new Label("fname",$f->fname)) ;
       $row->add(new ClickLink("fview",$this,'OnView')) ;
       $row->add(new ClickLink("fdown",$this,'OnFile')) ;
     
    }
    public function OnView($sender) {
        $f = $sender->getOwner()->getDataItem();
        $c= file_get_contents(_ROOT.'logs/'.$f->fname)  ;
        $this->fc->setText($c);
    }
    
    public function OnFile($sender) {
        $f = $sender->getOwner()->getDataItem();
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$f->fname.'"');
        header('Expires: 0');
       
       readfile(_ROOT.'logs/'.$f->fname)  ;
       die;
     
    }

    public function sfilterOnSubmit($sender) {
        $this->sds=[] ;
        
        $this->docview->setVisible(false);
        
        $from = $this->sfilter->from->getDate();
        $to = $this->sfilter->to->getDate();
        $doctype = $this->sfilter->doctype->getValue();
        $author = $this->sfilter->author->getValue();
        $status = $this->sfilter->status->getValue();
       
        $conn = \ZDB\DB::getConnect();
        $w=" createdon >= ".$conn->DBDate($from);
        if($to > 0) {
            $w .= " and   createdon <= ".$conn->DBDate($to);
        }
        if($status > 0) {
            $w .= " and docstate = {$status}  ";
        }
        if($author > 0) {
            $w .= " and user_id = {$author}  ";
        }
        if($doctype > 0) { //todo  после  обновления  Бд
            $w .= " and document_id  in (select document_id  from documents where  meta_id =  {$doctype} ) ";
        }
        $rc = $conn->Execute("select * from docstatelog_view where {$w}   order  by  log_id");
        
        foreach ($rc as $row) {
            $row['dt'] = strtotime($row['createdon']);
           
            $statename="";
            if($row['docstate'] >0) {
               $statename =  Document::getStateName($row['docstate']) ;
            }
            if($row['docstate'] == -1) {
               $statename = 'Оплата'  ;
            }           
            if($row['docstate'] == -2) {
               $statename = 'Склад'  ;
            }           
            $row['docstate'] = $statename ;
            $row['comm'] = 'Host: '. $row['hostname'] ;
           
            $this->sds[] = new \App\DataItem($row,$row['log_id']);
        }     
        $this->slist->Reload(); 
        
        $this->goAnkor('sfilter'); 
        
        
    }
  
    public function OnSRow($row) {
        $st = $row->getDataItem();

        $row->add(new Label("dt"))->setText( H::fdt($st->dt));
        $row->add(new Label("auser",$st->username)) ;    
        $row->add(new Label("state",$st->docstate)) ;    
        $row->add(new Label("comm",$st->comm)) ;    
        $row->add(new ClickLink('number',$this, 'showOnClick'))->setValue($st->document_number);
      

    }
    
  public function showOnClick($sender) {
        $it = $sender->getOwner()->getDataItem();
        $doc = Document::load($it->document_id);
    
      
        $doc = $doc->cast();
 
        if (false == \App\ACL::checkShowDoc($doc, true)) {
            return;
        }
        $ch = \App\ACL::checkExeDoc($doc, true, false) ;
     
        $this->docview->setVisible(true);
        $this->docview->setDoc($doc);
        $this->goAnkor('dankor'); 
     
    }
    
    
}
