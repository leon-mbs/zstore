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

    const TYPE_TOVAR    = 1;   //товар
    const TYPE_MAT      = 2;   //материалы  и комплектующие
    const TYPE_MBP      = 3;   //малоценка
    const TYPE_PROD     = 4;   //готовая продукция
    const TYPE_HALFPROD = 5;   //полуфабрикаты

    private $brprice = array(); //цены по  филиалам

    protected function init() {
        $this->item_id = 0;
        $this->cat_id = 0;
        $this->item_type = self::TYPE_TOVAR;
        $this->msr = "шт";

        $this->price = 0;
        $this->image_id = 0;
        $this->noprice = 0;
        $this->noshop = 0;
        $this->foodstate = 0;
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
        $this->thumb = (string)($xml->thumb[0]);

        $this->noprice = (int)$xml->noprice[0];
        $this->noshop = (int)$xml->noshop[0];
        $this->autooutcome = (int)$xml->autooutcome[0];
        $this->autoincome = (int)$xml->autoincome[0];
        $this->useserial = (int)$xml->useserial[0];
        $this->image_id = (int)$xml->image_id[0];

        $this->weight = (string)$xml->weight[0];
        $this->maxsize = (string)$xml->maxsize[0];
        $this->volume = (string)$xml->volume[0];
        $this->lost = (string)$xml->lost[0];
        $this->customsize = (string)$xml->customsize[0];
        $this->manufacturer = (string)$xml->manufacturer[0];
        $this->shortname = (string)$xml->shortname[0];
        $this->warranty = (string)$xml->warranty[0];
        $this->extdata = (string)$xml->extdata[0];
        $this->sef = (string)$xml->sef[0];
        $this->url = (string)$xml->url[0];
        $this->foodstate = (int)$xml->foodstate[0];

        $this->cell = (string)$xml->cell[0];
        //  $this->octoreoptions = (string) $xml->octoreoptions[0];
        $brprice = (string)$xml->brprice[0];

        $this->brprice = @unserialize($brprice);
        if (!is_array($this->brprice)) {
            $this->brprice = array();
        }

        $id = \App\System::getBranch();
        if ($id > 0 && is_array($this->brprice[$id])) {
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


        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
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
        $this->detail .= "<noprice>{$this->noprice}</noprice>";
        $this->detail .= "<noshop>{$this->noshop}</noshop>";
        $this->detail .= "<autooutcome>{$this->autooutcome}</autooutcome>";
        $this->detail .= "<autoincome>{$this->autoincome}</autoincome>";
        $this->detail .= "<useserial>{$this->useserial}</useserial>";

        $this->detail .= "<cell>{$this->cell}</cell>";
        //   $this->detail .= "<octoreoptions><![CDATA[{$this->octoreoptions}]]></octoreoptions>";
        $this->detail .= "<manufacturer><![CDATA[{$this->manufacturer}]]></manufacturer>";
        $this->detail .= "<shortname><![CDATA[{$this->shortname}]]></shortname>";
        $this->detail .= "<warranty><![CDATA[{$this->warranty}]]></warranty>";
        $this->detail .= "<extdata><![CDATA[{$this->extdata}]]></extdata>";

        $this->detail .= "<price1>{$this->price1}</price1>";
        $this->detail .= "<price2>{$this->price2}</price2>";
        $this->detail .= "<price3>{$this->price3}</price3>";
        $this->detail .= "<price4>{$this->price4}</price4>";
        $this->detail .= "<price5>{$this->price5}</price5>";
        $this->detail .= "<val>{$this->val}</val>";
        $this->detail .= "<rate>{$this->rate}</rate>";
        $this->detail .= "<zarp>{$this->zarp}</zarp>";
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


        if ($this->image_id > 0) {
            \App\Entity\Image::delete($this->image_id);
        }
    }

    //Вычисляет  отпускную цену без скидок
    //$_price - цифра (заданая цена) или  наименование  цены из настроек 
    //$store - склад
    //$partion - партия
    public function getPurePrice($_price_ = 'price1', $store = 0, $partion = 0) {
        $price = 0;
        $_price = 0;
        $common = \App\System::getOptions("common");
        if (strlen($common[$_price_]) == 0) {
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
            if (is_numeric($ret) ) {
                if ($partion == 0) {
                    //ищем последнюю закупочную  цену 
                    $partion = $this->getLastPartion($store);
                }
                $price = $partion + (int)$partion / 100 * $ret;
                //курсовая разница
                $opv = \App\System::getOptions("val");
                if (strlen($this->val) > 1 && $opv['valprice'] == 1) {
                    $k = $opv[$this->val] / $this->rate;
                    $price = $price * $k;
                }
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
                    $partion = $this->getLastPartion($store);
                }
                if ($_price_ == 'price1' && $cat->price1 > 0) {
                    $price = $partion + (int)$partion / 100 * $cat->price1;
                }
                if ($_price_ == 'price2' && $cat->price2 > 0) {
                    $price = $partion + (int)$partion / 100 * $cat->price2;
                }
                if ($_price_ == 'price3' && $cat->price3 > 0) {
                    $price = $partion + (int)$partion / 100 * $cat->price3;
                }
                if ($_price_ == 'price4' && $cat->price4 > 0) {
                    $price = $partion + (int)$partion / 100 * $cat->price4;
                }
                if ($_price_ == 'price5' && $cat->price5 > 0) {
                    $price = $partion + (int)$partion / 100 * $cat->price5;
                }

                //курсовая разница
                $opv = \App\System::getOptions("val");
                if (strlen($this->val) > 1 && $opv['valprice'] == 1) {
                    $k = $opv[$this->val] / $this->rate;
                    $price = $price * $k;
                }
            }
        }


        //если не  задано используем глобальную наценку
        if ($common['defprice'] > 0 && $price == 0) {

            if ($partion == 0) {
                //ищем последнюю закупочную  цену 
                $partion = $this->getLastPartion($store);
            }

            $price = $partion + (int)$partion / 100 * $common['defprice'];
            //курсовая разница
            $opv = \App\System::getOptions("val");
            if (strlen($this->val) > 1 && $opv['valprice'] == 1) {
                $k = $opv[$this->val] / $this->rate;
                $price = $price * $k;
            }
        }

         
        //если  не  задана  наценка и цена  то  берем  закупочную
        if ( intval($common['defprice']) == 0 && $price == 0) {

            if ($partion == 0) {
                //ищем последнюю закупочную  цену 
                $partion = $this->getLastPartion($store);
            }
            $price =  $partion;
            
            //курсовая разница
            $opv = \App\System::getOptions("val");
            if (strlen($this->val) > 1 && $opv['valprice'] == 1) {
                $k = $opv[$this->val] / $this->rate;
                $price = $price * $k;
            }
        }


        return $price;
    }

    public function hasAction() {
        if (doubleval($this->actionprice) > 0 || doubleval($this->actiondisc > 0)) {

            if ( intval($this->fromdate) < time() && intval($this->todate) > time()) {
                return true;
            }

        }
        if( doubleval($this->actionqty1) > 0) {
            return true;
        }
        return false;
    }

    //цена  со  скидкой
    public function getActionPrice($price,$qty=0) {
        if (doubleval($this->actionprice) > 0) {
            
            if ( intval($this->fromdate) < time() && intval($this->todate) > time()) {
                return $this->actionprice;
            }
            
            
            
        }
        
            if ( $this->actionprice2 >0 && doubleval($this->actionqty2) <= $qty && $qty>1) {
                return $this->actionprice2;
            }
            if ($this->actionprice1 >0 &&  doubleval($this->actionqty1) <= $qty && $qty>1 ) {
                return $this->actionprice1;
            }
        
        if (doubleval($this->actiondisc) > 0 && intval($this->fromdate) < time() && intval($this->todate) > time()) {   //по  категории
            return ($price - $price * $this->actiondisc / 100);
        }

        return $price;

    }

    //цена  со  скидками (если  есть)
    public function getPrice($_price_ = 'price1', $store = 0, $partion = 0,$qty=0) {
        if(strlen($_price_)==0) $_price_ = 'price1';
        $price = $this->getPurePrice($_price_, $store, $partion);
        if ($this->hasAction() && $_price_ == 'price1') {
            $price = $this->getActionPrice($price,$qty);

        }

        return \App\Helper::fa($price);
    }


    //последняя  партия true по  приходу  false по расходу
    public function getLastPartion($store = 0, $snumber = "", $gi = true) {
        $conn = \ZDB\DB::getConnect();
        $q = $gi == true ? "e.quantity >0" : "e.quantity <0";

        $sql = "  select coalesce(partion,0)  from  store_stock st join entrylist e  on st.stock_id = e.stock_id where {$q} and  st.partion>0 and    st.item_id = {$this->item_id}   ";

        if ($store > 0) {
            $sql = $sql . " and st.store_id=" . $store;
        }
        if (strlen($snumber) > 0) {
            $sql .= "  and  st.snumber =  " . $conn->qstr($snumber);
        }
        $limit =" limit 0,1";
        if($conn->dataProvider=="postgres") {
            $limit =" limit 1";
        }
        $sql = $sql . " order  by  e.document_id desc  ".$limit;

        return doubleval($conn->GetOne($sql));
    }

    public static function getPriceTypeList() {

        $common = \App\System::getOptions("common");
        $list = array();
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
     * @param mixed $item_id
     * @param mixed $store_id
     * @param mixed $snumber партия проиводителя
     */
    public function getQuantity($store_id = 0, $snumber = "",$date=0) {
        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn = \ZDB\DB::getConnect();
        $where = "   {$cstr}  item_id = {$this->item_id} ";
        if ($store_id > 0) {
            $where .= " and store_id = " . $store_id;
        }
        if (strlen($snumber) > 0) {
            $where .= " and  snumber = " . $conn->qstr($snumber);
        }

        if($date > 0){
            
            $sql = "  select  coalesce(sum(quantity),0)  as totqty  from  entrylist_view where document_date <= ". $conn->DBDate($date) ." and  stock_id in (select  stock_id from  store_stock_view where {$where} )" ;    
     
        }   else{
            $sql = "  select coalesce(sum(qty),0) as totqty  from  store_stock_view where ". $where;    
        }
          
        $cnt = $conn->GetOne($sql);
        return $cnt;
    }

    /**
     * возвращает сумму на складах
     *
     * @param mixed $item_id
     * @param mixed $store_id
     */
    public function getAmount($store_id = 0) {
        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn = \ZDB\DB::getConnect();
        $sql = "  select coalesce(sum(qty*partion),0) as amount  from  store_stock_view where   {$cstr}  item_id = {$this->item_id} ";
        if ($store_id > 0) {
            $sql .= " and store_id = " . $store_id;
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
            if($conn->dataProvider=="postgres") {
                $limit =" limit 1";
            }          
        $sql .= " order  by  sdate  desc ". $limit;


        return $conn->GetOne($sql);

    }


    /**
     * Метод  для   получения  имени  ТМЦ   для выпадающих списков
     *
     * @param mixed $criteria
     * @return []
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
     * генерирует новый артикул
     *
     */
    public static function getNextArticle() {
        $conn = \ZDB\DB::getConnect();

        $sql = "  select max(item_id)  from  items ";
        $id = $conn->GetOne($sql);

        return "ID" . sprintf("%04d", ++$id);
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
            if (strlen($v['manufacturer']) > 0) {
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

        $list[Item::TYPE_TOVAR] = \App\Helper::l('it_tovar');
        $list[Item::TYPE_MAT] = \App\Helper::l('it_mat');
        $list[Item::TYPE_MBP] = \App\Helper::l('it_mbp');
        $list[Item::TYPE_PROD] = \App\Helper::l('it_prod');
        $list[Item::TYPE_HALFPROD] = \App\Helper::l('it_hprod');

        return $list;
    }

    /**
     * себестоимость  для  готовой продукции
     *
     */
    public function getProdprice() {
        $price = 0;
        if ($this->zarp > 0) {
            $price += $this->zarp;
        }
        $ilist = \App\Entity\ItemSet::find("pitem_id=" . $this->item_id);

        if (count($ilist) > 0) {
            foreach ($ilist as $iset) {
                
                if($iset->item_id > 0) {
                    $it = \App\Entity\Item::load($iset->item_id);
                    $pr = $it->getLastPartion(0);
                    $price += ($iset->qty * $pr);
                }
                if($iset->service_id >0) {
                    $price += ($iset->cost);
        
                }                    
            }
        }
        if ($price == 0) {  //ищем  последнюю  партию
            $price = $this->getLastPartion(0);
        }

        return $price;
    }

    
    public   function getID() {
        return $this->item_id;
    }
}
