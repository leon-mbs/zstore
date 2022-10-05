<?php

namespace App\API;

use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Helper as H;

class docs extends JsonRPC
{

    //список  статусов
    public function statuslist() {
        $list = \App\Entity\Doc\Document::getStateList();

        return $list;
    }

    //список  филиалов
    public function branchlist() {
        $list = \App\Entity\Branch::findArray('branch_name', '', 'branch_name');

        return $list;
    }

    //список касс и  денежных счетов
    public function mflist() {
        $list = \App\Entity\MoneyFund::getList();

        return $list;
    }


    //записать заказ
    public function createorder($args) {
        $options = \App\System::getOptions('common');

        if (strlen($args['number']) == 0) {
            throw new \Exception(H::l("apinumber"));  //не задан  номер
        }


        $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
        $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");
        $doc = Document::getFirst("  content   like  {$num1} or  content   like  {$num2}  ");
        if ($doc != null) {
            throw new \Exception(H::l("apinumberexists", $args['number']));   //номер уже  существует
        }


        $doc = Document::create('Order');

        if ($args['customer_id'] > 0) {
            $c = \App\Entity\Customer::load($args['customer_id']);
            if ($c == null) {
                throw new \Exception(H::l("apicustnotfound"));
            } else {
                $doc->customer_id = $args['customer_id'];
            }
        }

        if ($options['usebranch'] == 1) {
            if ($args['branch_id'] > 0) {
                $doc->branch_id = $args['branch_id'];
            } else {
                throw new \Exception(H::l("apinobranch"));
            }
        }

        $doc->document_number = $doc->nextNumber();
        if (strlen($neworder->document_number) == 0) {
            $neworder->document_number = 'API00001';
        }
        $doc->document_date = time();

        $doc->headerdata["outnumber"] = $args['number'];
        $doc->headerdata["apinumber"] = $args['number'];
        // $doc->document_number = $args['number'];
        $doc->headerdata["phone"] = $args['phone'];
        $doc->headerdata["email"] = $args['email'];
        $doc->headerdata["ship_address"] = $args['ship_address'];

        $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;
        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {
                if (strlen($it['item_code']) == 0) {
                    throw new \Exception(H::l("apientercode"));
                }
                $item = Item::getFirst("disabled<> 1 and item_code=" . Item::qstr($it['item_code']));

                if ($item instanceof Item) {

                    $item->quantity = $it['quantity'];
                    $item->price = $it['price'];
                    $item->desc = $it['desc'];
                    $item->rowid = $item->item_id;
                    $item->amount = $item->quantity * $item->price;
                    $total = $total + $item->quantity * $item->price;
                    $details[$item->item_id] = $item;
                } else {
                    throw new \Exception(H::l("apiitemnotfound", $it['code']));
                }
            }
        } else {
            throw new \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw new \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);
        if ($args['total'] > 0) {
            $doc->amount = $args['total'];
        } else {
            $doc->amount = $total;
        }

        $doc->payamount = $doc->amount;

        $doc->save();
        $doc->updateStatus(Document::STATE_NEW);
   
