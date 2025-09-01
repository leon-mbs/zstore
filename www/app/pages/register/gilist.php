<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
 
use App\Entity\Customer;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * журнал  продаж
 */
class GIList extends \App\Pages\Base
{
    private $_doc = null;

    /**
     *
     * @param mixed $doc Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct($doc = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('GIList')) {
            App::RedirectHome() ;
        }

        $this->add(new Panel("listpan"));
        $this->listpan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->listpan->filter->add(new TextInput('searchnumber'));
        $this->listpan->filter->add(new TextInput('searchtext'));
        $this->listpan->filter->add(new DropDownChoice('status', array(0 => 'Відкриті', 1 => 'Нові', 2 => 'Відправлені', 5 => 'Готові до відправки', 3 => 'Всі'), 0));

        $this->listpan->filter->add(new DropDownChoice('salesource', H::getSaleSources(), 0));
        $this->listpan->filter->add(new DropDownChoice('fstore', \App\Entity\Store::getList(), 0));
        $this->listpan->filter->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
 
        $doclist = $this->listpan->add(new DataView('doclist', new GoodsIssueDataSource($this), $this, 'doclistOnRow'));

        $this->listpan->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());
        $this->listpan->add(new ClickLink('csv', $this, 'oncsv'));

        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        $this->statuspan->statusform->add(new SubmitButton('bsend'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bdevivered'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bttn'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bgi'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bgar'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bret'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bnp'))->onClick($this, 'npshowOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bdecl'))->onClick($this, 'statusOnSubmit');

        $this->statuspan->statusform->add(new TextInput('ship_number'));

        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->listpan->doclist->Reload();

        //новая  почта
        $this->add(new Panel("nppan"))->setVisible(false);
        $npform = $this->nppan->add(new Form("npform"));

        $npform->add(new DropDownChoice('deltype'),[],0)->onChange($this, 'onDelType');

        $npform->onSubmit($this, "npOnSubmit");
        $npform->add(new ClickLink("npcancel", $this, "npOnCancel"));

        $npform->add(new TextInput('seltel'));

        $npform->add(new AutocompleteTextInput('selcity'))->onText($this, 'onTextSelCity');
        $npform->selcity->onChange($this, 'onSelCity');
        $npform->add(new AutocompleteTextInput('selpoint'))->onText($this, 'onTextSelPoint');;
        
        $npform->add(new AutocompleteTextInput('baycity'))->onText($this, 'onTextBayCity');
        $npform->baycity->onChange($this, 'onBayCity');
        $npform->add(new AutocompleteTextInput('baypoint'))->onText($this, 'onTextBayPoint');;
        
    
        $npform->add(new TextInput('baylastname'));
        $npform->add(new TextInput('bayfirstname'));
        $npform->add(new TextInput('baymiddlename'));
        $npform->add(new TextInput('baytel'));
        $npform->add(new TextInput('npw'));
        $npform->add(new TextInput('npplaces'));
        $npform->add(new TextInput('npcost'));
        $npform->add(new TextArea('npdesc'));
        $npform->add(new Date('npdate'));
        //   $npform->add(new DropDownChoice('npst' ))->onChange($this,'onST');
        $npform->add(new DropDownChoice('nppt'));
        $npform->add(new DropDownChoice('nppm'));
        $npform->add(new DropDownChoice('nppmback'));
        $npform->add(new TextInput('npback'));
        $npform->add(new Label('npttncust'));
        $npform->add(new Label('npttaddress'));
        $npform->add(new Label('npttnnotes'));
        $npform->add(new Label('printform'));

        $npform->add(new DropDownChoice('npgab'))->onChange($this, 'onGab',true);
        $npform->add(new TextInput('npgw'));
        $npform->add(new TextInput('npgh'));
        $npform->add(new TextInput('npgd'));
        $npform->add(new TextInput('bayaddr'));
        $npform->add(new TextInput('bayhouse'));
        $npform->add(new TextInput('bayflat'));
        
        $this->onDelType($npform->deltype);
        
        if ($doc > 0) {
            $this->_doc = Document::load($doc);
            $this->showOn();
            // $this->npshowOnSubmit($this->statuspan->statusform->bnp);;
        }
    }

    public function filterOnSubmit($sender) {

        $this->statuspan->setVisible(false);

        $this->listpan->doclist->Reload();
        if (count($this->listpan->doclist->getDataRows()) == 1) {
            $r = array_pop($this->listpan->doclist->getDataRows());

            $this->_doc = $r->getDataItem();
            $this->showOn();
        }

    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();


        $row->add(new ClickLink('number', $this, 'showOnClick'))->setValue($doc->document_number);

        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));
        $row->add(new Label('order', $doc->headerdata['order'] ?? ''));
        $row->add(new Label('customer', $doc->customer_name));

        $row->add(new Label('state', Document::getStateName($doc->state)));
 
        $row->add(new Label('ispay'))->setVisible(false) ;
        $row->add(new Label('istruck'))->setVisible(false) ;
        if($doc->state >=4) {
            if($doc->payamount > 0 &&  $doc->payamount > $doc->payed) {
                $row->ispay->setVisible(true);
            }
            if($doc->meta_name=='Invoice') {
                $n = $doc->getChildren('GoodsIssue')+$doc->getChildren('TTN');
                $row->istruck->setVisible(count($n)==0);

            }
            if($doc->meta_name=='GoodsIssue') {
                if($doc->payamount == ($doc->headerdata['prepaid']??0 ) )  {
                   $row->ispay->setVisible(false);    
                }
            }
            if($doc->state==9) {
               $row->ispay->setVisible(false);    
            }            
            
        }
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state < Document::STATE_EXECUTED) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
        if ($doc->document_id == ($this->_doc->document_id ?? 0)) {
            $row->setAttribute('class', 'table-success');
        }
    }

    public function statusOnSubmit($sender) {
        if (\App\ACL::checkChangeStateDoc($this->_doc, true, true) == false) {
            return;
        }

        $state = $this->_doc->state;

        if ($sender->id == "bsend") {

            $this->_doc->headerdata['sentdate'] = H::fd(time());
            $this->_doc->headerdata['document_date'] = time();
            $this->_doc->save();

            if ($this->_doc->state < 4) {
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            }

            $this->_doc->updateStatus(Document::STATE_INSHIPMENT);

            $this->_doc->save();

            $this->setSuccess('Відправлено');
        }

        if ($sender->id == "bdevivered") {
            $this->_doc->updateStatus(Document::STATE_DELIVERED);


            // $this->_doc->updateStatus(Document::STATE_CLOSED);
        }

        if ($sender->id == "bdecl") {
            $dec = $this->statuspan->statusform->ship_number->getText();
            if (strlen($dec) > 0) {
                $this->_doc->headerdata['ship_number'] = $dec;
            }
            $this->_doc->save();
            $this->setSuccess("Збережено");
            $this->statuspan->setVisible(false);
        }
        if ($sender->id == "bttn") {

            App::Redirect("\\App\\Pages\\Doc\\TTN", 0, $this->_doc->document_id);
        }
        if ($sender->id == "bgi") {

            App::Redirect("\\App\\Pages\\Doc\\GoodsIssue", 0, $this->_doc->document_id);
        }

        if ($sender->id == "bgar") {
            App::Redirect("\\App\\Pages\\Doc\\Warranty", 0, $this->_doc->document_id);
        }
        if ($sender->id == "bret") {
            App::Redirect("\\App\\Pages\\Doc\\ReturnIssue", 0, $this->_doc->document_id);
        }

        $this->listpan->doclist->Reload(false);

        $this->statuspan->setVisible(false);

        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {

        $this->statuspan->statusform->bdevivered->setVisible(true);
        $this->statuspan->statusform->bttn->setVisible(true);
        $this->statuspan->statusform->bgi->setVisible(true);
        $this->statuspan->statusform->bret->setVisible(true);
        $this->statuspan->statusform->bsend->setVisible(true);
        $this->statuspan->statusform->bgar->setVisible(true);
        $this->statuspan->statusform->bnp->setVisible(false);
        $this->statuspan->statusform->ship_number->setVisible(false);
        $this->statuspan->statusform->bdecl->setVisible(false);


        $state = $this->_doc->state;

        //готов  к  отправке
        if ($state == Document::STATE_READYTOSHIP) {
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->bret->setVisible(false);

        }
        //отправлен
        if ($state == Document::STATE_INSHIPMENT) {
            $this->statuspan->statusform->bsend->setVisible(false);
        }
        // Доставлен
        if ($state == Document::STATE_DELIVERED) {

            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->bsend->setVisible(false);
        }


        if ($this->_doc->meta_name == 'TTN' && $this->_doc->state == Document::STATE_READYTOSHIP) {
            $this->statuspan->statusform->bnp->setVisible(true);
            $this->statuspan->statusform->bgi->setVisible(false);
        }

        if ($this->_doc->meta_name == 'GoodsIssue') {
 
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
        }
        if ($this->_doc->meta_name == 'POSCheck') {
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->ship_number->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
        }
        if ($this->_doc->meta_name == 'Invoice') {

            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->bret->setVisible(false);
            $this->statuspan->statusform->bgar->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(true);
        }
        if ($this->_doc->meta_name == 'ReturnIssue') {

            $this->statuspan->statusform->bsend->setVisible(false);
            $this->statuspan->statusform->bdevivered->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bret->setVisible(false);
            $this->statuspan->statusform->bgar->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
        }

        if ($this->_doc->meta_name == 'TTN' && $this->_doc->state != Document::STATE_DELIVERED) {
            $this->statuspan->statusform->ship_number->setVisible(true);
            $this->statuspan->statusform->bdecl->setVisible(true);
            $this->statuspan->statusform->bgi->setVisible(false);
        }
        
        //прячем лишнее
        if ($this->_doc->meta_name == 'TTN') {
            if ($this->_doc->headerdata['delivery'] < 3) { //не  служба  доставки
                $this->statuspan->statusform->ship_number->setVisible(false);
                $this->statuspan->statusform->bdecl->setVisible(false);
            }

            if ( strlen($this->_doc->headerdata['ship_number'] ??'') >0)  { //не  служба  доставки
                $this->statuspan->statusform->ship_number->setVisible(false);
                $this->statuspan->statusform->bdecl->setVisible(false);
                $this->statuspan->statusform->bnp->setVisible(false);
            }

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
        }        
    }

    //просмотр


    public function showOnClick($sender) {
        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }
        $this->showOn();
    }

    public function showOn() {


        $this->statuspan->setVisible(true);
        $this->statuspan->statusform->ship_number->setText($this->_doc->headerdata['ship_number']??'');
        $this->statuspan->docview->setDoc($this->_doc);

        $this->listpan->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }

        App::Redirect("\\App\\Pages\\Doc\\" . $doc->meta_name, $doc->document_id);
    }

    public function oncsv($sender) {
        $list = $this->listpan->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $header = array();
        $data = array();

        $i = 0;
        foreach ($list as $d) {
            $i++;
            $data['A' . $i] = H::fd($d->document_date);
            $data['B' . $i] = $d->document_number;
            $data['C' . $i] = $d->headerdata['order'];
            $data['D' . $i] = $d->customer_name;
            $data['E' . $i] = $d->amount;
            $data['F' . $i] = $d->notes;
        }

        H::exportExcel($data, $header, 'selllist.xlsx');
    }

    //функции НП
    public function npshowOnSubmit($sender) {
        $this->statuspan->setVisible(false);
        $this->listpan->setVisible(false);
        $this->nppan->setVisible(true);
        $this->nppan->npform->clean();

        $modules = System::getOptions("modules");

        $this->nppan->npform->npplaces->setText(1);
        $this->nppan->npform->npcost->setText(0);
        $this->nppan->npform->npdate->setDate(time());

        $api = new \App\Modules\NP\Helper();

 
        //   $st = $api->getServiceTypes() ;
        //   $ct = $api->getTypesOfCounterparties() ;
        $pf = $api->getPaymentForms();
        if($pf['success'] == false) {
            $error = array_pop($pf['errors'] );
            $this->setError($error) ;
            
            return;
                     
        }
        // $ct = $api->getCargoTypes()  ;
        $tp = $api->getTypesOfPayers();

        //тип оплаты
        $stlist = array();
        foreach ($pf['data'] as $r) {
            $stlist[$r['Ref']] = $r['Description'];
        }

        $this->nppan->npform->nppm->setOptionList($stlist);
        $this->nppan->npform->nppm->setValue('Cash');
        $bmlist = array();
        $bmlist['0'] = 'Без доставки';
        $bmlist['Cash'] = 'Готiвка';
        $bmlist['NonCash'] = 'Безготiвка';
        $bmlist['Control'] = 'Контроль доставки';
        $this->nppan->npform->nppmback->setOptionList($bmlist);
        $this->nppan->npform->nppmback->setValue('0');
        //кто оплачивает
        $stlist = array();
        foreach ($tp['data'] as $r) {
            $stlist[$r['Ref']] = $r['Description'];
        }

        $this->nppan->npform->nppt->setOptionList($stlist);
        if($this->_doc->headerdata['payseller'] == 1 ) {
           $this->nppan->npform->nppt->setValue('Sender');    
        } else {
           $this->nppan->npform->nppt->setValue('Recipient');    
        }
        
     
   
        $order = Document::load($this->_doc->parent_id);
     
        if($order != null && $order->meta_name == 'Order'){
           $this->nppan->npform->deltype->setValue($order->headerdata['deliverynp']);    
           $this->onDelType($this->nppan->npform->deltype);    
           $this->nppan->npform->bayhouse->setText($order->headerdata['bayhouse']);    
           $this->nppan->npform->bayflat->setText($order->headerdata['bayflat']);    
           $this->nppan->npform->bayaddr->setText($order->headerdata['ship_address']);    
           $this->nppan->npform->baycity->setKey($order->headerdata['baycity'] ?? '');
           $this->nppan->npform->baypoint->setKey($order->headerdata['baypoint'] ?? '');
           $this->nppan->npform->baycity->setText($order->headerdata['baycityname'] ?? '');
           $this->nppan->npform->baypoint->setText($order->headerdata['baypointname'] ?? '');
            
        }
     
      
        
        $this->nppan->npform->seltel->setText($modules['nptel']);
        $this->nppan->npform->selcity->setKey($modules['npcityref']);
        $this->nppan->npform->selcity->setText($modules['npcity']);
        $this->nppan->npform->selpoint->setKey($modules['nppointref']);
        $this->nppan->npform->selpoint->setText($modules['nppoint']);
        
        
        
        $this->nppan->npform->npdesc->setText($this->_doc->notes);

        $list = $this->_doc->unpackDetails('detaildata');

        if(strlen($this->_doc->notes)==0) {
            $desc = "";
            if(count($list) < 2) {
                foreach ($list as $it) {
                    $name= strlen( $it->shortname ) > 0  ? $it->shortname : $it->itemname ;
                    $desc .= ( $name  ."," )  ;
                }
            } else {
                foreach ($list as $it) {
                    $c =  \App\Entity\Category::load($it->cat_id) ;
                    if(strlen($c->cat_name ?? '') >0) {
                       $desc = $c->cat_name;
                       break;    
                    }
                }
                
            }
            if($desc =='') {
                foreach ($list as $it) {
                    $desc = $it->itemname." + " . (count($list)-1) . ' позицій'   ;
                }
                
            }

            $this->nppan->npform->npdesc->setText(trim($desc, ","));

        }

        $w = 0;
        $p = 0;
        foreach ($list as $it) {
            if ($it->weight > 0) {
                $w += ($it->weight * $it->quantity);
            }
            $p = $p + ($it->quantity * $it->price);
        }

        $this->nppan->npform->npw->setText($w);
        $this->nppan->npform->npback->setText(round($p));
        $this->nppan->npform->npcost->setText(round($p));

        if ($order instanceof Document) {
            if ($order->payamount > 0) {
                $this->nppan->npform->npback->setText(round($order->payamount));
                $this->nppan->npform->npcost->setText(round($order->payamount));
            }
        }

        $c = \App\Entity\Customer::load($this->_doc->customer_id);
        $cust = $c->customer_name;
        $tel = '';
        if (strlen($this->_doc->headerdata["phone"]??'') > 0) {
            $cust = $cust . ', ' . $this->_doc->headerdata["phone"]??'';
            $tel = $this->_doc->headerdata["phone"]??'';
        } else {
            $tel = $c->phone??'';
            $cust = $cust . ', ' . $c->phone;
            
        }

        
      
        if(  strlen($this->nppan->npform->baycity->getKey()) <2 ){
              
                if(strlen ($c->npcityref??'')>0)  {
                   $this->nppan->npform->baycity->setKey($c->npcityref) ;
                   $this->nppan->npform->baycity->setText( $c->npcityname) ;
                   $this->nppan->npform->baypoint->setKey($c->nppointref) ;
                   $this->nppan->npform->baypoint->setText($c->nppointname) ;
                }
        }          
        
        
        $this->nppan->npform->baytel->setText($tel);
        $name =   \App\Util::strtoarray($c->customer_name);
        $this->nppan->npform->baylastname->setText($name[0]);
        $this->nppan->npform->bayfirstname->setText($name[1]??'');
        $this->nppan->npform->baymiddlename->setText($name[2]??'');

        $this->nppan->npform->npttncust->setText($cust);
        $this->nppan->npform->npttaddress->setText($this->_doc->headerdata["ship_address"]);
        $this->nppan->npform->npttnnotes->setText($this->_doc->notes);

        $this->nppan->npform->printform->setText($this->_doc->cast()->generateReport(), true);
        
        $gablist=[];
        $tmp=[];
        
        if(strlen( $modules['npgl'] ?? '') >0) {
           $tmp = unserialize( $modules['npgl'] );    
        }
        foreach($tmp as $g){
           $gablist[$g->gabname]= $g->gabname;   
        }
        $this->nppan->npform->npgab->setOptionList($gablist);
    }

 
  

    public function npOnCancel($sender) {
        $this->statuspan->setVisible(false);
        $this->listpan->setVisible(true);
        $this->nppan->setVisible(false);
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1, true);
    }
    
    public function onGab($sender) {
       $g=$sender->getValue();  
       $ga=[];
       if($g=='0') {
          $ga[0] = '';
          $ga[1] = '';
          $ga[2] = '';
       }  else {
          $ga = explode('x',$g) ;
       }
        
       $this->nppan->npform->npgw->setText($ga[0]); 
       $this->nppan->npform->npgh->setText($ga[1]); 
       $this->nppan->npform->npgd->setText($ga[2]); 
    }
    public function onDelType($sender) {
      
      $dt=$sender->getValue();  
        
 //     $this->nppan->npform->baypoint->setKey('') ;   
  //    $this->nppan->npform->baypoint->setText('') ;   
  //    $this->nppan->npform->baycity->setKey('') ;   
   //   $this->nppan->npform->baycity->setText('') ;   


      $this->nppan->npform->npgab->setVisible($dt ==1) ;   
      $this->nppan->npform->npgw->setVisible($dt ==1) ;   
      $this->nppan->npform->npgh->setVisible($dt ==1) ;   
      $this->nppan->npform->npgd->setVisible($dt ==1) ;   
      $this->nppan->npform->bayaddr->setVisible($dt ==2) ;   
      $this->nppan->npform->bayhouse->setVisible($dt ==2) ;   
      $this->nppan->npform->bayflat->setVisible($dt ==2) ;   
      
      
      
    }  
    
    public function onTextSelCity($sender) {
        $text = $sender->getText()  ;
        $api = new \App\Modules\NP\Helper();
        $list = $api->searchCity($text);

        if($list['success']!=true) return;
        $opt=[];  
        foreach($list['data'] as $d ) {
            foreach($d['Addresses'] as $c) {
               $opt[$c['Ref']]=$c['Present']; 
            }
        }
        
        return $opt;
       
    }

    public function onSelCity($sender) {
     
        $this->nppan->npform->selpoint->setKey('');
        $this->nppan->npform->selpoint->setText('');
    }
 
  
    public function onTextSelPoint($sender) {
        $text = $sender->getText()  ;
        $ref=  $this->nppan->npform->selcity->getKey();
        $api = new \App\Modules\NP\Helper();
        $list = $api->searchPoints($ref,$text);
       
        if($list['success']!=true) return;
        
        $opt=[];  
        foreach($list['data'] as $d ) {
           $opt[$d['WarehouseIndex']]=$d['Description']; 
        }
        
        return $opt;        
    }
    
    public function onTextBayCity($sender) {
        $text = $sender->getText()  ;
        $api = new \App\Modules\NP\Helper();
        $list = $api->searchCity($text);

        if($list['success']!=true) return;
        $opt=[];  
        foreach($list['data'] as $d ) {
            foreach($d['Addresses'] as $c) {
               $opt[$c['Ref']]=$c['Present']; 
            }
        }
        
        return $opt;
       
    }

    public function onBayCity($sender) {
     
        $this->nppan->npform->baypoint->setKey('');
        $this->nppan->npform->baypoint->setText('');
    }
 
  
    public function onTextBayPoint($sender) {
        $text = $sender->getText()  ;
        $ref=  $this->nppan->npform->baycity->getKey();
        $api = new \App\Modules\NP\Helper();
        $list = $api->searchPoints($ref,$text);
       
        if($list['success']!=true) return;
        
        $opt=[];  
        foreach($list['data'] as $d ) {
           $opt[$d['WarehouseIndex']]=$d['Description']; 
        }
        
        return $opt;        
    }
    
    
     
    public function npOnSubmit($sender) {
        $params = array();
        $dt = $this->nppan->npform->deltype->getValue();  //0-отделение 1-поштомат 2-по адресу

        $params['DateTime'] = date('d.m.Y', $this->nppan->npform->npdate->getDate());
        $params['ServiceType'] = 'WarehouseWarehouse';
        $params['PaymentMethod'] = $this->nppan->npform->nppm->getValue();
        $params['PayerType'] = $this->nppan->npform->nppt->getValue();
        $params['Cost'] = $this->nppan->npform->npcost->getText();
        $params['SeatsAmount'] = $this->nppan->npform->npplaces->getText();
        $params['Description'] = trim($this->nppan->npform->npdesc->getText());
        $params['CargoType'] = 'Cargo';
     
        $params['Weight'] = $this->nppan->npform->npw->getText();
        if ($params['SeatsAmount'] > 1) {
            $params['Weight'] = number_format($params['Weight'] / $params['SeatsAmount'], 1, '.', '');
        }
     
        if($dt==1) {
            $params['CargoType'] = 'Parcel';
     
            $params['OptionsSeat'] =[] ;
            $params['OptionsSeat'][] =array(
                            'volumetricWidth' =>intval( $this->nppan->npform->npgw->getText()),
                            'volumetricLength' => intval( $this->nppan->npform->npgd->getText()),
                            'volumetricHeight' => intval( $this->nppan->npform->npgh->getText()),
                            'weight' => $params['Weight']  
                           
                          );
                
        }
        
        $moneyback = $this->nppan->npform->npback->getText();

        if ($moneyback > 0) {   //если  введена  обратная сумма
            $back = $this->nppan->npform->nppmback->getValue();

            if ($back == '0') {
                $this->setError('Не вказано тип оплати зворотньої доставки'); 
                return;
            }
            if ($back == 'Control') {
                $params['AfterpaymentOnGoodsCost'] = $moneyback;
            } else {
                if ($back == 'Cash') {
                    $params['BackwardDeliveryData'] = array(array(
                        'PayerType'        => 'Recipient',
                        'CargoType'        => 'Money',
                        'RedeliveryString' => $moneyback
                    )
                    );
                } else {
                    if ($back == 'NonCash') {
                        $params['BackwardDeliveryData'] = array(array(
                            'PayerType'        => 'Recipient',
                            'CargoType'        => 'Money',
                            'PaymentMethod'    => 'NonCash',
                            'RedeliveryString' => $moneyback
                        )
                        );
                    }
                }
            }
        }


        //проверка  введеных параметров
        if (($params['Weight'] > 0) == false) {
            $this->setError('Не вказано вагу');
            return;
        }
        if (strlen($params['Description']) == 0) {
            $this->setError('Не вказано опис');
            return;
        }
        $api = new \App\Modules\NP\Helper();

        $sender = array();
        $recipient = array();

        try {
       

            $result = $api->model('Counterparty')->getCounterparties("Sender");
            if ($result['success'] == false) {
                $error = array_pop($result['errors'] );
                $this->setError($error) ;
            
                return;
            }

            $resultc = $api->model('Counterparty')->getCounterpartyContactPersons($result['data'][0]['Ref']);
            if ($resultc['success'] == false) {
                $error = array_pop($result['errors'] );
                $this->setError($error) ;
                return;
            }


            $sender['Sender'] = $result['data'][0]['Ref'];
            $sender['SendersPhone'] = $this->nppan->npform->seltel->getText();
            $sender['CitySender'] = $this->nppan->npform->selcity->getKey();
            $sender['SenderType'] = $result['data'][0]['CounterpartyType'];
            $sender['ContactSender'] = $resultc['data'][0]['Ref'];
          //  $sender['SenderAddress'] = $this->nppan->npform->selpoint->getKey();
            $sender['SenderWarehouseIndex'] = $this->nppan->npform->selpoint->getKey();
     
            $recipient['FirstName'] = $this->nppan->npform->bayfirstname->getText();
            $recipient['MiddleName'] = $this->nppan->npform->baymiddlename->getText();
            $recipient['LastName'] = $this->nppan->npform->baylastname->getText();
            $recipient['Phone'] = $this->nppan->npform->baytel->getText();
            $recipient['Email'] = '';
            $recipient['CounterpartyType'] = 'PrivatePerson';
            $recipient['CounterpartyProperty'] = 'Recipient';

            $result = $api->model('Counterparty')->save($recipient);

            if ($result['success'] == false) {
                $error = array_pop($result['errors'] );
                $this->setError($error) ;
                return;
            }

            $recipient['RecipientsPhone'] = $this->nppan->npform->baytel->getValue();
            $recipient['RecipientType'] = $result['data'][0]['CounterpartyType'];
              $recipient['Recipient'] = $result['data'][0]['Ref'];
              $recipient['ContactRecipient'] = $result['data'][0]['ContactPerson']['data'][0]['Ref'];
              $recipient['CityRecipient'] = $this->nppan->npform->baycity->getKey();
            //   $recipient['RecipientAddress'] = $this->nppan->npform->baypoint->getKey();
              $recipient['RecipientWarehouseIndex'] = $this->nppan->npform->baypoint->getKey();
    
            if($dt==2) {
              $recipient['RecipientCityName'] = $this->nppan->npform->baycity->getText();
  //            $recipient['RecipientAreaRegions'] = $this->nppan->npform->bayarea->getValue();
            //  $recipient['RecipientArea'] = $this->nppan->npform->bayarea->getValueName();
              $recipient['RecipientAddressName'] = $this->nppan->npform->bayaddr->getText();
              $recipient['RecipientHouse'] = $this->nppan->npform->bayhouse->getText();
              $recipient['RecipientFlat'] = $this->nppan->npform->bayflat->getText();
              $recipient['RecipientName'] = $recipient['LastName'] .' '.$recipient['FirstName'].' '.$recipient['MiddleName'];
             
          
           }  
            $paramsInternetDocument = array_merge($sender, $recipient, $params);

            $result = $api->model('InternetDocument')->save($paramsInternetDocument);
            
        } catch(\Throwable $e) {
            $this->setError($e->getMessage());
            return;
        }
        if ($result['success'] == true) {

            $this->_doc->headerdata['delivery_date'] = strtotime($result['data'][0]['EstimatedDeliveryDate']);
            if($params['PayerType']=='Sender') {
                $this->_doc->headerdata['ship_amount'] = $result['data'][0]['CostOnSite'];
            }
            $this->_doc->headerdata['ship_number'] = $result['data'][0]['IntDocNumber'];
            $this->_doc->headerdata['ship_numberref'] = $result['data'][0]['Ref'];
            $this->_doc->headerdata['moneyback'] = $moneyback;
            $this->_doc->save();
            $this->setSuccess("Створено декларацію номер " . $this->_doc->headerdata['ship_number']);


            $order = Document::load($this->_doc->parent_id);
            if ($order instanceof Document) {
                if ($order->state == Document::STATE_READYTOSHIP) {
                    $order->updateStatus(Document::STATE_INSHIPMENT);
                }
            }

            if($this->_doc->customer_id > 0){
                $c = \App\Entity\Customer::load($this->_doc->customer_id);
                $c->npcityref   =  $this->nppan->npform->baycity->getKey() ;
                $c->npcityname  =  $this->nppan->npform->baycity->getText() ;
                $c->nppointref  =  $this->nppan->npform->baypoint->getKey() ;
                $c->nppointname =  $this->nppan->npform->baypoint->getText() ;
                $c->save();
            }
            

            $this->statuspan->setVisible(false);
            $this->listpan->setVisible(true);
            $this->nppan->setVisible(false);
        } else {
            $error =" ". implode(";", $result['errors'] );
            if(strpos($error,'OptionsSeat')>0) {
                $error= "Не вказано габарити для поштомата або невідповідний тип доставки";
            }
            
            $this->setError($error) ;
        }
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class GoodsIssueDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();
        $common = System::getOptions("common");
        $actualdate = $common['actualdate'] ??  strtotime('2023-01-01') ;
        
        $conn = \ZDB\DB::getConnect();

        $actualdate =   $conn->DBDate($actualdate  );
        
        $where = "   meta_name  in('GoodsIssue', 'Invoice','POSCheck','ReturnIssue' ,'Warranty','TTN' )  and document_date >= ".$actualdate;

        $salesource = $this->page->listpan->filter->salesource->getValue();
        if ($salesource > 0) {
            $where .= " and   content like '%<salesource>{$salesource}</salesource>%' ";
        }

        $status = $this->page->listpan->filter->status->getValue();
        if ($status == 0) {
            $where .= "  and    state >3 and  state  not in(14,5,9 )        ";
        }
        if ($status == 1) {
            $where .= " and  state =  " . Document::STATE_NEW;
        }
        if ($status == 2) {
            $where .= " and state = " . Document::STATE_INSHIPMENT;
        }
        if ($status == 5) {
            $where .= " and state = " . Document::STATE_READYTOSHIP;
        }

      
        $cust = $this->page->listpan->filter->searchcust->getKey();
        if ($cust > 0) {
            $where = $where . " and customer_id = " . $cust;
        }

        $store_id = $this->page->listpan->filter->fstore->getValue();
        if ($store_id > 0) {
            $where .= " and   content like '%<store>{$store_id}</store>%' ";
        }

        $st = trim($this->page->listpan->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and  (  notes like {$st} or    content like {$st}  )";
        }
        $sn = trim($this->page->listpan->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = "  meta_name in('GoodsIssue', 'Invoice','POSCheck','ReturnIssue' ,'Warranty','TTN' )  and document_number like  {$sn}    ";
        }

        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "priority desc,document_id desc", $count, $start);

        return $docs;
    }

    public function getItem($id) {

    }

}
