<?php

namespace App\Entity;

/**
 * Клас-сущность  ТМЦ
 *
 * @table=items
 * @view=items_view
 * @keyfield=item_id
 */
class Item extends \ZCL\DB\Entity
{
    public const TYPE_TOVAR    = 1;   //товар
    public const TYPE_MAT      = 2;   //материалы  и комплектующие
    public const TYPE_MBP      = 3;   //малоценка
    public const TYPE_PROD     = 4;   //готовая продукция
    public const TYPE_HALFPROD = 5;   //полуфабрикаты

    private $brprice = array(); //цены по  филиалам

    protected function init() {
        $this->item_id = 0;
        $this->cat_id = 0;
        $this->item_type = self::TYPE_TOVAR;
        $this->msr = "шт";
        $this->notes = "";

        $this->price = 0;
        $this->image_id = 0;
        $this->noprice = 0;
        $this->noshop = 0;
        $this->foodstate = 0;
        $this->reclist = array();
        $this->printqty = 1;      
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->price1 = (string)($xml->price1[0]);
        $this->price2 = (string)($xml->price2[0]);
        $this->price3 = (string)($xml->price3[0]);
        $this->price4 = (string)($xml->price4[0]);
        $this->price5 = (string)($xml->price5[0]);
        $this->rate = (string)($xml->rate[0]);
        $this->val = (string)($xml->val[0]);
        $this->zarp = (string)($xml->zarp[0]);
        $this->costprice = (string)($xml->costprice[0]);
        $this->thumb = (string)($xml->thumb[0]);

        $this->isweight = (int)$xml->isweight[0];
        $this->noprice = (int)$xml->noprice[0];
        $this->noshop = (int)$xml->noshop[0];
        $this->autooutcome = (int)$xml->autooutcome[0];
        $this->autoincome = (int)$xml->autoincome[0];
        $this->useserial = (int)$xml->useserial[0];
        $this->image_id = (int)$xml->image_id[0];
        $this->imageurl = (string)$xml->imageurl[0];

        $this->techcard = (string)$xml->techcard[0];
        $this->weight = (string)$xml->weight[0];
        $this->maxsize = (string)$xml->maxsize[0];
        $this->volume = (string)$xml->volume[0];
        $this->lost = (string)$xml->lost[0];
        $this->customsize = (string)$xml->customsize[0];
        $this->manufacturer = (string)$xml->manufacturer[0];
        $this->shortname = (string)$xml->shortname[0];
        $this->warranty = (string)$xml->warranty[0];
        $this->snumber = (string)$xml->snumber[0];
        $this->extdata = (string)$xml->extdata[0];
        $this->sef = (string)$xml->sef[0];
        $this->url = (string)$xml->url[0];
        $this->country = (string)$xml->country[0];
        $this->notes = (string)$xml->notes[0];
        $this->bar_code1 = (string)$xml->bar_code1[0];
        $this->bar_code2 = (string)$xml->bar_code2[0];
        $this->cflist = (string)$xml->cflist[0];
        $reclist = (string)$xml->reclist[0];
     

        if(strlen($reclist) >0) {
            $this->reclist = @unserialize(@base64_decode($reclist))   ;
        }


        $this->uktz = (string)$xml->uktz[0];
        $this->cell = (string)$xml->cell[0];
        //  $this->octoreoptions = (string) $xml->octoreoptions[0];
        $brprice = (string)$xml->brprice[0];

        $this->brprice = @unserialize($brprice);
        if (!is_array($this->brprice)) {
            $this->brprice = array();
        }

        $id = \App\System::getBranch();
        if ($id > 0 && is_array($this->brprice[$id]??null)) {
            $this->price1 = $this->brprice[$id]['price1'];
            $this->price2 = $this->brprice[$id]['price2'];
            $this->price3 = $this->brprice[$id]['price3'];
            $this->price4 = $this->brprice[$id]['price4'];
            $this->price5 = $this->brprice[$id]['price5'];
        }

        $this->actionqty1 = doubleval($xml->actionqty1[0]);
        $this->actionprice1 = doubleval($xml->actionprice1[0]);
        $this->actionqty2 = doubleval($xml->actionqty2[0]);
        $this->actionprice2 = doubleval($xml->actionprice2[0]);
        $this->actionprice = doubleval($xml->actionprice[0]);
        $this->actiondisc = doubleval($xml->actiondisc[0]);
        $this->todate = intval($xml->todate[0]);
        $this->fromdate = intval($xml->fromdate[0]);
        $this->printqty = intval($xml->printqty[0]);

       
        
        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        
        $this->itemname = str_replace("'","`",$this->itemname) ;
        $this->itemname = str_replace("\"","`",$this->itemname) ;
        $this->shortname = str_replace("'","`",$this->shortname) ;
        $this->shortname = str_replace("\"","`",$this->shortname) ;
         
        
        $fid = \App\System::getBranch();
        if ($fid > 0) {
            $this->brprice[$fid] = array('price1' => $this->price1, 'price2' => $this->price2, 'price3' => $this->price3, 'price4' => $this->price4, 'price5' => $this->price5);
            $prev = self::load($this->item_id); //востанавливаем  предыдущую цену
            $this->price1 = $prev->price1;
            $this->price2 = $prev->price2;
            $this->price3 = $prev->price3;
            $this->price4 = $prev->price4;
            $this->price5 = $prev->price5;
        }
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<isweight>{$this->isweight}</isweight>";
        $this->detail .= "<noprice>{$this->noprice}</noprice>";
        $this->detail .= "<noshop>{$this->noshop}</noshop>";
        $this->detail .= "<autooutcome>{$this->autooutcome}</autooutcome>";
        $this->detail .= "<autoincome>{$this->autoincome}</autoincome>";
        $this->detail .= "<useserial>{$this->useserial}</useserial>";

        $this->detail .= "<uktz>{$this->uktz}</uktz>";
        $this->detail .= "<cell>{$this->cell}</cell>";
        //   $this->detail .= "<octoreoptions><![CDATA[{$this->octoreoptions}]]></octoreoptions>";
        $this->detail .= "<manufacturer><![CDATA[{$this->manufacturer}]]></manufacturer>";
        $this->detail .= "<shortname><![CDATA[{$this->shortname}]]></shortname>";
        $this->detail .= "<warranty><![CDATA[{$this->warranty}]]></warranty>";
        $this->detail .= "<snumber><![CDATA[{$this->warranty}]]></snumber>";
        $this->detail .= "<extdata><![CDATA[{$this->extdata}]]></extdata>";
        $this->detail .= "<country><![CDATA[{$this->country}]]></country>";
        $this->detail .= "<notes><![CDATA[{$this->notes}]]></notes>";
        $this->detail .= "<bar_code1><![CDATA[{$this->bar_code1}]]></bar_code1>";
        $this->detail .= "<bar_code2><![CDATA[{$this->bar_code2}]]></bar_code2>";
        $this->detail .= "<techcard><![CDATA[{$this->techcard}]]></techcard>";

        $this->detail .= "<price1>{$this->price1}</price1>";
        $this->detail .= "<price2>{$this->price2}</price2>";
        $this->detail .= "<price3>{$this->price3}</price3>";
        $this->detail .= "<price4>{$this->price4}</price4>";
        $this->detail .= "<price5>{$this->price5}</price5>";
        $this->detail .= "<val>{$this->val}</val>";
        $this->detail .= "<rate>{$this->rate}</rate>";
        $this->detail .= "<zarp>{$this->zarp}</zarp>";
        $this->detail .= "<costprice>{$this->costprice}</costprice>";
        $this->detail .= "<thumb>{$this->thumb}</thumb>";

        $this->detail .= "<image_id>{$this->image_id}</image_id>";
        $this->detail .= "<weight>{$this->weight}</weight>";
        $this->detail .= "<maxsize>{$this->maxsize}</maxsize>";
        $this->detail .= "<volume>{$this->volume}</volume>";
        $this->detail .= "<lost>{$this->lost}</lost>";
        $this->detail .= "<customsize>{$this->customsize}</customsize>";
        $this->detail .= "<sef>{$this->sef}</sef>";
        $this->detail .= "<url>{$this->url}</url>";
        $this->detail .= "<foodstate>{$this->foodstate}</foodstate>";
        $this->detail .= "<state>{$this->state}</state>";
        $this->detail .= "<cflist>{$this->cflist}</cflist>";
        $this->detail .= "<imageurl>{$this->imageurl}</imageurl>";

        //упаковываем  цены  по  филиалам
        $brprice = serialize($this->brprice);

        $this->detail .= "<brprice><![CDATA[{$brprice}]]></brprice>";

        if ($this->actionprice > 0) {
            $this->detail .= "<actionprice>{$this->actionprice}</actionprice>";
        }
        if ($this->actiondisc > 0) {
            $this->detail .= "<actiondisc>{$this->actiondisc}</actiondisc>";
        }
        if ($this->actionqty1 > 1) {
            $this->detail .= "<actionqty1>{$this->actionqty1}</actionqty1>";
            $this->detail .= "<actionprice1>{$this->actionprice1}</actionprice1>";
            if ($this->actionqty2 > $this->actionqty1) {
                $this->detail .= "<actionqty2>{$this->actionqty2}</actionqty2>";
                $this->detail .= "<actionprice2>{$this->actionprice2}</actionprice2>";
            }
        }
        $this->detail .= "<todate>{$this->todate}</todate>";
        $this->detail .= "<fromdate>{$this->fromdate}</fromdate>";
        $this->detail .= "<printqty>{$this->printqty}</printqty>";
        if(count($this->reclist) > 0) {
            $this->detail .= "<reclist>";
            $ss =    serialize($this->reclist) ;
            $this->detail .= base64_encode(serialize($this->reclist));
            $this->detail .= "</reclist>";
        }


        $this->detail .= "</detail>";

        return true;
    }