        return $doc->document_number;
    }

    //записать ТТН
    public function createttn($args) {

        if (strlen($args['number']) == 0) {
            throw new \Exception(H::l("apinumber"));  //не задан  номер
        }
        $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
        $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");
        $doc = Document::getFirst("  content   like  {$num1} or  content   like  {$num2}  ");
        if ($doc != null) {
            throw new \Exception(H::l("apinumberexists", $args['number']));   //номер уже  существует
        }
        $doc = Document::create('TTN');
        if ($args['customer_id'] > 0) {
            $c = \App\Entity\Customer::load($args['customer_id']);
            if ($c == null) {
                throw new \Exception(H::l("apicustnotfound"));
            } else {
                $doc->customer_id = $args['customer_id'];
                $doc->headerdata['customer_name'] = $c->customer_name;
            }
        }

        $st = \App\Entity\Store::load($args['store_id']);
        if ($st == null) {
            throw new \Exception(H::l("apistorenotfound"));
        } else {
            $doc->headerdata['store'] = $args['store_id'];
            $doc->headerdata['store_name'] = $st->storename;
        }


        $doc->document_number = $doc->nextNumber();
        $doc->document_date = time();

        $doc->headerdata["apinumber"] = $args['number'];
        $doc->headerdata["phone"] = $args['phone'];
        $doc->headerdata["email"] = $args['email'];
        $doc->headerdata["ship_address"] = $args['ship_address'];

        $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;
        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {
                if (strlen($it['item_code']) == 0) {
                    throw new \Exception(H::l("apientercode"));
                }
                $item = Item::getFirst("disabled<> 1 and item_code=" . Item::qstr($it['item_code']));

                if ($item instanceof Item) {
                 
                    $item->quantity = $it['quantity'];
                    $item->price = $it['price'];
                      $item->rowid = $item->item_id;
                  $item->amount = $item->quantity * $item->price;
                    $total = $total + $item->quantity * $item->price;
                    $details[$item->item_id] = $item;
                } else {
                    throw new \Exception(H::l("apiitemnotfound", $it['code']));
                }
            }
        } else {
            throw new \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw new \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);
        if ($args['total'] > 0) {
            $doc->amount = $args['total'];
        } else {
            $doc->amount = $total;
        }

        $doc->payamount = $doc->amount;

        $doc->save();
        $doc->updateStatus(Document::STATE_NEW);

        return $doc->document_number;
    }

    //записать расходную накладную
    public function goodsissue($args) {

        if (strlen($args['number']) == 0) {
            throw new \Exception(H::l("apinumber"));  //не задан  номер
        }
        $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
        $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");
        $doc = Document::getFirst("  content   like  {$num1} or  content   like  {$num2}  ");
        if ($doc != null) {
            throw new \Exception(H::l("apinumberexists", $args['number']));   //номер уже  существует
        }
        $doc = Document::create('GoodsIssue');
        if ($args['customer_id'] > 0) {
            $c = \App\Entity\Customer::load($args['customer_id']);
            if ($c == null) {
                throw new \Exception(H::l("apicustnotfound"));
            } else {
                $doc->customer_id = $args['customer_id'];
                $doc->headerdata['customer_name'] = $c->customer_name;
            }
        }
        $st = \App\Entity\Store::load($args['store_id']);
        if ($st == null) {
            throw new \Exception(H::l("apistorenotfound"));
        } else {
            $doc->headerdata['store'] = $args['store_id'];
            $doc->headerdata['store_name'] = $st->storename;
        }


        $doc->document_number = $doc->nextNumber();
        $doc->document_date = time();

        $doc->headerdata["apinumber"] = $args['number'];
        $doc->headerdata["payment"] = $args['mf'];


        $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;

        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {
                if (strlen($it['item_code']) == 0) {
                    throw new \Exception(H::l("apientercode"));
                }
                $item = Item::getFirst("disabled<> 1 and item_code=" . Item::qstr($it['item_code']));

                if ($item instanceof Item) {

                    $item->quantity = $it['quantity'];
                    $item->price = $it['price'];
                    $item->amount = $item->quantity * $item->price;
                   $item->rowid = $item->item_id;
                     $total = $total + $item->quantity * $item->price;
                    $details[$item->item_id] = $item;
                } else {
                    throw new \Exception(H::l("apiitemnotfound", $it['code']));
                }
            }
        } else {
            throw new \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw new \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);
        if ($args['total'] > 0) {
            $doc->amount = $args['total'];
        } else {
            $doc->amount = $total;
        }

        $doc->payamount = $doc->amount;
        $doc->payed = $args["payed"];

        $doc->save();
        $doc->updateStatus(Document::STATE_NEW);
        
        if ($args["autoexec"] == true) {
            $doc->updateStatus(Document::STATE_EXECUTED);
        }


        return $doc->document_number;
    }

    //записать приходную накдадную
    public function goodsreceipt($args) {

        if (strlen($args['number']) == 0) {
            throw new \Exception(H::l("apinumber"));  //не задан  номер
        }
        $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
        $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");
        $doc = Document::getFirst("  content   like  {$num1} or  content   like  {$num2}  ");
        if ($doc != null) {
            throw new \Exception(H::l("apinumberexists", $args['number']));   //номер уже  существует
        }
        $doc = Document::create('GoodsReceipt');

        $c = \App\Entity\Customer::load($args['customer_id']);
        if ($c == null) {
            throw new \Exception(H::l("apicustnotfound"));
        } else {
            $doc->customer_id = $args['customer_id'];
            $doc->headerdata['customer_name'] = $c->customer_name;
        }

        $st = \App\Entity\Store::load($args['store_id']);
        if ($st == null) {
            throw new \Exception(H::l("apistorenotfound"));
        } else {
            $doc->headerdata['store'] = $args['store_id'];
            $doc->headerdata['store_name'] = $st->storename;
        }


        $doc->document_number = $doc->nextNumber();
        $doc->document_date = time();

        $doc->headerdata["apinumber"] = $args['number'];
        $doc->headerdata["payment"] = $args['mf'];
        $doc->headerdata["nds"] = 0;
        $doc->headerdata["disc"] = 0;


        $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;

        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {
                if (strlen($it['item_code']) == 0) {
                    throw new \Exception(H::l("apientercode"));
                }
                $item = Item::getFirst("disabled<> 1 and item_code=" . Item::qstr($it['item_code']));

                if ($item instanceof Item) {

                    $item->quantity = $it['quantity'];
                    $item->price = $it['price'];
                    $item->rowid = $item->item_id;
                    $item->amount = $item->quantity * $item->price;
                    $total = $total + $item->quantity * $item->price;
                    $details[$item->item_id] = $item;
                } else {
                    throw new \Exception(H::l("apiitemnotfound", $it['code']));
                }
            }
        } else {
            throw new \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw new \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);
        if ($args['total'] > 0) {
            $doc->amount = $args['total'];
        } else {
            $doc->amount = $total;
        }

        $doc->payamount = $doc->amount;
        $doc->payed = $args["payed"];

        $doc->save();
        $doc->updateStatus(Document::STATE_NEW);
        
        if ($args["autoexec"] == true) {
            $doc->updateStatus(Document::STATE_EXECUTED);
        }


        return $doc->document_number;
    }

