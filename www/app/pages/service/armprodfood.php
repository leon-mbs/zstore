<?php

namespace App\Pages\Service;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;

use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * АРМ кухни (бара)
 */
class ArmProdFood extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowSer('ArmProdFood')) {
            return;
        }


    }


    public function onReady($args, $post) {


        $doc = Document::load($args[0]);
        $doc = $doc->cast();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {


            $items = $doc->unpackDetails('detaildata');
            if(isset($items[$args[1]])) {
                $items[$args[1]]->foodstate = 3;  //готово
            }

            $doc->packDetails('detaildata', $items);
            $doc->save();


            $isinproces = false;
            foreach ($items as $it) {
                if ($it->foodstate < 3) {
                    $isinproces = true;
                }
            }
            if ($isinproces == false) {    //все  сделаны

                //    $doc->updateStatus(Document::STATE_FINISHED);

                if ($doc->headerdata['delivery'] > 0) {
                    $doc->updateStatus(Document::STATE_READYTOSHIP);

                    $n = new \App\Entity\Notify();
                    $n->user_id = \App\Entity\Notify::DELIV;
                    $n->dateshow = time();

                    $n->message = serialize(array('document_id' => $doc->document_id));

                    $n->save();

                    $doc->DoStore();
                } else {
                    $n = new \App\Entity\Notify();
                    $n->user_id = \App\Entity\Notify::ARMFOOD;
                    $n->dateshow = time();

                    $n->message = serialize(array('document_id' => $doc->document_id));

                    $n->save();




                }

            }


            $conn->CommitTrans();

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();

            $logger->error(" Арм  кухни " . $ee->getMessage());

            return json_encode(['error'=>$ee->getMessage() ], JSON_UNESCAPED_UNICODE);


        }

        return json_encode([], JSON_UNESCAPED_UNICODE);


    }
    public function onInprocess($args, $post) {


        $doc = Document::load($args[0]);
        $doc = $doc->cast();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {


            $items = $doc->unpackDetails('detaildata');
            if(isset($items[$args[1]])) {
                $items[$args[1]]->foodstate = 2;  //в  работу
            }

            $doc->packDetails('detaildata', $items);
            $doc->save();


            $conn->CommitTrans();

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();

            $logger->error(" Арм  кухни " . $ee->getMessage());

            return json_encode(['error'=>$ee->getMessage() ], JSON_UNESCAPED_UNICODE);


        }

        return json_encode([], JSON_UNESCAPED_UNICODE);


    }


    public function onSend($args, $post) {


        $doc = Document::load($args[0]);
        $doc = $doc->cast();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {


            $items = $doc->unpackDetails('detaildata');
            if(isset($items[$args[1]])) {
                $items[$args[1]]->foodstate = 4;  //выдан
            }

            $doc->packDetails('detaildata', $items);
            $doc->save();

            
            $allsend=true;
            foreach ($items as $it) {
                if ($it->foodstate < 4) {
                    $allsend = false;
                }
            }
            
            
            if($allsend==true) {  //все  выданы
               if($doc->payamount <= $doc->payed ) {
                  $doc->updateStatus(Document::STATE_CLOSED);
               }   
            }
            

            $conn->CommitTrans();

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();

            $logger->error(" Арм  кухни " . $ee->getMessage());

            return json_encode(['error'=>$ee->getMessage() ], JSON_UNESCAPED_UNICODE);


        }

        return json_encode([], JSON_UNESCAPED_UNICODE);


    }



    public function getItems($args, $post) {


        $itemlist = array();
        $where = "meta_name='OrderFood' and state in (7) ";
        if($args[0]=="true") {
            $where .= " and content like '%<forbar>1</forbar>%'";
        } else {
            $where .= " and (content like '%<forbar>0</forbar>%' or content not  like '%<forbar>%' ) ";
        }
        

        foreach (Document::findYield($where, "  document_id asc") as $doc) {
            $items = $doc->unpackDetails('detaildata');
            foreach ($items as $rowid=>$item) {
                if ($item->foodstate == 0 || $item->foodstate == 4) {
                    continue;
                }

                $item->ordern = $doc->document_number;
                $item->docnotes = $doc->notes;
                $item->document_id = $doc->document_id;

                $item->del = $doc->headerdata['delivery'] > 0;

                $notes = "";
                if ($item->myself == 1) {
                    $notes = "Із собою";
                }
                if ($item->del == true) {
                    $notes = "Доставка";
                }

                $itemlist[]=array(
                   'ordern'=>$doc->document_number,
                   'notes'=>$notes,
                   'document_id'=>$doc->document_id,
                   'table'=>$doc->headerdata['table'],
                   'name'=>$item->itemname,
                   'foodstate'=>$item->foodstate,
                   'techcard'=>$item->techcard ?? '',
                   'qty'=>$item->quantity,
                   'rowid'=>$rowid,
                   'del'=>$doc->headerdata['delivery'] > 0

                );
            }
        }




        return json_encode($itemlist, JSON_UNESCAPED_UNICODE);
    }
    public function getMessages($args, $post) {

        $text = '';
        $cnt = 0;

        $mlist = \App\Entity\Notify::find("checked <> 1 and user_id=" . \App\Entity\Notify::ARMFOODPROD);
        foreach ($mlist as $n) {
            $msg = @unserialize($n->message);

            if ($msg['cmd'] == 'update') {
                $n->checked = 1;
                $n->save();
                return json_encode(array("update" => true), JSON_UNESCAPED_UNICODE);
            }

            $doc = Document::load(intval($msg['document_id']));


            if ($msg['cmd'] == 'new') {
                if ($doc->state == Document::STATE_INPROCESS) {
                    $cnt++;
                }

            }
        }

        \App\Entity\Notify::markRead(\App\Entity\Notify::ARMFOODPROD);

        return json_encode(array("cnt" => $cnt), JSON_UNESCAPED_UNICODE);
    }


}