    public function allowDelete() {

        $conn = \ZDB\DB::getConnect();
        //проверка на партии
        $sql = "  select count(*)  from  store_stock where   item_id = {$this->item_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? false : true;
    }

    protected function afterDelete() {

        foreach(\App\Entity\ItemSet::find("item_id = {$this->item_id} or  pitem_id={$this->item_id} ") as $is) {
            \App\Entity\ItemSet::delete($is->set_id) ;
        }

        if ($this->image_id > 0) {
            \App\Entity\Image::delete($this->image_id);
        }
        
        \App\Entity\Tag::updateTags([],   \App\Entity\Tag::TYPE_ITEM,$this->item_id) ;
        
    }

    //Вычисляет  отпускную цену без скидок
    //$_price - цифра (заданая цена) или  наименование  цены из настроек
    //$store - склад
    //$partion - партия
    public function getPurePrice($_price_ = 'price1', $store = 0, $partion = 0) {
        $price = 0;
        $_price = 0;
        $common = \App\System::getOptions("common");
        if (strlen($common[$_price_]??'') == 0) {
            return 0;
        }


        if ($_price_ == 'price1') {
            $_price = $this->price1;
        } else {
            if ($_price_ == 'price2') {
                $_price = $this->price2;
            } else {
                if ($_price_ == 'price3') {
                    $_price = $this->price3;
                } else {
                    if ($_price_ == 'price4') {
                        $_price = $this->price4;
                    } else {
                        if ($_price_ == 'price5') {
                            $_price = $this->price5;
                        }
                    }
                }
            }
        }


        //если процент
        if (strpos($_price, '%') > 0) {

            $ret = doubleval(str_replace('%', '', $_price));
            if (is_numeric($ret)) {
                if ($partion == 0) {
                    //ищем последнюю закупочную  цену
                    $partion = $this->getLastPartion($store,"",true);
                }
                $price = $partion + doubleval($partion) / 100 * $ret;

            }
        } else {
            if ($_price > 0) {
                $price = $_price; //задана  просто  цифра
            }
        }

        if ($price == 0 && $this->cat_id > 0) {
            $cat = \App\Entity\Category::load($this->cat_id);
            if ($cat != null) {
                if ($partion == 0) {
                    //ищем последнюю закупочную  цену
                    $partion = $this->getLastPartion($store,"",true);
                }
                if ($_price_ == 'price1' && $cat->price1 > 0) {
                    $price = $partion + doubleval($partion)  / 100 * $cat->price1;
                }
                if ($_price_ == 'price2' && $cat->price2 > 0) {
                    $price = $partion + doubleval($partion)  / 100 * $cat->price2;
                }
                if ($_price_ == 'price3' && $cat->price3 > 0) {
                    $price = $partion + doubleval($partion)  / 100 * $cat->price3;
                }
                if ($_price_ == 'price4' && $cat->price4 > 0) {
                    $price = $partion + doubleval($partion)  / 100 * $cat->price4;
                }
                if ($_price_ == 'price5' && $cat->price5 > 0) {
                    $price = $partion + doubleval($partion)  / 100 * $cat->price5;
                }


            }
        }


        //если не  задано используем глобальную наценку
        if ($common['defprice'] > 0 && $price == 0) {

            if ($partion == 0) {
                //ищем последнюю закупочную  цену
                $partion = $this->getLastPartion($store,"",true);
            }

            $price = $partion + (doubleval($partion) / 100) * $common['defprice'];

        }


        //если  не  задана  наценка и цена  то  берем  закупочную
        /*
        if (intval($common['defprice']) == 0 && $price == 0) {

            if ($partion == 0) {
                //ищем последнюю закупочную  цену
                $partion = $this->getLastPartion($store,"",true);
            }
            $price =  $partion;


        }
         */
        //курсовая разница
        if($common['useval']==1) {
            $opv = \App\System::getOptions("val");
            if (strlen($this->val) > 1 && $opv['valprice'] == 1) {

                foreach($opv['vallist'] as $v) {
                    if($v->code==$this->val) {
                        $k = $v->rate / $this->rate;
                        $price = $price * $k;
                    }
                }

            }
        }
        return $price;
    }

