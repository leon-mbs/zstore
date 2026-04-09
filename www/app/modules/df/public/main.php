<?php

namespace App\Modules\DF\Public;

 
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Pager;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Panel;
use Zippy\Html\DataList\ArrayDataSource;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\Application as App;

class Main extends Base
{
    private $_doc = null;

    public function __construct( ) {
        parent::__construct();
   
        $doclist=$this->add(new DataView('doclist', new DocDataSource($this ), $this, 'doclistOnRow')) ;
        $this->add(new Pager('pag', $doclist));
        $doclist->setPageSize(25);
        $this->doclist->setSorting('priority desc,document_id desc', '');
        $doclist->Reload();
        
        $this->add(new Panel('docpan'))->setVisible(false);
        
        $this->docpan->add(new Label('docview')) ;
        $this->docpan->add(new ClickLink('bcancel',$this,'onButton')) ;
        $this->docpan->add(new ClickLink('bedit',$this,'onButton')) ;
        $this->docpan->add(new ClickLink('bdel',$this,'onButton')) ;
        
    }

   public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();
        $doc = $doc->cast();
        $row->add(new ClickLink('name',$this, 'showOnClick'))->setValue($doc->meta_desc);
        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('amount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));
        $st = Document::getStateName($doc->state);
        if($doc->state == Document::STATE_INPROCESS && intval($doc->user_id) == 0)  {
           $st = "Очікує виконання";    
        }
        $row->add(new Label('state',$st));
        $row->add(new Label('notes', $doc->notes));
         
   } 

   public function onButton($sender) {
       if($sender->id=='bedit') {
           App::Redirect("\\App\\Modules\\DF\\Public\\Order", $this->_doc->document_id);
       }
       if($sender->id=='bcancel') {
           $this->_doc->setHD('delayinprocess',1);  
           $this->_doc->save();
           $this->_doc->updateStatus( Document::STATE_CANCELED  );
       }
       if($sender->id=='bdel') {
            Document::delete($this->_doc->document_id) ;
       }
       $this->docpan->setVisible(false);
        
       $this->doclist->Reload();  
   }
    
    public function showOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
      //  $doc = Document::load($doc->document_id);
        $this->_doc = $doc->cast() ;
        $html=$this->_doc->generateReportDF() ;
        $this->docpan->docview->setText($html,true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        $this->docpan->setVisible(true);
        $this->goAnkor('docpan');
        
        $this->docpan->bedit->setVisible(false);
        $this->docpan->bcancel->setVisible(false);
        $this->docpan->bdel->setVisible(false);
        
        if($this->_doc->state < 5)   {
           $this->docpan->bedit->setVisible(true);
           $this->docpan->bdel->setVisible(true);
         
        }
        if($this->_doc->state  == Document::STATE_INPROCESS)   {
           $this->docpan->bcancel->setVisible(true);
        }
         
    }
}

class DocDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }    
    private function getWhere( ) {
       
     //   $conn = \ZDB\DB::getConnect();
        $c = Customer::load(  \App\System::getCustomer() );
        
        $where  = " customer_id  =  ". $c->customer_id;
         

        return $where;
    }

    public function getItemCount() {
        $conn = \ZDB\DB::getConnect();
        return  intval( $conn->GetOne( "select count(*) from documents_view where ".  $this->getWhere()) );
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

     
        $docs = Document::findBySql( "select * from documents_view where ". $this->getWhere(), $sortfield . " " . $asc, $count, $start);
 

        return $docs;
    }

    public function getItem($id) {

    }

}