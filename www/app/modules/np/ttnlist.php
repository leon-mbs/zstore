<?php

namespace App\Modules\NP;

use App\System;
use App\Helper as H;
use App\Entity\Doc\Document;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;

class TTNList extends \App\Pages\Base
{

    public  $_doclist = array();
    private $_apikey  = '';

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'np') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");
        $this->_apikey = $modules['npapikey'];
        $this->add(new ClickLink('refresh', $this, 'onRefresh'));
        $this->add(new Form('searchform'))->onSubmit($this, 'onFilter');
        $this->searchform->add(new TextInput('searchnumber' ));
        $this->searchform->add(new DropDownChoice('searchcust' ));

        $this->add(new DataView('doclist', new ArrayDataSource($this, '_doclist'), $this, 'doclistOnRow'));

        $this->onRefresh($this->refresh);
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('document_number', $doc->document_number));
        $row->add(new Label('ship_number', $doc->headerdata['ship_number']));
        $row->add(new Label('customer_name', $doc->customer_name));
        $row->add(new Label('amount', H::fa($doc->amount)));
        $row->add(new Label('state', $doc->headerdata['sn_state']));

        $link = "https://my.novaposhta.ua/orders/printMarking100x100/orders[]/" . $doc->headerdata['ship_number'] . "/type/pdf/apiKey/" . $this->_apikey;
        $row->add(new BookmarkableLink('print'))->setLink($link);
    }

    //обновление  статусов
    public function onRefresh($sender) {
        $this->_doclist = array();
        $api = new \App\Modules\NP\Helper();
        $errors = array();
        $docs = Document::find("content like '%<ship_number>%' and  meta_name = 'TTN' and state in(11,20) ");
        $tracks = array();
        foreach ($docs as $ttn) {
            if (strlen($ttn->headerdata['ship_number']) > 0) {
                $tracks[] = $ttn->headerdata['ship_number'];
            }
        }

        $statuses = $api->check($tracks);
        $cnt = 0;
        foreach ($docs as $ttn) {

            $decl = $ttn->headerdata['ship_number'];
            if (strlen($decl) == 0) {
                continue;
            }
          

            $st = $statuses[$decl]['Status'];
            $code = $statuses[$decl]['StatusCode'];
            $ttn->headerdata['sn_state'] = $st;
            // 9,10,11,106 - получено
            //4,5,6,7,8,41,101 - в  пути
            //102,103,104,108,105,2,3  проблемы


            if (in_array($code, array(9, 10, 11, 106))) {
                $ttn->updateStatus(Document::STATE_DELIVERED);
                $cnt++;
            }
            if (in_array($code, array(4, 5, 6, 7, 8, 41, 101))) {
                $ttn->updateStatus(Document::STATE_INSHIPMENT);
                $cnt++;
            }
            if (in_array($code, array(102, 103, 104, 108, 105, 2, 3))) {
                $errors[] = $ttn->headerdata['ship_number'] . " -  " . $st;
            }


            $this->_doclist[$ttn->document_id] = $ttn;
        }
            if (count($errors) > 0) {
                $this->setError(Implode('<br>', $errors));
            } 
            $this->setSuccess("npupdated", $cnt);

        $this->doclist->Reload();
        
        $this->searchform->searchnumber->setText('');
        $c = array();
        
        foreach($this->_doclist as $d) {
           $c[$d->customer_id] =  $d->customer_name;
        }
        
        $this->searchform->searchcust->setOptionList($c);
            
        
    }
    
    public function onFilter($sender) {
        $list = array();
        $cust =   $this->searchform->searchcust->getValue();
        $n =   $this->searchform->searchnumber->getText();
        foreach($this->_doclist as $d) {
           if($cust >0 && $d->customer_id <> $cust) continue;
           if( strlen($n) >0 && $d->headerdata['ship_number'] <> $n) continue;
            
           $list[$d->document_id] = $d;
        }
        $this->_doclist = $list;
        $this->doclist->Reload();
    }

}