    public function hasAction($date=0) {
        $date = intval($date) ;
        if($date==0) {
            $date=time();
        }
        if(doubleval($this->actionqty1) > 0) {
            return true;
        }

        if (doubleval($this->actionprice) > 0 || doubleval($this->actiondisc > 0)) {

            if (intval($this->fromdate) < $date && intval($this->todate) > $date) {
                return true;
            }

        }

        return false;
    }



    /**
    * цена  со  скидкой по  количксиву
    * возвращает null если  нет  акции
    * @param mixed $qty
    */
    public function getActionPriceByQuantity($qty) {
        //по  количеству
        if ($this->actionprice2 >0 && doubleval($this->actionqty2) <= $qty && $qty>1) {
            return $this->actionprice2;
        }
        if ($this->actionprice1 >0 &&  doubleval($this->actionqty1) <= $qty && $qty>1) {
            return $this->actionprice1;
        }


        return null;
    }

    //цена  со  скидкой
    public function getActionPrice($qty=0) {
        //по  количеству
        if ($this->actionprice2 >0 && doubleval($this->actionqty2) <= $qty && $qty>1) {
            return $this->actionprice2;
        }
        if ($this->actionprice1 >0 &&  doubleval($this->actionqty1) <= $qty && $qty>1) {
            return $this->actionprice1;
        }

        //акционная цена
        if (doubleval($this->actionprice) > 0) {
            if (intval($this->fromdate) < time() && intval($this->todate) > time()) {
                return $this->actionprice;
            }
        }

        // скидка
        $price = $this->getPurePrice() ;
        if (doubleval($this->actiondisc) > 0 && intval($this->fromdate) < time() && intval($this->todate) > time()) {   //по  категории
            return  \App\Helper::fa1($price - $price * $this->actiondisc / 100);
        }

        return $price;

    }

