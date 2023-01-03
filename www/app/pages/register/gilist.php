<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\Entity\Firm;
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
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct($doc = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('GIList')) {
            return;
        }

        $this->add(new Panel("listpan"));
        $this->listpan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->listpan->filter->add(new TextInput('searchnumber'));
        $this->listpan->filter->add(new TextInput('searchtext'));
        $this->listpan->filter->add(new DropDownChoice('status', array(0 => H::l('opened'), 1 => H::l('newed'), 2 => H::l('sended'), 5 => H::l('st_rdshipment'), 3 => H::l('all')), 0));
        $this->listpan->filter->add(new DropDownChoice('searchcomp', Firm::findArray('firm_name', 'disabled<>1', 'firm_name'), 0));
        $this->listpan->filter->add(new DropDownChoice('salesource', H::getSaleSources(), 0));
        $this->listpan->filter->add(new DropDownChoice('fstore', \App\Entity\Store::getList(), 0));

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
        $npform->onSubmit($this, "npOnSubmit");
        $npform->add(new ClickLink("npcancel", $this, "npOnCancel"));
        $npform->add(new DropDownChoice('selarea'))->onChange($this, 'onSelArea');
        $npform->add(new DropDownChoice('selcity'))->onChange($this, 'onSelCity');
        $npform->add(new DropDownChoice('selpoint'));
        $npform->add(new TextInput('seltel'));

        $npform->add(new DropDownChoice('bayarea'))->onChange($this, 'onBayArea');
        $npform->add(new DropDownChoice('baycity'))->onChange($this, 'onBayCity');
        $npform->add(new DropDownChoice('baypoint'));
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

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));
        $row->add(new Label('order', $doc->headerdata['order']));
        $row->add(new Label('customer', $doc->customer_name));

        $row->add(new Label('state', Document::getStateName($doc->state)));
        $row->add(new Label('firm', $doc->firm_name));
        $row->add(new Label('ispay'   ))->setVisible(false) ;
        $row->add(new Label('istruck' ))->setVisible(false) ;
        if($doc->state >=4){
           if($doc->payamount > 0 &&  $doc->payamount > $doc->payed)  {
               $row->ispay->setVisible(true);
           }
           if($doc->meta_name=='Invoice') {
               $n = $doc->getChildren('GoodsIssue')+$doc->getChildren('TTN');
               $row->istruck->setVisible(count($n)==0);
               
           }
        }
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state < Document::STATE_EXECUTED) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
        if ($doc->document_id == @$this->_doc->document_id) {
            $row->setAttribute('class', 'table-success');
        }
    }

    public function statusOnSubmit($sender) {
        if (\App\Acl::checkChangeStateDoc($this->_doc, true, true) == false) {
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

            $this->setSuccess('sent');
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
            $this->setSuccess("saved");
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

        //прячем лишнее
        if ($this->_doc->meta_name == 'TTN') {
            if ($this->_doc->headerdata['delivery'] < 3) { //не  служба  доставки
                $this->statuspan->statusform->ship_number->setVisible(false);
                $this->statuspan->statusform->bdecl->setVisible(false);
            }

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
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
        $this->statuspan->statusform->ship_number->setText($this->_doc->headerdata['ship_number']);
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

        $areas = $api->getAreaListCache();
        //   $st = $api->getServiceTypes() ;
        //   $ct = $api->getTypesOfCounterparties() ;
        $pf = $api->getPaymentForms();
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
        $bmlist['Cash'] = 'Наличные';
        $bmlist['NonCash'] = 'Безнал';
        $bmlist['Control'] = 'Контроль доставки';
        $this->nppan->npform->nppmback->setOptionList($bmlist);
        $this->nppan->npform->nppmback->setValue('Cash');
        //кто оплачивает
        $stlist = array();
        foreach ($tp['data'] as $r) {
            $stlist[$r['Ref']] = $r['Description'];
        }

        $this->nppan->npform->nppt->setOptionList($stlist);
        $this->nppan->npform->nppt->setValue('Recipient');

        $this->nppan->npform->bayarea->setOptionList($areas);
        $this->nppan->npform->selarea->setOptionList($areas);

        $this->nppan->npform->selarea->setValue($modules['nparearef']);

        if (strlen($modules['nparearef']) > 0) {
            $this->onSelArea($this->nppan->npform->selarea);
            $this->nppan->npform->selcity->setValue($modules['npcityref']);
        }
        if (strlen($modules['npcityref']) > 0) {
            $this->onSelCity($this->nppan->npform->selcity);
            $this->nppan->npform->selpoint->setValue($modules['nppointref']);
        }
        $this->nppan->npform->seltel->setText($modules['nptel']);
        $this->nppan->npform->npdesc->setText($this->_doc->notes);
      
        $list = $this->_doc->unpackDetails('detaildata');
      
        if(strlen($this->_doc->notes)==0) {
             $desc = "";
             foreach ($list as $it) {
                 $desc .= $it->itemname.","   ;     
             }

             $this->nppan->npform->npdesc->setText(trim($desc,","));
             
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

        $order = Document::load($this->_doc->parent_id);
        if ($order instanceof Document) {
            if ($order->payamount > 0) {
                $this->nppan->npform->npback->setText(round($order->payamount));
                $this->nppan->npform->npcost->setText(round($order->payamount));
            }
        }

        $c = \App\Entity\Customer::load($this->_doc->customer_id);
        $cust = $c->customer_name;
        $tel = '';
        if (strlen($this->_doc->headerdata["phone"]) > 0) {
            $cust = $cust . ', ' . $this->_doc->headerdata["phone"];
            $tel = $this->_doc->headerdata["phone"];
        } else {
            $cust = $cust . ', ' . $cust->phone;
            $tel = $cust->phone;
        }

        $this->nppan->npform->baytel->setText($tel);
        $name = explode(' ', $c->customer_name);
        $this->nppan->npform->baylastname->setText($name[0]);
        $this->nppan->npform->bayfirstname->setText($name[1]);
        $this->nppan->npform->baymiddlename->setText($name[2]);

        $this->nppan->npform->npttncust->setText($cust);
        $this->nppan->npform->npttaddress->setText($this->_doc->headerdata["ship_address"]);
        $this->nppan->npform->npttnnotes->setText($this->_doc->notes);

        $this->nppan->npform->printform->setText($this->_doc->cast()->generateReport(), true);
    }

    public function onSelArea($sender) {

        $api = new \App\Modules\NP\Helper();
        $list = $api->getCityListCache($sender->getValue());

        $this->nppan->npform->selcity->setOptionList($list);
    }

    public function onSelCity($sender) {

        $api = new \App\Modules\NP\Helper();
        $list = $api->getPointListCache($sender->getValue());

        $this->nppan->npform->selpoint->setOptionList($list);
    }

    public function onBayArea($sender) {

        $api = new \App\Modules\NP\Helper();
        $list = $api->getCityListCache($sender->getValue());

        $this->nppan->npform->baycity->setOptionList($list);
    }

    public function onBayCity($sender) {

        $api = new \App\Modules\NP\Helper();
        $list = $api->getPointListCache($sender->getValue());

        $this->nppan->npform->baypoint->setOptionList($list);
    }

    public function npOnCancel($sender) {
        $this->statuspan->setVisible(false);
        $this->listpan->setVisible(true);
        $this->nppan->setVisible(false);
    }

    public function npOnSubmit($sender) {
        $params = array();

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

        $moneyback = $this->nppan->npform->npback->getText();

        if ($moneyback > 0) {   //если  введена  обратная сумма
            $back = $this->nppan->npform->nppmback->getValue();

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
            $this->setError(H::l('npnoweight'));
            return;
        }
        if (strlen($params['Description']) == 0) {
            $this->setError(H::l('npnodesc'));
            return;
        }
        $api = new \App\Modules\NP\Helper();

        $sender = array();
        $recipient = array();

        try {


            $result = $api->model('Counterparty')->getCounterparties("Sender");
            if ($result['success'] == FALSE) {
                $errors = implode(',', $result['errors']);
                $this->setError($errors);
                return;
            }

            $resultc = $api->model('Counterparty')->getCounterpartyContactPersons($result['data'][0]['Ref']);
            if ($resultc['success'] == FALSE) {
                $errors = implode(',', $result['errors']);
                $this->setError($errors);
                return;
            }


            $sender['Sender'] = $result['data'][0]['Ref'];
            $sender['SendersPhone'] = $this->nppan->npform->seltel->getText();
            $sender['CitySender'] = $this->nppan->npform->selcity->getValue();
            //  $sender['Region']= $this->nppan->npform->selarea->getValue();
            //  $sender['Warehouse']= $this->nppan->npform->selpoint->getValue();
            $sender['SenderType'] = $result['data'][0]['CounterpartyType'];
            $sender['ContactSender'] = $resultc['data'][0]['Ref'];
            $sender['SenderAddress'] = $this->nppan->npform->selpoint->getValue();

            $recipient['FirstName'] = $this->nppan->npform->bayfirstname->getText();
            $recipient['MiddleName'] = $this->nppan->npform->baymiddlename->getText();
            $recipient['LastName'] = $this->nppan->npform->baylastname->getText();
            $recipient['Phone'] = $this->nppan->npform->baytel->getText();
            $recipient['Email'] = '';
            $recipient['CounterpartyType'] = 'PrivatePerson';
            $recipient['CounterpartyProperty'] = 'Recipient';

            $result = $api->model('Counterparty')->save($recipient);

            if ($result['success'] == FALSE) {
                $errors = implode(',', $result['errors']);
                $this->setError($errors);
                return;
            }


            $recipient['Recipient'] = $result['data'][0]['Ref'];
            $recipient['ContactRecipient'] = $result['data'][0]['ContactPerson']['data'][0]['Ref'];
            $recipient['CityRecipient'] = $this->nppan->npform->baycity->getValue();
            $recipient['RecipientsPhone'] = $this->nppan->npform->baytel->getValue();
            //     $recipient['Region'] = $this->nppan->npform->bayarea->getValue();
            //    $recipient['Warehouse'] = $this->nppan->npform->baypoint->getValue();
            $recipient['RecipientType'] = $result['data'][0]['CounterpartyType'];
            $recipient['RecipientAddress'] = $this->nppan->npform->baypoint->getValue();

            $paramsInternetDocument = array_merge($sender, $recipient, $params);

            $result = $api->model('InternetDocument')->save($paramsInternetDocument);
        } catch(\Throwable $e) {
            $this->setError($e->getMessage());
            return;
        }
        if ($result['success'] == TRUE) {

            $this->_doc->headerdata['delivery_date'] = strtotime($result['data'][0]['EstimatedDeliveryDate']);
            $this->_doc->headerdata['ship_amount'] = $result['data'][0]['CostOnSite'];
            $this->_doc->headerdata['ship_number'] = $result['data'][0]['IntDocNumber'];
            $this->_doc->headerdata['ship_numberref'] = $result['data'][0]['Ref'];
            $this->_doc->save();
            $this->setSuccess(H::l("npnewdec", $this->_doc->headerdata['ship_number']));


            $order = Document::load($this->_doc->parent_id);
            if ($order instanceof Document) {
                if ($order->state == Document::STATE_READYTOSHIP) {
                    $order->updateStatus(Document::STATE_INSHIPMENT);
                }
            }


            $this->statuspan->setVisible(false);
            $this->listpan->setVisible(true);
            $this->nppan->setVisible(false);
        } else {
            $errors = implode(',', $result['errors']);
            $this->setError($errors);
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

        $conn = \ZDB\DB::getConnect();
   
        $where = "   meta_name  in('GoodsIssue', 'Invoice','POSCheck','ReturnIssue' ,'Warranty','TTN' ) ";

        $salesource = $this->page->listpan->filter->salesource->getValue();
        if ($salesource > 0) {
            $where .= " and   content like '%<salesource>{$salesource}</salesource>%' ";
        }

        $status = $this->page->listpan->filter->status->getValue();
        if ($status == 0) {
            $where .= "  and    state >3 and  state  not in(14,5,9)        ";
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

        $comp = $this->page->listpan->filter->searchcomp->getValue();
        if ($comp > 0) {
            $where = $where . " and firm_id = " . $comp;
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
