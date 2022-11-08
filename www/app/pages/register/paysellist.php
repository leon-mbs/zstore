<?php

namespace App\Pages\Register;

use App\Entity\Doc\Document;
use App\Entity\Pay;
use App\Helper as H;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * журнал расчет с поставщиками
 */
class PaySelList extends \App\Pages\Base
{

    private $_doc       = null;
    private $_cust      = null;
    public  $_custlist  = array();
    public  $_doclist   = array();
    public  $_blist   = array();
    public  $_pays      = array();
    public  $_totamountd = 0;
    public  $_totamountc = 0;
 public  $_bal = 0;
     
    public function __construct($docid=0) {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('PaySelList')) {
            return;
        }
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('holdlist', \App\Entity\Customer::getHoldList(), 0));

        $this->add(new Panel("clist"));

        $this->clist->add(new Label("totamountd"));
        $this->clist->add(new Label("totamountc"));

        $this->clist->add(new DataView('custlist', new ArrayDataSource($this, '_custlist'), $this, 'custlistOnRow'));

        $this->add(new Panel("plist"))->setVisible(false);
        $this->plist->add(new Label("cname"));
        $this->plist->add(new ClickLink("back", $this, "onBack"));

        $doclist = $this->plist->add(new DataView('doclist', new ArrayDataSource($this, '_doclist'), $this, 'doclistOnRow'));

       $this->add(new Panel("dlist"))->setVisible(false);
        $this->dlist->add(new Label("cnamed"));
        $this->dlist->add(new ClickLink("backd", $this, "onBack"));
        $this->dlist->add(new DataView('blist', new ArrayDataSource($this, '_blist'), $this, 'blistOnRow'));
       
        
        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        $this->add(new Panel("paypan"))->setVisible(false);
        $this->paypan->add(new Label("pname"));
        $this->paypan->add(new Form('payform'))->onSubmit($this, 'payOnSubmit');
        $this->paypan->payform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(), H::getDefMF()));
        $this->paypan->payform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', "details like '%<usefisc>1</usefisc>%' "), 0));
        $this->paypan->payform->add(new TextInput('pamount'));
        $this->paypan->payform->add(new TextInput('pcomment'));
        $this->paypan->payform->add(new Date('pdate', time()));

        $this->paypan->add(new DataView('paylist', new ArrayDataSource($this, '_pays'), $this, 'payOnRow'))->Reload();


        $this->updateCust();
        
      if($docid>0) {
            $this->payDoc($docid) ;
        }
          
        
    }

    public function filterOnSubmit($sender) {


        $this->plist->setVisible(false);
        $this->updateCust();
    }

    public function updateCust() {
        $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }
        $hold = "";
        $holding = $this->filter->holdlist->getValue();
        if ($holding > 0) {
            $hold = "  and   c.detail like '%<holding>{$holding}</holding>%'";
        }

 
     
         $sql = "SELECT c.customer_name,c.phone, c.customer_id, COALESCE( SUM( a.s_passive),0) as  pas, coalesce(SUM( a.s_active ),0) AS act
            FROM cust_acc_view a  join customers c  on a.customer_id = c.customer_id and c.status=0    
             WHERE  a.s_active <> a.s_passive     {$hold}
             group by c.customer_name,c.phone, c.customer_id
             order by c.customer_name
             ";

      $this->_custlist = array();
        
        foreach( \App\DataItem::query($sql) as $_c){
            $_c->docs=0;
            $this->_custlist[$_c->customer_id]=$_c;         
        };
                                    
        $sql = "SELECT c.customer_name,c.phone, c.customer_id, coalesce(count(*),0) as docs 
             FROM documents_view d  join customers c  on d.customer_id = c.customer_id and c.status=0    
             WHERE  d.state = ". Document::STATE_WP  ."   and   d.meta_name in('InvoiceCust','RetCustIssue','GoodsReceipt')   {$hold}
             group by c.customer_name,c.phone, c.customer_id
             order by c.customer_name
             ";

        $ids = array_keys($this->_custlist)  ;
        foreach( \App\DataItem::query($sql) as $_c){
            if(in_array($_c->customer_id,$ids)) {
                 $this->_custlist[$_c->customer_id]->docs = $_c->docs;                                         
            } else {
                 $this->_custlist[$_c->customer_id] = $_c;                                                         
            }
            
        };

  
        $this->_totamountc = 0;
        $this->_totamountd = 0;

        $this->clist->custlist->Reload();
        $this->clist->totamountd->setText( $this->_totamountd <0 ? H::fa(0-$this->_totamountd):'');
        $this->clist->totamountc->setText( $this->_totamountc >0 ? H::fa($this->_totamountc):'');

        
    }

    public function custlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $cust = $row->getDataItem();
        $row->add(new RedirectLink('customer_name', "\\App\\Pages\\Reference\\CustomerList", array($cust->customer_id)))->setValue($cust->customer_name);
        $row->add(new Label('phone', $cust->phone));
        $diff = $cust->act - $cust->pas;   //плюс - наш долг
        $row->add(new Label('amountc',$diff >0 ? H::fa($diff):''));
        $row->add(new Label('amountd',$diff <0 ? H::fa(0-$diff):''));

        $row->add(new RedirectLink('createpay'))->setVisible($diff>0);
        if ($diff>0) {
            $row->createpay->setLink("\\App\\Pages\\Doc\\OutcomeMoney", array(0, $cust->customer_id, $diff ));
            $row->createpay->setVisible(true);
        }

        $row->add(new ClickLink('showdocs',$this, 'showdocsOnClick'))->setVisible($cust->docs>0);
        $row->add(new ClickLink('showdet',$this, 'showdetOnClick'))->setVisible($diff != 0);

        $this->_totamountd += ($diff<0 ? $diff:0 );
        $this->_totamountc += ($diff>0 ? $diff:0 );
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


        $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }
        $this->_doclist = array();

        $list = \App\Entity\Doc\Document::find(" {$br} customer_id= {$this->_cust->customer_id}  and   state = ". Document::STATE_WP  ."    and meta_name in('InvoiceCust','RetCustIssue','GoodsReceipt') ", "document_date desc, document_id desc");
  

        foreach ($list as $d) {
            $this->_doclist[] = $d;
          
        }
        $this->_doclist = array_reverse($this->_doclist);

        $this->plist->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('name', $doc->meta_desc));
        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('date', H::fd($doc->document_date)));

        $row->add(new Label('sum', H::fa( $doc->payamount  - $doc->payed   )) );
 
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('pay'))->onClick($this, 'payOnClick');
        $row->pay->setVisible($doc->payamount > 0);
         
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        
        if($this->_doc instanceof \App\DataItem){
           $this->_doc  = Document::load($this->_doc->document_id ); 
        }        
        
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

      
        $this->docview->setVisible(true);
        $this->paypan->setVisible(false);
        $this->docview->setDoc($this->_doc);
        $this->goAnkor('dankor');
    }

    public function onBack($sender) {
        $this->clist->setVisible(true);
        $this->dlist->setVisible(false);
        $this->plist->setVisible(false);
        $this->docview->setVisible(false);
        $this->paypan->setVisible(false);
        $this->updateCust();
    }

    //оплаты
    
    
    public function payDoc($docid) { 

        $this->_doc = Document::load($docid)  ;
        $this->_cust = \App\Entity\Customer::load($this->_doc->customer_id);
        $this->showPay();
        $this->plist->cname->setText($this->_cust->customer_name);
        $this->updateDocs();
     
        $this->clist->setVisible(false);
        $this->plist->setVisible(true);

    }    
    
    public function payOnClick($sender) { 
        $this->docview->setVisible(false);

        $this->_doc = $sender->owner->getDataItem();
     //   $this->plist->doclist->setSelectedRow($sender->getOwner());
        $this->showPay();
    }

    public function showPay() { 
//        $this->plist->doclist->Reload(false);
        $this->docview->setVisible(false);


        $this->paypan->setVisible(true);


        $this->plist->doclist->Reload(false);

        $this->goAnkor('dankor');
        $amount = $this->_doc->payamount - $this->_doc->payed;
   
        $this->paypan->payform->pamount->setText(H::fa($amount));
        $this->paypan->payform->pcomment->setText("");;
        $this->paypan->pname->setText($this->_doc->document_number);;

        $this->_pays = \App\Entity\Pay::getPayments($this->_doc->document_id);
        $this->paypan->paylist->Reload();
    }

    public function payOnRow($row) {
        $pay = $row->getDataItem();
        $row->add(new Label('plamount', H::fa($pay->amount)));
        $row->add(new Label('pluser', $pay->username));
        $row->add(new Label('pldate', H::fdt($pay->paydate)));
        $row->add(new Label('plmft', $pay->mf_name));
        $row->add(new Label('plcomment', $pay->notes));
    }

    public function payOnSubmit($sender) {
        $form = $this->paypan->payform;
        $pos_id = $form->pos->getValue();
        $amount = $form->pamount->getText();
        $pdate = $form->pdate->getDate();
        if ($amount == 0) {
            return;
        }


        if ($amount > H::fa($this->_doc->payamount - $this->_doc->payed)) {

            $this->setWarn('sumoverpay');
        }
        $type = \App\Entity\IOState::TYPE_BASE_INCOME;

        if (in_array($this->_doc->meta_name, array('GoodsReceipt', 'InvoiceCust', 'ReturnIssue'))) {
            $amount = 0 - $amount;
            $type = \App\Entity\IOState::TYPE_BASE_OUTCOME;
        }


        if ($pos_id > 0) {
            $pos = \App\Entity\Pos::load($pos_id);
            $this->_doc->headerdata["fiscalnumberpos"]  =  $pos->fiscalnumber;
 
            $ret = \App\Modules\PPO\PPOHelper::checkpay($this->_doc, $pos_id, $amount, $form->payment->getValue());
            if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                //повторяем для  нового номера
                $pos->fiscdocnumber = $ret['doclocnumber'];
                $pos->save();
                $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
            }
            if ($ret['success'] == false) {
                $this->setErrorTopPage($ret['data']);
                return;
            } else {

                if ($ret['docnumber'] > 0) {
                    $pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                    $pos->save();
                    $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                } else {
                    $this->setError("ppo_noretnumber");
                    return;
                }
            }
        }


        $payed = Pay::addPayment($this->_doc->document_id, $pdate, $amount, $form->payment->getValue(),  $form->pcomment->getText());
        \App\Entity\IOState::addIOState($this->_doc->document_id, $amount, $type);
        
        if($payed>=$this->_doc->payamount){
           $this->markPayed()  ;            
        }

 
        $this->setSuccess('payment_added');

        //$this->updateDocs();
        $this->paypan->setVisible(false);
        $this->onBack(null);
    }

     private function markPayed(){
        if( $this->_doc->state == Document::STATE_WP ){
         
            $this->_doc = Document::load($this->_doc->document_id);
            if( $this->_doc->meta_name=='InvoiceCust') {
                $this->_doc->updateStatus(Document::STATE_PAYED);                            
                return;
            }

             //предыдущий статус
            $states = $this->_doc->getLogList();
            
            $prev = intval( $states[count($states)-2]->docstate )        ;
            if($prev  < 5) $prev = Document::STATE_EXECUTED  ;
            $this->_doc->updateStatus($prev,true);            
            
        }         
        
    }       
    
    //детализация  баланса
   public function showdetOnClick($sender) {

        $this->_cust = $sender->owner->getDataItem();
        $this->dlist->cnamed->setText($this->_cust->customer_name);
        $this->_bal = 0;         
        $this->updateDetDocs();
 

        $this->clist->setVisible(false);
        $this->dlist->setVisible(true);
    }
    public function updateDetDocs() {

        
        $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }

        $this->_blist = array();

        $list = \App\Entity\Doc\Document::find(" {$br} customer_id= {$this->_cust->customer_id} and    state NOT IN (0, 1, 2, 3, 15, 8, 17) " , "document_id asc ",-1,-1,"*,  COALESCE( ((CASE WHEN (meta_name IN ('InvoiceCust', 'GoodsReceipt', 'IncomeService', 'OutcomeMoney')) THEN payed WHEN ((meta_name = 'OutcomeMoney') AND      (content LIKE '%<detail>2</detail>%')) THEN payed WHEN (meta_name = 'RetCustIssue') THEN payamount ELSE 0 END)), 0) AS s_passive,  COALESCE( ((CASE WHEN (meta_name IN ('GoodsReceipt','IncomeService') ) THEN payamount WHEN ((meta_name = 'IncomeMoney') AND      (content LIKE '%<detail>2</detail>%')) THEN payed WHEN (meta_name = 'RetCustIssue') THEN payed ELSE 0 END)), 0) AS s_active ");

        $bal=0;

        foreach ($list as $id=>$d) {
            if($d->s_active != $d->s_passive ){
                
                 $r = new  \App\DataItem() ;
                 $r->document_id = $d->document_id;
                 $r->meta_desc = $d->meta_desc;
                 $r->document_number = $d->document_number;
                 $r->document_date = $d->document_date;
                 $r->s_active = $d->s_active;
                 $r->s_passive = $d->s_passive;

                 $diff = $d->s_passive - $d->s_active;
        
                 $bal +=  $diff;        
                 $r->bal =  $bal;

                 $this->_blist[] = $r; 
                 if($bal==0){
                     $this->_blist = array();  
                 }                                
            }
             
        }
//        $this->_blist = array_reverse($this->_doclist);


        $this->dlist->blist->Reload();
    }

    public function blistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('dname', $doc->meta_desc));
        $row->add(new Label('dnumber', $doc->document_number));
        $row->add(new Label('ddate', H::fd($doc->document_date)));
        

        $row->add(new Label('out', $doc->s_passive > 0 ?  H::fa( $doc->s_passive):"" ));
        $row->add(new Label('in', $doc->s_active>0 ? H::fa( $doc->s_active):"" ));
        $row->add(new Label('bc', $doc->bal > 0? H::fa( $doc->bal):"" ));
        $row->add(new Label('bd', $doc->bal < 0? H::fa( 0-$doc->bal):"" ));
      
        $row->add(new ClickLink('showdet'))->onClick($this, 'showOnClick');

        
    }   
}