    //цена  со  скидками (если  есть)
    public function getPrice($_price_ = 'price1', $store = 0, $partion = 0, $qty=0) {
        if(strlen($_price_)==0) {
            $_price_ = 'price1';
        }
        $price = $this->getPurePrice($_price_, $store, $partion);
        if ($this->hasAction() && $_price_ == 'price1') {
            $price = $this->getActionPrice($qty) ?? $price;
        }
        
        $common = \App\System::getOptions("common");
         
        if ($common['sell2'] ==1   ) { 
            $price = doubleval($price) ;
            return  round($price);
        }
        return \App\Helper::fa($price);
    }


    /**
    * цена  со  скидками (если  есть)
    *
    * @param mixed $p  массив
    *                  pricetype
    *                  store
    *                  partion
    *                  quantity
    *                  customer
    *                  date
    */
    public function getPriceEx($p=array()) {
        $common = \App\System::getOptions("common");

        if(strlen($p['pricetype'])==0) {
            $p['pricetype'] = 'price1';
        }
        $p['store']   = intval($p['store'] ?? null);
        $p['partion']   = intval($p['partion']?? null);
        $p['quantity']   = intval($p['quantity']?? null);
        $p['customer']   = intval($p['customer'] ?? null);
        $p['date']   = intval($p['date'] ?? null);

        $pureprice = $this->getPurePrice($p['pricetype'], $p['store'], $p['partion']);
        $price = $pureprice;

        $pq=$this->getActionPriceByQuantity($p['quantity']);
        if($pq != null) {
            if ($common['sell2'] ==1   ) { 
                $price = doubleval($price) ;
                return  round($price);
            }            
            
            return \App\Helper::fa($price);
        }
        if ($this->hasAction() && $p['pricetype']  == 'price1') {
            $price = $this->getActionPrice($p['quantity']) ?? $price;

        }
        //если  нет скидок  проверяем  по  контрагенту
        if($price == $pureprice &&  $p['customer']  >0) {
            $c = \App\Entity\Customer::load($p['customer']) ;
            $d = $c->getDiscount();
            if($d >0) {
                $price = \App\Helper::fa($pureprice - ($pureprice*$d/100)) ;
            }
        }
        if ($common['sell2'] ==1   ) { 
            $price = doubleval($price) ;
            return  round($price);
        }

        return \App\Helper::fa($price);
    }


