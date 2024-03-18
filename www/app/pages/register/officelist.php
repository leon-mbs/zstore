<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * журнал  офисных документов
 */
class OfficeList extends \App\Pages\Base
{
    private $_doc = null;
    public $_tag = '' ; 
    
    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('SalaryList')) {
            \App\Application::RedirectHome() ;
        }
        
        $this->add(new \Zippy\Html\Link\LinkList("taglist"))->onClick($this, 'OnTagList');        
         
        
        $conn = \ZDB\DB::getConnect();
        $names = $conn->GetCol("select distinct notes from documents_view where  meta_name='OfficeDoc' order  by notes");

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->filter->add(new CheckBox('archive'));
        $this->filter->add(new TextInput('searchcontext'));
        $this->filter->add(new Date('from'));
        $this->filter->add(new Date('to'));
        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtype'));
        $this->filter->searchtype->setDataList($names);
        $this->filter->add(new ClickLink('erase', $this, "onErase"));

        $doclist = $this->add(new DataView('doclist', new OfficeListDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->Reload();

    }

    public function filterOnSubmit($sender) {


        $this->statuspan->setVisible(false);

        $this->Reload();
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', H::fd($doc->document_date)));
        
        $notes= ($doc->headerdata['customer_name'] ??'').' '.($doc->headerdata['employee_name'] ??'');

        $row->add(new Label('notes', trim( $notes)));
        $stname =Document::getStateName($doc->state) ;
        if($doc->state==Document::STATE_EXECUTED ) {
            $stname = "Виконаний";    
        }
        $row->add(new Label('state', $stname));
        $row->add(new Label('user',$doc->username ));

        $row->add(new ClickLink('title'))->onClick($this, 'showOnClick');
        $row->title->setValue($doc->notes) ;
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('copy'))->onClick($this, 'copyOnClick');

        if ($doc->state != Document::STATE_CLOSED) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
        if ($doc->document_id == @$this->_doc->document_id) {
            $row->setAttribute('class', 'table-success');
        }

    }


    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);

        $this->Reload(false);

        $this->goAnkor('dankor');
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }
        $class = "\\App\\Pages\\Doc\\OfficeDoc";

        App::Redirect($class, $doc->document_id);
    }

    public function copyOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $class = "\\App\\Pages\\Doc\\OfficeDoc";

        App::Redirect($class,0, $doc->document_id);
    }

    public function onErase($sender) {
       $this->_tag ='';
       $form=$sender->getOwner();
       $form->clean() ;
       $this->filterOnSubmit($form);

        
    }
    
    
    public function Reload($flag=true) {
          $this->doclist->Reload($flag);
         
       
          $this->taglist->Clear();
          $tags = \App\Entity\Tag::getTags(\App\Entity\Tag::TYPE_OFFICEDCO ) ;
          foreach ($tags as $tag) {
             $this->taglist->addClickLink($tag, '#'.$tag);
          }           
          
           
    }    
    
     public function OnTagList($sender) {
        $this->_tag  = $sender->getSelectedValue();

    
        $this->Reload() ;
         
    }  
}

/**
 *  Источник  данных  для   списка  документов
 */
class OfficeListDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = "   meta_name = 'OfficeDoc'   ";

        $filterform = $this->page->filter;

        $sn = trim($filterform->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " meta_name = 'OfficeDoc'   and document_number like  {$sn} ";
        }
        if(strlen($this->page->_tag)>0) {
                
               $tag   = Document::qstr($this->page->_tag) ;
               $where = " document_id in (select item_id from taglist where  tag_type=2 and tag_name={$tag} )"; 
        }
        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "document_date desc,document_id desc", $count, $start);

        return $docs;
    }

    public function getItem($id) {

    }

}
