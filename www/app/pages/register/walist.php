<?php

namespace App\Pages\Register;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\Paginator;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \App\Entity\Doc\Document;
use \App\Helper as H;
use \App\Application as App;
use \App\System;

/**
 * Документы  на  утверждение
 */
class WAList extends \App\Pages\Base {

    private $_doc = null;

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('WAList'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        

        $conn = \Zdb\DB::getConnect() ;
        $res = $conn->Execute("select distinct  meta_name,meta_desc  from documents_view where  state=". Document::STATE_WA  .' order  by  meta_desc ') ;
        $tlist = array();
        foreach($res as $row){
           $tlist['meta_name'] = $row['meta_desc'];
        }
        
        $this->filter->add(new DropDownChoice('doctype', $tlist, 0));
        
        $doclist = $this->add(new DataView('doclist', new WADataSource($this), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());


        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        $this->add(new Form('statusform'));

        $this->statusform->add(new SubmitButton('bap'))->onClick($this, 'statusOnSubmit');
        $this->statusform->add(new SubmitButton('bref'))->onClick($this, 'statusOnSubmit');
        $this->statusform->add(new TextInput('comment')) ;
        $this->statusform->setVisible(false);
   
        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function filterOnSubmit($sender) {


        $this->docview->setVisible(false);
        $this->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('desc', $doc->meta_desc));

        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa($doc->amount)));
            

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
    }

    public function statusOnSubmit($sender) {

        $state = $this->_doc->state;
 

        if ($sender->id == "bap") {
            $this->_doc->updateStatus(Document::STATE_APPROVED);
            
        }
        if ($sender->id == "bref") {
            $this->_doc->updateStatus(Document::STATE_REFUSED);
            $text = $sender->comment->getText();
         
            $user = System::getUser();

            $n = new \App\Entity\Notify();
            $n->user_id = $this->_doc->user_id;
            $n->message = "Документ {$this->_doc->meta_desc} {$this->_doc->document_number} отклонен ";
            $n->message .= "<br>" .$text;
            $n->sender_name = $user->username;
            $n->save();           
            
            $sender->comment->setText('');
        }
  
        $this->doclist->Reload(false);
        $this->statusform->setVisible(false);
        $this->docview->setVisible(false);
    }

    public function updateStatusButtons() {

     
        $state = $this->_doc->state;
   
        if ($state == Document::STATE_WA) {
            $this->statusform->bref->setVisible(true);
            $this->statusform->bap->setVisible(true);
            $this->statusform->comment->setVisible(true);
        }  else {
            $this->statusform->bref->setVisible(false);
            $this->statusform->bap->setVisible(false);
            $this->statusform->comment->setVisible(false);
        }
          
    }

    
    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true))
            return;
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(false);
        $this->docview->setVisible(true);
        $this->docview->setDoc($this->_doc);
        
        $this->statusform->setVisible(true);
        $this->updateStatusButtons();
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true))
            return;


        App::Redirect("\\App\\Pages\\Doc\\" . $doc->meta_name, $doc->document_id);
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $d) {
            $csv .= date('Y.m.d', $d->document_date) . ';';
            $csv .= $d->document_number . ';';
            $csv .= $d->headerdata["pareaname"] . ';';
            $csv .= $d->amount . ';';
            $csv .= str_replace(';', '', $d->notes) . ';';
            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=baylist.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class WADataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();
        
        $where  = "   state= " . Document::STATE_WA   ;
 

        $doctype = $this->page->filter->doctype->getValue();
        if ($doctype > 0) {
            $where .= " and meta_name = '{doctype}'  ";
        }
        $user = System::getUser();
        if ($user->acltype == 2) {
 
            $where .= " and meta_id in({$user->aclexe}) ";
        }
   
        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "document_date desc,document_id desc", $count, $start);

        //$l = Traversable::from($docs);
        //$l = $l->where(function ($doc) {return $doc->document_id == 169; }) ;
        //$l = $l->select(function ($doc) { return $doc; })->asArray() ;
        return $docs;
    }

    public function getItem($id) {
        
    }

}