    //последняя  партия true по  приходу  false по расходу
    public function getLastPartion($store = 0, $snumber = "", $in = true) {
        $conn = \ZDB\DB::getConnect();
        $q = $in == true ? "e.quantity >0" : "e.quantity < 0";

        $sql = "  select coalesce(partion,0) as p  from  store_stock st join entrylist e  on st.stock_id = e.stock_id where {$q} and  st.partion>0 and    st.item_id = {$this->item_id}   ";

        if ($store > 0) {
            $sql = $sql . " and st.store_id=" . intval($store);
        }
        if (strlen($snumber) > 0) {
            $sql .= "  and  st.snumber =  " . $conn->qstr($snumber);
        }
     
        $sql = $sql . " order  by  e.entry_id desc  "  ;

        foreach($conn->Execute($sql) as $r) {
           return doubleval($r['p']);            
        }
        
        return 0;
    }

    //средняя  учетная  цена
    public function getPartion($store = 0, $snumber = "",$emp=0) {
        $conn = \ZDB\DB::getConnect();

        $sql = "  select coalesce(sum(partion*qty),0) as p,coalesce(sum(qty),0) as q from  store_stock st  where     st.item_id = {$this->item_id}   ";

        if ($store > 0) {
            $sql = $sql . " and st.store_id=" . $store;
        }
        if ($emp > 0) {
            $sql = $sql . " and st.emp_id=" . $emp;
        }
        if (strlen($snumber) > 0) {
            $sql .= "  and  st.snumber =  " . $conn->qstr($snumber);
        }
     
        $price=0;

        $r=$conn->GetRow($sql) ;
        
        if($r['q'] <> 0) {
          $price  =  abs($r['p']/$r['q']); 
        }
        
        $price = number_format($price, 2, '.', '') ;
        
        return doubleval($price);
    }
    
    
    public static function getPriceTypeList() {

        $common = \App\System::getOptions("common");
        $list = [];

        if (strlen($common['price1']) > 0) {
            $list['price1'] = $common['price1'];
        }
        if (strlen($common['price2']) > 0) {
            $list['price2'] = $common['price2'];
        }
        if (strlen($common['price3']) > 0) {
            $list['price3'] = $common['price3'];
        }
        if (strlen($common['price4']) > 0) {
            $list['price4'] = $common['price4'];
        }
        if (strlen($common['price5']) > 0) {
            $list['price5'] = $common['price5'];
        }

        return $list;
    }

    /**
     * возвращает количество на складах
     *
     * @param mixed $store_id
     * @param mixed $snumber партия проиводителя
     */
    public function getQuantity($store_id = 0, $snumber = "", $date=0, $emp=0) {
        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn = \ZDB\DB::getConnect();
        $where = "   {$cstr}  item_id = {$this->item_id} ";
        if ($store_id > 0) {
            $where .= " and store_id = " . $store_id;
        }
        if ($emp > 0) {
            $where .= " and emp_id = " . $emp;
        }
        if (strlen($snumber) > 0) {
            $where .= " and  snumber = " . $conn->qstr($snumber);
        }

        if($date > 0) {

            $sql = "  select  coalesce(sum(quantity),0)  as totqty  from  entrylist_view where document_date <= ". $conn->DBDate($date) ." and  stock_id in (select  stock_id from  store_stock_view where {$where} )" ;

        } else {
            $sql = "  select coalesce(sum(qty),0) as totqty  from  store_stock_view where ". $where;
        }

        $cnt = $conn->GetOne($sql);
        return $cnt;
    }  
    
    
    //количество  по  складам
    public function getQuantityAllStores() {
        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn = \ZDB\DB::getConnect();
        $where = "   {$cstr}  item_id = {$this->item_id} ";
    
       
        $sql = "  select coalesce(sum(qty),0) as totqty, store_id  from  store_stock_view where ". $where ."  group by store_id ";
       
        $ret=[];
        foreach($conn->Execute($sql) as $r) {
          $ret[$r['store_id']]= $r['totqty'];
        }
        return $ret;
    }

    /**
     * возвращает сумму на складах
     *
     * @param mixed $store_id
     */
    public function getAmount($store_id = 0,$emp_id=0) {
        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn = \ZDB\DB::getConnect();
        $sql = "  select coalesce(sum(qty*partion),0) as amount  from  store_stock_view where   {$cstr}  item_id = {$this->item_id} ";
        if ($store_id > 0) {
            $sql .= " and store_id = " . $store_id;
        }
        if ($emp_id > 0) {
            $sql .= " and emp_id = " . $emp_id;
        }
        $amount = $conn->GetOne($sql);
        return $amount;
    }

