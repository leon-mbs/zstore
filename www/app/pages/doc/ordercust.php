<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\CustItem;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  заявки  поставщику
 */
class OrderCust extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;


    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");


        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->_itemlist = $this->_doc->unpackDetails('detaildata');

        } else {
            $this->_doc = Document::create('OrderCust');
            $this->_doc->document_number = $this->_doc->nextNumber();
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {

                        $order = $basedoc->cast();


                        $this->_itemlist = $basedoc->unpackDetails('detaildata');

                    }
                }
            }
        }

        $this->add(new \App\Widgets\ItemList('wItemList'))->init();


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function save($args, $post) {
        $post = json_decode($post) ;
        if (false == \App\ACL::checkEditDoc($this->_doc, false, false)) {

            return json_encode(['error'=>'Нема прав редагування документу' ], JSON_UNESCAPED_UNICODE);
        }

        $this->_doc->document_number = $post->doc->document_number;
        $this->_doc->document_date = strtotime($post->doc->document_date);
        $this->_doc->notes = $post->doc->notes;
        $this->_doc->customer_id = $post->doc->customer_id;
        $this->_doc->amount = $post->doc->total;

        if (false == $this->_doc->checkUniqueNumber()) {
            return json_encode(['error'=>'Не унікальний номер документу. Створено новий.','newnumber'=>$this->_doc->nextNumber()], JSON_UNESCAPED_UNICODE);
        }

        $i=0;

        $this->_itemlist=[];
        foreach($post->doc->items as $it) {
            $i++;
            $item = Item::load($it->item_id);
            $item->custcode = $it->custcode;
            $item->desc = $it->desc;
            $item->quantity = $it->quantity;
            $item->price = $it->price;
            $item->rowid = $i;


            $this->_itemlist[$i]=$item;
        }
        $this->_doc->packDetails('detaildata', $this->_itemlist);


        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
            $this->_doc->save();

            if ($post->op == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }

                $this->_doc->updateStatus(Document::STATE_INPROCESS);
            } else {
                if ($post->op == 'apprdoc') {
                    if (!$isEdited) {
                        $this->_doc->updateStatus(Document::STATE_NEW);
                    }

                    $this->_doc->updateStatus(Document::STATE_WA);
                } else {
                    $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
                }
            }


            $conn->CommitTrans();


        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return json_encode(['error'=>$ee->getMessage()], JSON_UNESCAPED_UNICODE);


        }

        return json_encode([], JSON_UNESCAPED_UNICODE);

    }

    public function loaddata($args, $post) {

        $ret['doc'] = [];
        $ret['doc']['document_date']   =  date('Y-m-d', $this->_doc->document_date) ;
        $ret['doc']['document_number']   =   $this->_doc->document_number ;
        $ret['doc']['notes']   =   $this->_doc->notes ;
        $ret['doc']['customer_id']   =   $this->_doc->customer_id ;
        $ret['doc']['customer_name']   =   $this->_doc->customer_name ;
        $ret['doc']['items'] = [];
        foreach($this->_itemlist as $item) {
            $ret['doc']['items'][]  = array(
               'item_id'=>$item->item_id,
               'itemname'=>$item->itemname ,
               'custcode'=>$item->custcode ,
               'item_code'=>$item->item_code ,
               'desc'=>$item->desc ,
               'price'=>H::fa($item->price) ,
               'quantity'=>H::fqty($item->quantity) ,
               'amount'=>H::fa($item->quantity * $item->price) ,
               'msr'=>$item->msr
            );
        }


        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }





}
