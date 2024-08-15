<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\System;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\MoneyFund;
use App\Entity\Service;
use App\Entity\Item;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  акта выполненных работ
 */
class ServiceAct extends \App\Pages\Base
{
    private $_doc;
    private $_basedocid   = 0;

    /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        
        $this->add(new \App\Widgets\ItemList("itemsel"))->init();
        
        $common = System::getOptions("common");

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();

        } else {
            $this->_doc = Document::create('ServiceAct');
            $this->_doc->document_number = $this->_doc->nextNumber();
            $this->_doc->document_date = time();
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid)->cast();
                $this->_doc->document_date = time();
                $this->_doc->customer_id = $basedoc->customer_id;
                $this->_doc->firm_id = $basedoc->firm_id;
                $this->_doc->customer_id = $basedoc->customer_id;

                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'ServiceAct') {

                        $this->_doc->headerdata['detaildata'] = $basedoc->headerdata['detaildata'];
                        $this->_doc->headerdata['detail2data'] = $basedoc->headerdata['detail2data'];


                    }
                }
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    $list =[];
                    if ($basedoc->meta_name == 'Task') {
                        $i=0;
                        foreach($basedoc->unpackDetails('detaildata') as $v) {
                            if($v->service_id>0) {
                                $list[++$i] = $v ;
                            }
                        }
                        $this->_doc->packDetails('detaildata', $list);
                    }
                }
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Invoice') {
                        $i=0;
                        $list=[];
                        foreach($basedoc->unpackDetails('detaildata') as $v) {
                            if($v->service_id>0) {
                                $list[++$i] = $v ;
                            }
                        }
                        $this->_doc->packDetails('detaildata', $list);
                        $i=0;
                        $list=[];
                        foreach($basedoc->unpackDetails('detaildata') as $v) {
                            if($v->item_id>0) {
                                $list[++$i] = $v ;
                            }
                        }
                        $this->_doc->packDetails('detail2data', $list);

                    }
                }
            }
        }
    }

    public function loaddata($args, $post) {

        if (false == \App\ACL::checkShowDoc($this->_doc, false, false)) {

            return json_encode(['error'=>'Нема прав на  доступ до документу' ], JSON_UNESCAPED_UNICODE);
        }
        $common = System::getOptions("common");


        $ret =[];
        
        $ret['options'] = [];        
        $ret['options']['usesnumber']   =  $common['usesnumber'] ;
        
        $ret['doc'] = [];
        $ret['doc']['document_date']   =  date('Y-m-d', $this->_doc->document_date) ;
        $ret['doc']['document_number']   =   $this->_doc->document_number ;
        $ret['doc']['notes']   =   $this->_doc->notes ;
        $ret['doc']['firm_id']   =   $this->_doc->firm_id ?? 0;
        $ret['doc']['customer_id']   =   $this->_doc->customer_id ?? 0;
        $ret['doc']['customer_name']   =   $this->_doc->customer_name ;
        $ret['doc']['store']   =   $this->_doc->headerdata['store'] ?? 0;
        $ret['doc']['contract_id']   =   $this->_doc->headerdata['contract_id'] ?? 0;
        $ret['doc']['device']   =   $this->_doc->headerdata['device'] ?? '';
        $ret['doc']['devsn']   =   $this->_doc->headerdata['devsn'] ?? '';
        $ret['doc']['devdesc']   =   $this->_doc->headerdata['devdesc'] ?? '';
        $ret['doc']['gar']   =   $this->_doc->headerdata['gar'] ?? '';
        $ret['doc']['amount']   = H::fa($this->_doc->amount);
        $ret['doc']['payamount']   = H::fa($this->_doc->payamount);
        $ret['doc']['paydisc']   = H::fa($this->_doc->headerdata['paydisc']??0);
        $ret['doc']['payment']   = H::fa($this->_doc->headerdata['payment']??0);
        $ret['doc']['payed']   = H::fa($this->_doc->headerdata['payed']??0);
        $ret['doc']['totaldisc']   = H::fa($this->_doc->headerdata['totaldisc']??0);
        $ret['doc']['bonus']   = H::fa($this->_doc->headerdata['bonus']??0);

        $ret['doc']['services'] = [];
        $servicelist =  $this->_doc->unpackDetails('detaildata') ;
        foreach($servicelist as $ser) {
            $ret['doc']['services'][]  = array(
               'service_id'=>$ser->service_id,
               'service_name'=>$ser->service_name ,
               'desc'=>$ser->desc ,
               'price'=>H::fa($ser->price) ,
               'disc'=>$ser->disc ,
               'msr'=>$ser->msr ,
               'pureprice'=>$ser->pureprice ,

               'quantity'=>H::fqty($ser->quantity) ,
               'amount'=>H::fa(doubleval($ser->quantity) * doubleval($ser->price))

            );
        }

        $ret['doc']['items'] = [];
        $itemlist =  $this->_doc->unpackDetails('detail2data') ;
        foreach($itemlist as $item) {
            $ret['doc']['items'][]  = array(
               'item_id'=>$item->item_id,
               'itemname'=>$item->itemname ,
               'item_code'=>$item->item_code ,
               'price'=>H::fa($item->price) ,
               'disc'=>$item->disc ,
               'msr'=>$item->msr ,
               'snumber'=>$item->snumber ,
               'pureprice'=>$item->pureprice ,

               'quantity'=>H::fqty($item->quantity) ,
               'amount'=>H::fa($item->quantity * $item->price)

            );
        }
        //для  комбобокса
        $ret['servicelist'] = \App\Util::tokv(\App\Entity\Service::getList()) ;

        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }


    public function save($args, $post) {
        $post = json_decode($post) ;
        if (false == \App\ACL::checkEditDoc($this->_doc, false, false)) {

            return json_encode(['error'=>'Нема прав редагування документу' ], JSON_UNESCAPED_UNICODE);
        }

        $this->_doc->document_number = $post->doc->document_number;
        $this->_doc->document_date = strtotime($post->doc->document_date);
        $this->_doc->notes = $post->doc->notes;
        $this->_doc->firm_id = $post->doc->firm_id;
        $this->_doc->customer_id = $post->doc->customer_id;
        if($this->_doc->customer_id >0) {
            $c = \App\Entity\Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customerphone'] = $c->phone;
        }

        $this->_doc->amount = $post->doc->total;
        $this->_doc->payamount = $post->doc->payamount;
        $this->_doc->payed = $post->doc->payed;
        $this->_doc->headerdata['payed'] = $post->doc->payed;
        $this->_doc->headerdata['store'] = $post->doc->store;
        $this->_doc->headerdata['devsn'] = $post->doc->devsn;
        $this->_doc->headerdata['devdesc'] = $post->doc->devdesc;
        $this->_doc->headerdata['device'] = $post->doc->device;
        $this->_doc->headerdata['gar'] = $post->doc->gar;
        $this->_doc->headerdata['contract_id'] = $post->doc->contract_id;
        $this->_doc->headerdata['payment'] = $post->doc->payment;
        $this->_doc->headerdata['totaldisc'] = $post->doc->totaldisc;
        $this->_doc->headerdata['bonus'] = $post->doc->bonus;

        if (false == $this->_doc->checkUniqueNumber()) {
            return json_encode(['error'=>'Не унікальний номер документу. Створено новий.','newnumber'=>$this->_doc->nextNumber()], JSON_UNESCAPED_UNICODE);
        }


        $i=0;

        $itemlist=[];
        foreach($post->doc->items as $it) {
            $i++;
            $item = Item::load($it->item_id);

            $item->quantity = $it->quantity;
            $item->disc = $it->disc;
            $item->price = $it->price;
            $item->pureprice = $it->pureprice;
            $item->snumber = $it->snumber;
            $item->rowid = $i;

            $itemlist[$i]=$item;
        }
        $this->_doc->packDetails('detail2data', $itemlist);

        $i=0;
        $servicelist=[];
        foreach($post->doc->services as $s) {
            $i++;
            $ser = Service::load($s->service_id);

            $ser->quantity = $s->quantity;
            $ser->disc = $s->disc;
            $ser->desc = $s->desc;
            $ser->price = $s->price;
            $ser->pureprice = $s->pureprice;
            $ser->rowid = $i;

            $servicelist[$i]=$ser;
        }

        $this->_doc->packDetails('detaildata', $servicelist);
        $isEdited = $this->_doc->document_id >0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }


            $this->_doc->save();

            if ($post->op != 'savedoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }


                if ($post->op == 'execdoc' || $post->op == 'paydoc') {
                    
                    $this->_doc->headerdata['timeentry'] = time();
                    $this->_doc->updateStatus(Document::STATE_INPROCESS);
                }
                if (  $post->op == 'paydoc') {
                     $this->_doc->updateStatus(Document::STATE_WP);
                }


            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();


        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );

            return json_encode(['error'=>$ee->getMessage()], JSON_UNESCAPED_UNICODE);


        }

        return json_encode([], JSON_UNESCAPED_UNICODE);

    }
}