    /**
     * проверка  на  списывание  в  миннуч
     *
     * @param mixed $testqty
     * @param mixed $store_id
     * @param mixed $snumber
     */
    public function checkMinus($testqty, $store_id = 0, $snumber = "") {
        $allowminus = \App\System::getOption("common", "allowminus");
        if ($allowminus == 1) {
            return true;
        }

        $qty = $this->getQuantity($store_id, $snumber);

        return doubleval($qty) >= doubleval($testqty);

    }

    /**
     * возвращает список скенрий производителя
     *
     * @param mixed $store_id
     */
    public function getSerials($store_id = 0) {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select snumber  from  store_stock_view where   item_id = {$this->item_id} and qty >0 and snumber <>'' and snumber is not null ";
        if ($store_id > 0) {
            $sql .= " and store_id = " . $store_id;
        }


        $res = $conn->Execute($sql);
        $list = array();
        foreach ($res as $row) {
            if (strlen($row['snumber']) > 0) {
                $list[] = $row['snumber'];
            }
        }
        return $list;
    }

    /**
     * вовращает  самую срочную по  дате  серию  кроме  просроченых
     *
     * @param mixed $store_id
     */
    public function getNearestSerie($store_id = 0) {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select coalesce(snumber,'') as snumber   from  store_stock_view where   item_id = {$this->item_id} and qty >0 and snumber <>'' and snumber is not null   and (  sdate is   null  or sdate >=  now()  )   ";
        if ($store_id > 0) {
            $sql .= " and store_id = " . $store_id;
        }

        $limit =" limit 0,1";
  
        $sql .= " order  by  sdate  desc ". $limit;


        return $conn->GetOne($sql);

    }


    /**
     * Метод  для   получения  имени  ТМЦ   для выпадающих списков
     *
     * @param mixed $partname
     * @static
     */
    public static function findArrayAC($partname, $store = 0, $cat = 0) {

        $criteria = "  disabled <> 1 ";
        if ($store > 0) {
            $criteria .= "     and item_id in (select item_id from store_stock  where  store_id={$store})";
        }
        if ($cat > 0) {
            $criteria .= "  and cat_id={$cat}    and item_id in (select item_id from store_stock  where  store_id={$store})";
        }

        if (strlen($partname) > 0) {
            $like = self::qstr('%' . $partname . '%');
            $partname = self::qstr($partname);
            $criteria .= "  and  (itemname like {$like} or item_code like {$like}   or   bar_code like {$like} )";
        }

        $itemlist = self::find($criteria);

        $list = array();
        foreach ($itemlist as $key => $value) {

            $list[$key] = $value->itemname;
            if (strlen($value->item_code) > 0) {
                $list[$key] = $value->itemname . ', ' . $value->item_code;
            }
            if (strlen($value->manufacturer) > 0) {
                $list[$key] = $value->itemname . ' (' . $value->manufacturer . ')';
            }
        }

        return $list;
    }

    /**
    * поиск  по  штрих коду
    * 
    * @param mixed $code
    * @param mixed $store_id
    * @param mixed $cat_id
    */
    public static function findBarCode($code, $store_id = 0,$cat_id=0) {
            $code0 = ltrim($code, '0');
            $codes = Item::qstr($code) ;
            $code0s = Item::qstr($code0) ;
            $codex= trim($codes,"'") ;
            $code0x= trim($code0s,"'") ;
            
            $w='';
            if ($cat_id > 0) {


                $c = Category::load($cat_id) ;
                $ch = $c->getChildren();
                $ch[]=$cat_id;
                $cats = implode(",", $ch)  ;


                $w =   "  cat_id in ({$cats}) and  ";
            }            
            
            
            if($store_id > 0)  {
                $item = Item::getFirst($w." item_id in(select item_id from store_stock where store_id={$store_id}) and   (item_code = {$codes} or bar_code = {$codes} or bar_code = {$code0s}   or detail like '%<bar_code1><![CDATA[{$codex}]]></bar_code1>%'   or detail like '%<bar_code2><![CDATA[{$codex}]]></bar_code2>%'   or detail like '%<bar_code1><![CDATA[{$code0x}]]></bar_code1>%'   or detail like '%<bar_code2><![CDATA[{$code0x}]]></bar_code2>%' )");
            }   else {
                $item = Item::getFirst($w."  item_code = {$codes} or bar_code = {$codes} or bar_code = {$code0s}  or detail like '%<bar_code1><![CDATA[{$codex}]]></bar_code1>%'   or detail like '%<bar_code2><![CDATA[{$codex}]]></bar_code2>%'   or detail like '%<bar_code1><![CDATA[{$code0x}]]></bar_code1>%'   or detail like '%<bar_code2><![CDATA[{$code0x}]]></bar_code2>%'   ");
            }
            return $item;
    }
      