//записать  оприходование  ТМЦ
    public function incomeitem($args) {

        if (strlen($args['number']) == 0) {
            throw new \Exception(H::l("apinumber"));  //не задан  номер
        }
        $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
        $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");
        $doc = Document::getFirst("  content   like  {$num1} or  content   like  {$num2}  ");
        if ($doc != null) {
            throw new \Exception(H::l("apinumberexists", $args['number']));   //номер уже  существует
        }
        $doc = Document::create('IncomeItem');


        $st = \App\Entity\Store::load($args['store_id']);
        if ($st == null) {
            throw new \Exception(H::l("apistorenotfound"));
        } else {
            $doc->headerdata['store'] = $args['store_id'];
            $doc->headerdata['store_name'] = $st->storename;
        }


        $doc->document_number = $doc->nextNumber();
        $doc->document_date = time();
        $doc->headerdata["apinumber"] = $args['number'];


        $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;

        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {
                if (strlen($it['item_code']) == 0) {
                    throw new \Exception(H::l("apientercode"));
                }
                $item = Item::getFirst("disabled<> 1 and item_code=" . Item::qstr($it['item_code']));

                if ($item instanceof Item) {

                    $item->quantity = $it['quantity'];
                    $item->price = $it['price'];
                   $item->rowid = $item->item_id;
                     $item->amount = $item->quantity * $item->price;
                    $total = $total + $item->quantity * $item->price;
                    $details[$item->item_id] = $item;
                } else {
                    throw new \Exception(H::l("apiitemnotfound", $it['code']));
                }
            }
        } else {
            throw new \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw new \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);
        if ($args['total'] > 0) {
            $doc->amount = $args['total'];
        } else {
            $doc->amount = $total;
        }


        $doc->save();
        $doc->updateStatus(Document::STATE_NEW);
        
        if ($args["autoexec"] == true) {
            $doc->updateStatus(Document::STATE_EXECUTED);
        }


        return $doc->document_number;
    }

    // проверка  статусов документов по  списку  номеров
    public function checkstatus($args) {
        $list = array();

        if (!is_array($args['numbers'])) {
            throw new \Exception(H::l("apiinvalidparameters"));
        }
        foreach ($args['numbers'] as $num) {
            $num1 = Document::qstr("%<apinumber>{$num}</apinumber>%");
            $num2 = Document::qstr("%<apinumber><![CDATA[{$num}]]></apinumber>%");
            $doc = Document::getFirst("  content   like  {$num1} or content   like  {$num2}  ");
            if ($doc instanceof Document) {
                $list[] = array(
                    "number"     => $num,
                    "status"     => $doc->state,
                    "statusname" => Document::getStateName($doc->state)
                );
            }
        }

        return $list;
    }

    //запрос на  отмену
    public function cancel($args) {
        $doc = null;
        if (strlen($args['number']) > 0) {
            $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
            $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");

            $doc = Document::getFirst(" content like {$num1}  or content like {$num2} ");
        }
        if ($doc == null) {
            throw new \Exception(H::l("apinodoc"));
        }

        $user = \App\System::getUser();
        $admin = \App\Entity\User::getByLogin('admin');
        $n = new \App\Entity\Notify();
        $n->user_id = $admin->user_id;
        $n->sender_id = $user->user_id;

        $n->dateshow = time();
        $n->message = H::l("apiasccancel", $doc->document_number, $args['reason']);
        $n->save();
    }

    // /api/docs
    // {"jsonrpc": "2.0", "method": "createprodissue", "params": { "store_id":"1","parea":"1","items":[{"item_code":"cbs500-1","quantity":2.1},{"item_code":"ID0018","quantity":2}] }, "id": 1}
    //Списание ТМЦ в  производсво
    public function createprodissue($args) {


        if ($args['store_id'] > 0) {
            $store = \App\Entity\Store::load($args['store_id']);
            if ($store == null) {
                throw new \Exception('Не  указан  склад');
            }
        }
        $doc = Document::create('ProdIssue');
        $doc->document_number = $doc->nextNumber();
        $doc->document_date = time();
        $doc->headerdata['store'] = $args['store_id'];
        $doc->headerdata['parea'] = $args['parea'];


        $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;
        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {
                if (strlen($it['item_code']) == 0) {
                    throw new \Exception(H::l("apientercode"));
                }
                $item = Item::getFirst("disabled<> 1 and item_code=" . Item::qstr($it['item_code']));

                if ($item instanceof Item) {

                    $item->quantity = $it['quantity'];
                          $item->rowid = $item->item_id;
 
                    $details[$item->item_id] = $item;
                } else {
                    throw new \Exception(H::l("apiitemnotfound", $it['code']));
                }
            }
        } else {
            throw new \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw new \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);

        $doc->amount = 0;

        $doc->save();
        $doc->updateStatus(Document::STATE_NEW);

        $doc->updateStatus(Document::STATE_EXECUTED);

        return $doc->document_number;
    }

    //записать акт выполненых работ
    public function serviceact($args) {

        if (strlen($args['number']) == 0) {
            throw new \Exception(H::l("apinumber"));  //не задан  номер
        }
        $num1 = Document::qstr("%<apinumber>{$args['number']}</apinumber>%");
        $num2 = Document::qstr("%<apinumber><![CDATA[{$args['number']}]]></apinumber>%");
        $doc = Document::getFirst("  content   like  {$num1} or  content   like  {$num2}  ");
        if ($doc != null) {
            throw new \Exception(H::l("apinumberexists", $args['number']));   //номер уже  существует
        }
        $doc = Document::create('ServiceAct');
        if ($args['customer_id'] > 0) {
            $c = \App\Entity\Customer::load($args['customer_id']);
            if ($c == null) {
                throw new \Exception(H::l("apicustnotfound"));
            } else {
                $doc->customer_id = $args['customer_id'];
                $doc->headerdata['customer_name'] = $c->customer_name;
            }
        }


        $doc->document_number = $doc->nextNumber();
        $doc->document_date = time();

        $doc->headerdata["apinumber"] = $args['number'];
        $doc->headerdata["payment"] = $args['mf'];
        $doc->headerdata["device"] = $args['device'];


        //    $doc->notes = @base64_decode($args['description']);
        $details = array();
        $total = 0;

        if (is_array($args['items']) && count($args['items']) > 0) {
            foreach ($args['items'] as $it) {

                $item = \App\Entity\Service::load($it['service_id']);

                if ($item instanceof \App\Entity\Service) {

                    $item->quantity = $it['quantity'];
                    $item->price = $it['price'];
                    $item->rowid = $item->item_id;
                    $item->amount = $item->quantity * $item->price;
                    $total = $total + $item->quantity * $item->price;
                    $details[$item->service_id] = $item;
                } else {
                    throw new \Exception(H::l("apiitemnotfound", $it['service_id']));
                }
            }
        } else {
            throw new \Exception(H::l("apinoitems"));
        }
        if (count($details) == 0) {
            throw new \Exception(H::l("apinoitems"));
        }
        $doc->packDetails('detaildata', $details);
        if ($args['total'] > 0) {
            $doc->amount = $args['total'];
        } else {
            $doc->amount = $total;
        }

        $doc->payamount = $doc->amount;
        $doc->payed = $args["payed"];

        $doc->save();
        $doc->updateStatus(Document::STATE_NEW);
        
        if ($args["autoexec"] == true) {
            $doc->updateStatus(Document::STATE_INPROCESS);
        }


        return $doc->document_number;
    }

}
