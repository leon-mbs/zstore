<?php

namespace App\Pages\Register;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\Paginator;
use \Zippy\Html\DataList\ArrayDataSource;
 
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
 * журнал расчет с контрагентами 
 */
class PayCustList extends \App\Pages\Base {

    private $_doc = null;
    private $_cust = null;
    public  $_custlist = array();
    public  $_doclist = array();
    public $_pays = array();
    
    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('PayCustList'))
            return;

        $this->add(new Panel("clist")) ;    
            
        $this->clist->add(new DataView('custlist', new ArrayDataSource($this,'_custlist'), $this, 'custlistOnRow'));
 
        $this->clist->add(new ClickLink('csv', $this, 'oncsv'));
 
        $this->add(new Panel("plist"))->setVisible(false);
        $this->plist->add(new Label("cname")) ;
        $this->plist->add(new ClickLink("back",$this,"onBack")) ;

        $doclist = $this->plist->add(new DataView('doclist', new ArrayDataSource($this,'_doclist'), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');
  
        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);
  

        $this->add(new Panel("paypan"))->setVisible(false);
        $this->paypan->add(new Label("pname"));
        $this->paypan->add(new Form('payform'))->onSubmit($this, 'payOnSubmit');
        $this->paypan->payform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(), H::getDefMF()));
        $this->paypan->payform->add(new TextInput('pamount'));
        $this->paypan->payform->add(new TextInput('pcomment'));
        $this->paypan->payform->add(new SubmitButton('bpay'))->onClick($this, 'payOnSubmit');

        $this->paypan->add(new DataView('paylist', new ArrayDataSource($this, '_pays'), $this, 'payOnRow'))->Reload();



        $this->updateCust();
        
    }

   
    public function updateCust() {
      
    $sql = "select customer_name,fl,coalesce(sum(am),0) as sam from  (
            select   customer_name,  ( amount - payamount)  as  am ,(case when meta_name in('GoodsReceipt') then -1 else 1 end) as fl
            from `documents_view` where amount > 0 and amount <> payamount  and state not in (1,2,3,17)  and meta_name in('GoodsReceipt','GoodsIssue','Task','ServiceAct')

            ) t   group by customer_name ,fl   order by  (sam) desc";
      $this->_custlist = \App\DataItem::query($sql);  
      $this->clist->custlist->Reload();   
    }
    
    public function custlistOnRow($row) {
        $cust = $row->getDataItem();
        $row->add(new Label('customer_name', $cust->customer_name)); 
        $row->add(new Label('credit',$cust->fl==-1 ? $cust->sam : "")); 
        $row->add(new Label('debet', $cust->fl==1 ? $cust->sam : "")); 
        
        $row->add(new ClickLink('showdocs'))->onClick($this, 'showdocsOnClick');
              
    }
    
    //список документов
    public function showdocsOnClick($sender) {

        $this->_cust = $sender->owner->getDataItem();
        $this->plist->cname->setText($this->_cust->customer_name);
        $this->updateDocs();
        
        $this->clist->setVisible(false);
        $this->plist->setVisible(true);
        
    }
    
    public function updateDocs() {
     
      if($this->_cust->fl == -1){
          $docs="'GoodsReceipt','CustInvoice'";
      }
      if($this->_cust->fl == 1){
          $docs="'GoodsIssue','Task','ServiceAct','Invoice'";
      }
      
     
      $sql = "select * from (
            select d.* ,(amount - payamount) as am
            from `documents_view` where amount > 0 and amount <> payamount  and state not in (1,2,3,17)  and meta_name in({$docs}) 
              
            ) t  order by am desc  ";  
      $this->_doclist = \App\Entity\Doc\Document::find("amount > 0 and amount <> payamount  and state not in (1,2,3,17)  and meta_name in({$docs})","(amount - payamount) desc");  
                      
      $this->plist->doclist->Reload(); 
    } 
 
    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('name', $doc->meta_desc));
        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('date', date('d.m.Y',$doc->document_date)));

        
        $row->add(new Label('amount', $doc->amount));
        $row->add(new Label('payamount', $doc->amount - $doc->payamount));

   

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('pay'))->onClick($this, 'payOnClick');
  
    }

   //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true))
            return;
        $this->plist->doclist->setSelectedRow($sender->getOwner());
        $this->plist->doclist->Reload(false);
        $this->docview->setVisible(true);
        $this->paypan->setVisible(false);
        $this->docview->setDoc($this->_doc);
        $this->goAnkor('dankor');
    }

    public function onBack($sender) {
        $this->clist->setVisible(true);
        $this->plist->setVisible(false);
        $this->docview->setVisible(false);
        $this->paypan->setVisible(false);
        $this->updateCust();
    }
     //оплаты
    public function payOnClick($sender) {
        $this->docview->setVisible(false);


        $this->_doc = $sender->owner->getDataItem();


        $this->paypan->setVisible(true);

  
        $this->plist->doclist->setSelectedRow($sender->getOwner());
        $this->plist->doclist->Reload(false);

        $this->goAnkor('dankor');

        $this->paypan->payform->pamount->setText($this->_doc->amount - $this->_doc->payamount);
        ;
        $this->paypan->payform->pcomment->setText("");
        ;
        $this->paypan->pname->setText($this->_doc->document_number);
        ;

        $this->_pays = \App\Entity\Pay::getPayments($this->_doc->document_id);
        $this->paypan->paylist->Reload();
    }

    public function payOnRow($row) {
        $pay = $row->getDataItem();
        $row->add(new Label('plamount', 0 - $pay->amount));
        $row->add(new Label('pluser', $pay->username));
        $row->add(new Label('pldate', date('Y-m-d', $pay->paydate)));
        $row->add(new Label('plmft', $pay->mf_name));
        $row->add(new Label('plcomment', $pay->notes));
    }

    public function payOnSubmit($sender) {
        $form = $this->paypan->payform;
        $amount = $form->pamount->getText();
        if ($amount == 0)
            return;
        $amount = $form->pamount->getText();
        if ($amount == 0)
            return;
       
        if ($amount > $this->_doc->amount) {
            $this->setError('Сумма  больше  необходимой  оплаты');
            return;
        }

        $type=  \App\Entity\Pay::PAY_BASE_INCOME;
        //закупки  и возвраты
        if($this->_doc->meta_name == 'GoodsReceipt' || $this->_doc->meta_name == 'ReturnIssue'){
            $amount  = 0 - $amount;
             $type =  \App\Entity\Pay::PAY_BASE_OUTCOME;
        }
        
        \App\Entity\Pay::addPayment($this->_doc->document_id, 0,  $amount, $form->payment->getValue(),$type, $form->pcomment->getText());
     


        $this->setSuccess('Оплата добавлена');
   

        $this->updateDocs();
        $this->paypan->setVisible(false);
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $d) {
            $csv .= date('Y.m.d', $d->document_date) . ';';
            $csv .= $d->document_number . ';';
            $csv .= $d->headerdata["pareaname"] . ';';
            $csv .= $d->amount . ';';
            $csv .= $d->notes . ';';
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



class PayCustDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

  

    public function getItemCount() {                   
        
        $conn = \ZDB\DB::getConnect();
         
        return $conn->GetOne($sql);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "document_date desc,document_id desc", $count, $start);

        
        return $docs;
    }

    public function getItem($id) {
        
    }

}
 
class PayCustDocDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = " date(document_date) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(document_date) <= " . $conn->DBDate($this->page->filter->to->getDate());

        $where .= " and meta_name  in ('Task','ProdIssue','ProdReceipt')  ";




        $parea = $this->page->filter->parea->getValue();
        if ($parea > 0) {
            $where .= " and content like '%<parea>{$parea}</parea>%'  ";
        }



        if ($user->acltype == 2) {


            $where .= " and meta_id in({$user->aclview}) ";
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