    /**
     * генерирует новый артикул
     *
     */
    public static function getNextArticle() {
        
        if (\App\System::getOption("common", "autoarticle") != 1) {
            return "";    //не генерим
        }        
        $options = \App\System::getOptions('common');
            
        $conn = \ZDB\DB::getConnect();
        $letters = $options['articleprefix'] ?? "ID";
        $like= $letters=="" ?"" : " like '{$letters}%'" ;
        $last=0;
        $sql = "select item_code from  items where  item_code {$like}   order  by  item_id desc   ";  
 
        foreach($conn->Execute($sql) as $row) {
           $digits = intval( preg_replace('/[^0-9]/', '', $row['item_code']) );
           if($digits > $last) {
              $last =  $digits ; //максимальная цифра
           }
        }
        
        $last++;
          
      //  $l =  gmp_init($last, 10);
     //   $l=   gmp_add( $l , (gmp_init(1))) ;
      //  $last = gmp_strval($l, 10);
        
        $d=5;
        if( strlen( ''.$last) >$d){ //если не  влазит
           $d =  strlen( ''.$last); 
        }
        if(strlen($letters) >0){
           $next = "".$letters . sprintf("%0{$d}d", $last);
        } else {
           $next = "".$last;
        }

        return $next;
    }

    /**
    * проверка  уникальности артикула
    * возвращает true если  уникальный
    */
    public   function checkUniqueArticle( ) {
        if (\App\System::getOption("common", "nocheckarticle") == 1) {
            return true; //не проверяем
        }        
        if (strlen($this->item_code) ==0 ) {
            return true;
        }
        $code = Item::qstr($this->item_code);
        
        if(strlen($this->manufacturer)==0){
            $where = "item_id <> {$this->item_id} and  item_code={$code} ";  
        }  else {
             $manufacturer = Item::qstr($this->manufacturer);

             $where = "item_id <> {$this->item_id} and ( item_code={$code} and manufacturer= {$manufacturer} )";  
        }
        $cnt = Item::findCnt($where);
        if ($cnt > 0) {
            return false;
        }

        return true;             
    }    
    
    /**
     * список производителей
     *
     * @param mixed $nametoindex добавить имя в  индекс (для  комбобоксов)
     */
    public static function getManufacturers($nametoindex = false) {

        $conn = \ZDB\DB::getConnect();

        $sql = "  select distinct manufacturer from  items  where  disabled<> 1  order  by manufacturer";
        $res = $conn->Execute($sql);
        $list = array();
        foreach ($res as $v) {
            if (strlen($v['manufacturer'] ?? '') > 0) {
                if ($nametoindex) {
                    $list[$v['manufacturer']] = $v['manufacturer'];
                } else {
                    $list[] = $v['manufacturer'];
                }
            }
        }
        return $list;
    }

    public static function getTypes() {
        $list = array();

        $list[Item::TYPE_TOVAR] = "Товар";
        $list[Item::TYPE_MAT] = "Матеріали та комплектуючі";
        $list[Item::TYPE_MBP] = "МБП (малоцінка)";
        $list[Item::TYPE_PROD] = "Готова продукція";
        $list[Item::TYPE_HALFPROD] = "Напівфабрикати";

        return $list;
    }


    /**
     * себестоимость  для  готовой продукции
     *
     */
    public function getProdprice() {
        $price = 0;
        if ($this->costprice > 0) {
            $price += doubleval($this->costprice);
        }
        else {
            $ilist = \App\Entity\ItemSet::find("pitem_id=" . $this->item_id);

            if (count($ilist) > 0) {
                foreach ($ilist as $iset) {

                    if($iset->item_id > 0) {
                        $it = \App\Entity\Item::load($iset->item_id);
                        $pr = $it->getPartion(0);
                        $price += doubleval($iset->qty * $pr);
                    }
                    if($iset->service_id >0) {
                        $price += doubleval($iset->cost);

                    }
                }
            }
            
        }
        
        if ($price == 0) {   
            $price = $this->getPartion(0);
        }
        if($price==0) {
            $price = $this->getLastPartion() ;
        }
        if($price==0) {
            \App\System::setWarnMsg("Для {$this->itemname} не  вирахувано собївартїсть") ;
        }
        return $price;
    }


    public function getID() {
        return $this->item_id;
    }

     /**
     * упаковка  штрих кода для  стикера 
     * 
     * @param mixed $price
     * @param mixed $qty
     * @param mixed $item_id
     */
     public static function packStBC($price,$qty,$item_id) {
   
        $price=doubleval(\App\Helper::fa($price));
        $qty=doubleval(\App\Helper::fqty($qty));
          
        $barcode = "".$price.'-'.$qty. '-' . $item_id;  
        
        return $barcode;
     }
    
    /**
    * раcпаковка штрих кода стикера
    * 
    * @param mixed $barcode
    */
     public static function unpackStBC($barcode) {
 
        
        $s=explode('-',$barcode) ;
        
        $item= Item::load(trim($s[2]??0));
        if($item != null)  {
                
                $item->price = \App\Helper::fa($s[0]);
                $item->quantity =\App\Helper::fqty($s[1]);
               
        }   
        return $item;            
     }
     
     /**
     * сохранить значения  кастомных  полей
     * 
     * @param mixed $cf     код=>значение
     */
     public function savecf($cf){
         if(!is_array($cf)) {
             $cf=[];
         }
        $this->cflist  = serialize($cf);
     }
     /**
     * вернуть  значения кастомных  полей
     * 
     */
     public function getcf(){
        $cfv = []  ;
        if(strlen($this->cflist)>0) {
          $cfv=unserialize($this->cflist)   ;   
        }
        $options = \App\System::getOptions('common');
        $cflist = $options['cflist'] ?? [];
        $i=1;
        $cat = Category::load($this->cat_id);
        if($cat != null)   {
            foreach($cat->cflist as $k=>$v){
                $ls = new \App\DataItem();
                $ls->code = $k;
                $ls->name = $v;
               
              $cflist[$i++] = $ls;          
                    
            }
        }
        $i=1;
        $ret=[];
        foreach($cflist as $cf=>$f) {

                  $it = new \App\DataItem()  ;
                  $it->id= $i++;
                  $it->code= $f->code;
                  $it->name= $f->name;
                  $it->val='';
                  foreach($cfv as $cv=>$v) {
                    if($f->code==$cv)  {    
                       $it->val= $v;
                    }
                  }
                  $ret[$it->code]=$it;
             
         
        }  
       
        return $ret;
     }
   
     /**
     * возвращает ссылку  на  изображение
     * 
     * @param mixed $shop  для  онлайн каталога (не проверяется  доступ)
     * @param mixed $t предпросмотр (thumbmil) если  есть
     * @return mixed
     */
     public function getImageUrl($shop=false,$t=false){ 
        
        if ($this->image_id > 0){
           if($shop) {
               return "/loadshopimage.php?id=".$this->image_id . ($t ? '&t=t' : '');    
           }   else {
               return "/loadimage.php?id=".$this->image_id;           
           }
           
        }   
        if (strlen($this->imageurl)>0){
           return $this->imageurl;
        }   
        return;    
     } 
     
    /**
    * аплоад  в БД изображения  по  url
    * 
    * @param mixed $url
    * @param mixed $dothumb
    */
    public   function saveImage($url,$dothumb=true) {
        $file = file_get_contents($url) ;
        if(strlen($file)==0) {
           return 0  ;
        }
        $tmp = tempnam(sys_get_temp_dir(), "import") ;
        file_put_contents($tmp, $file) ;
        if (filesize($tmp) > 1024*1024) {
             
             return 0;
        }
            
        $imagedata = getimagesize($tmp);
        if ($imagedata== false) {
            return 0  ;

        }
        $image = new \App\Entity\Image();
        $image->content = file_get_contents($tmp);
        $image->mime = $imagedata['mime'];

        if ($imagedata[0] != $imagedata[1]) {
            $thumb = new \App\Thumb($tmp);
            if ($imagedata[0] > $imagedata[1]) {
                $thumb->cropFromCenter($imagedata[1], $imagedata[1]);
            }
            if ($imagedata[0] < $imagedata[1]) {
                $thumb->cropFromCenter($imagedata[0], $imagedata[0]);
            }


            $image->content = $thumb->getImageAsString();
  
            $thumb->resize(512, 512);
            if($dothumb) {
               $image->thumb = $thumb->getImageAsString();
               $thumb->resize(128, 128);
               $this->thumb = "data:{$image->mime};base64," . base64_encode($thumb->getImageAsString());               
            }   

           
        }


        $image->save(); 
        $this->image_id=$image->image_id ;
        return $image->image_id;       
    }
     
     
}
